<?php

namespace App\Console\Commands;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Grado;
use App\Models\Rol;
use App\Models\Seccion;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga masiva de alumnos desde un JSON extraído de los listados oficiales
 * de MINEDUC ("LISTADO DE ALUMNOS INSCRITOS").
 *
 * No se hace con INSERT de SQL crudo porque cada alumno necesita además una
 * fila en "usuario" con la contraseña hasheada con bcrypt y su fila en
 * "usuario_rol": SQL no puede calcular un hash bcrypt, y sin eso los alumnos
 * no podrían iniciar sesión.
 *
 * Es idempotente: la clave natural es codigo_mineduc (que también es el
 * username), así que volver a correrlo actualiza en lugar de duplicar.
 * Todo va en una transacción — si algo falla, no queda nada a medias.
 */
class ImportarAlumnos extends Command
{
    protected $signature = 'alumnos:importar
                            {archivo : Ruta al JSON con los alumnos}
                            {--dry-run : Muestra qué haría, sin escribir nada}
                            {--passwords= : Ruta donde escribir un CSV con las contraseñas temporales}';

    protected $description = 'Importa alumnos desde un JSON de listados MINEDUC (crea usuario, rol y perfil de alumno)';

    /** Los PDF dicen CUARTO/QUINTO/SEXTO; en la base los grados se llaman 4TO/5TO/6TO. */
    private const GRADOS = [
        'CUARTO' => '4TO',
        'QUINTO' => '5TO',
        'SEXTO' => '6TO',
    ];

    public function handle(): int
    {
        $ruta = $this->argument('archivo');

        if (!is_readable($ruta)) {
            $this->error("No puedo leer el archivo: {$ruta}");
            return self::FAILURE;
        }

        $registros = json_decode(file_get_contents($ruta), true);
        if (!is_array($registros) || $registros === []) {
            $this->error('El archivo no contiene un arreglo JSON válido de alumnos.');
            return self::FAILURE;
        }

        if ($errores = $this->validar($registros)) {
            $this->error('El archivo tiene problemas; no se importó nada:');
            foreach ($errores as $e) {
                $this->line("  - {$e}");
            }
            return self::FAILURE;
        }

        $seco = (bool) $this->option('dry-run');
        $this->info(sprintf(
            '%s %d alumnos desde %s',
            $seco ? '[DRY-RUN] Simulando' : 'Importando',
            count($registros),
            basename($ruta)
        ));

        $credenciales = [];
        $resumen = ['creados' => 0, 'actualizados' => 0];

        try {
            DB::transaction(function () use ($registros, $seco, &$credenciales, &$resumen) {
                $rolAlumno = Rol::where('nombre', 'alumno')->firstOrFail();

                foreach ($registros as $r) {
                    $this->importarUno($r, $rolAlumno, $credenciales, $resumen);
                }

                if ($seco) {
                    // Deshace todo: sirve para ver el resumen y que cualquier
                    // violación de restricción aparezca antes de escribir nada.
                    throw new DryRunException();
                }
            });
        } catch (DryRunException) {
            // Esperado en --dry-run; la transacción ya se deshizo.
        }

        $this->mostrarResumen($resumen, $seco);
        $this->escribirPasswords($credenciales, $seco);

        return self::SUCCESS;
    }

    private function importarUno(array $r, Rol $rolAlumno, array &$credenciales, array &$resumen): void
    {
        $carrera = Carrera::firstOrCreate(
            ['nombre_carrera' => $r['nombre_carrera']],
            ['descripcion' => 'Código MINEDUC ' . ($r['codigo_carrera'] ?? '—')]
        );
        $grado = Grado::firstOrCreate(
            ['nombre' => $this->nombreGrado($r['grado'])],
            ['nivel' => 'Diversificado']
        );
        $seccion = Seccion::firstOrCreate(['nombre' => strtoupper($r['seccion'])]);

        $fechaNacimiento = $this->aFechaIso($r['fecha_nacimiento']);
        $password = $this->generarPassword($r['nombre'], $r['apellido'], $fechaNacimiento);

        $usuario = Usuario::where('username', $r['codigo_mineduc'])->first();

        if ($usuario === null) {
            $usuario = new Usuario(['username' => $r['codigo_mineduc']]);
            $usuario->password = bcrypt($password);
            $usuario->estado = 'activo';
            $usuario->password_change_required = true;
            $usuario->nombre = $r['nombre'];
            $usuario->apellido = $r['apellido'];
            $usuario->save();

            $resumen['creados']++;
            // La contraseña solo se puede entregar en la corrida que crea la
            // cuenta: después queda hasheada y no hay forma de recuperarla.
            $credenciales[] = [
                $r['codigo_mineduc'],
                $r['apellido'] . ', ' . $r['nombre'],
                $this->nombreGrado($r['grado']),
                $password,
            ];
        } else {
            $resumen['actualizados']++;
        }

        $usuario->roles()->syncWithoutDetaching([$rolAlumno->id_rol]);

        Alumno::updateOrCreate(
            ['id_usuario' => $usuario->id_usuario],
            [
                'nombre' => $r['nombre'],
                'apellido' => $r['apellido'],
                'codigo_mineduc' => $r['codigo_mineduc'],
                'fecha_nacimiento' => $fechaNacimiento,
                'genero' => $r['genero'],
                'nacionalidad' => $r['nacionalidad'],
                'tipo_documento' => $r['tipo_documento'],
                'numero_documento' => $r['numero_documento'],
                'id_carrera' => $carrera->id_carrera,
                'id_grado_actual' => $grado->id_grado,
                'id_seccion_actual' => $seccion->id_seccion,
                'estado_academico' => 'activo',
            ]
        );
    }

    /**
     * Valida el lote entero antes de tocar la base, para que un dato malo en
     * la fila 140 no aparezca recién después de haber escrito 139 alumnos.
     */
    private function validar(array $registros): array
    {
        $errores = [];
        $documentos = [];
        $codigos = [];

        $requeridos = [
            'codigo_mineduc', 'nombre', 'apellido', 'fecha_nacimiento', 'genero',
            'nacionalidad', 'tipo_documento', 'numero_documento', 'grado',
            'seccion', 'nombre_carrera',
        ];

        foreach ($registros as $i => $r) {
            $fila = $i + 1;

            foreach ($requeridos as $campo) {
                if (empty($r[$campo])) {
                    $errores[] = "fila {$fila}: falta '{$campo}'";
                }
            }
            if (isset($r['genero']) && !in_array($r['genero'], ['masculino', 'femenino'], true)) {
                $errores[] = "fila {$fila}: género inválido '{$r['genero']}'";
            }
            if (isset($r['tipo_documento']) && !in_array($r['tipo_documento'], ['cui', 'pasaporte'], true)) {
                $errores[] = "fila {$fila}: tipo de documento inválido '{$r['tipo_documento']}'";
            }
            if (isset($r['fecha_nacimiento']) && !$this->aFechaIso($r['fecha_nacimiento'])) {
                $errores[] = "fila {$fila}: fecha de nacimiento inválida '{$r['fecha_nacimiento']}'";
            }

            foreach ([['numero_documento', $documentos], ['codigo_mineduc', $codigos]] as [$campo, $_]) {
                if (!isset($r[$campo])) {
                    continue;
                }
                $vistos = $campo === 'numero_documento' ? $documentos : $codigos;
                if (isset($vistos[$r[$campo]])) {
                    $errores[] = "fila {$fila}: {$campo} '{$r[$campo]}' repetido (ya en la fila {$vistos[$r[$campo]]})";
                }
                if ($campo === 'numero_documento') {
                    $documentos[$r[$campo]] = $fila;
                } else {
                    $codigos[$r[$campo]] = $fila;
                }
            }
        }

        // Un documento que ya exista en la base pero pertenezca a OTRO alumno
        // rompería el índice único a mitad de la importación.
        foreach ($registros as $r) {
            if (empty($r['numero_documento'])) {
                continue;
            }
            $duenio = Alumno::where('numero_documento', $r['numero_documento'])->first();
            if ($duenio && $duenio->codigo_mineduc !== ($r['codigo_mineduc'] ?? null)) {
                $errores[] = "documento {$r['numero_documento']} ya está en la base con otro alumno "
                    . "(código {$duenio->codigo_mineduc}); en el archivo viene como {$r['codigo_mineduc']}";
            }
        }

        return $errores;
    }

    private function nombreGrado(string $grado): string
    {
        $g = strtoupper(trim($grado));
        return self::GRADOS[$g] ?? $g;
    }

    private function aFechaIso(string $fecha): ?string
    {
        $partes = explode('/', $fecha);
        if (count($partes) !== 3) {
            return null;
        }
        [$d, $m, $a] = $partes;
        if (!checkdate((int) $m, (int) $d, (int) $a)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $a, $m, $d);
    }

    /** Misma convención que AlumnoController, para no tener dos reglas distintas. */
    private function generarPassword(string $nombre, string $apellido, string $fechaNacimiento): string
    {
        $inicialApellido = Str::lower(Str::substr($apellido, 0, 1));
        $primerNombre = str_replace(' ', '', Str::lower($nombre));
        $anio = date('Y', strtotime($fechaNacimiento));

        return $inicialApellido . $primerNombre . $anio;
    }

    private function mostrarResumen(array $resumen, bool $seco): void
    {
        $this->newLine();
        $this->table(['', 'Alumnos'], [
            ['Creados', $resumen['creados']],
            ['Actualizados (ya existían)', $resumen['actualizados']],
        ]);

        if ($seco) {
            $this->warn('DRY-RUN: no se escribió nada, la transacción se deshizo.');
        }
    }

    private function escribirPasswords(array $credenciales, bool $seco): void
    {
        if ($credenciales === []) {
            return;
        }

        $destino = $this->option('passwords');

        if (!$destino) {
            $this->warn(sprintf(
                'Se %s %d cuentas. Sin --passwords no queda registro de sus contraseñas temporales, '
                . 'y una vez hasheadas no se pueden recuperar.',
                $seco ? 'habrían creado' : 'crearon',
                count($credenciales)
            ));
            return;
        }

        if ($seco) {
            $this->line('DRY-RUN: se habrían escrito ' . count($credenciales) . " credenciales en {$destino}");
            return;
        }

        $fh = fopen($destino, 'w');
        fputcsv($fh, ['codigo_mineduc', 'alumno', 'grado', 'password_temporal']);
        foreach ($credenciales as $fila) {
            fputcsv($fh, $fila);
        }
        fclose($fh);
        @chmod($destino, 0600);

        $this->info("Contraseñas temporales escritas en {$destino} (permisos 600).");
        $this->warn('Contiene credenciales: entregalas y borrá el archivo. Todos deben cambiarla al primer ingreso.');
    }
}

/** Señal interna para deshacer la transacción en --dry-run. */
class DryRunException extends \RuntimeException
{
}

<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Catedratico;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Edificio;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\PeriodoAcademico;
use App\Models\Bitacora;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $rolAdmin = Rol::where('nombre', 'admin')->firstOrFail();
            $rolAlumno = Rol::where('nombre', 'alumno')->firstOrFail();
            $rolCatedratico = Rol::where('nombre', 'catedratico')->firstOrFail();

            // 1. Admin
            $admin = Usuario::firstOrCreate(
                ['username' => 'admin'],
                ['password' => bcrypt('admin123'), 'estado' => 'activo']
            );
            if (!$admin->roles()->where('rol.id_rol', $rolAdmin->id_rol)->exists()) {
                $admin->roles()->attach($rolAdmin->id_rol);
            }

            // 2. Carrera de prueba
            $carrera = Carrera::firstOrCreate(
                ['nombre_carrera' => 'Ingeniería en Sistemas'],
                ['descripcion' => 'Carrera de Ingeniería en Sistemas']
            );

            // 3. Alumno de prueba
            $alumnoUser = Usuario::firstOrCreate(
                ['username' => 'ALG001'],
                ['password' => bcrypt('pjuan2005'), 'estado' => 'activo', 'password_change_required' => true]
            );
            if (!$alumnoUser->roles()->where('rol.id_rol', $rolAlumno->id_rol)->exists()) {
                $alumnoUser->roles()->attach($rolAlumno->id_rol);
            }
            $alumnoUser->update(['password_change_required' => true]);
            Alumno::firstOrCreate(
                ['id_usuario' => $alumnoUser->id_usuario],
                [
                    'nombre' => 'Juan',
                    'apellido' => 'Pérez',
                    'codigo_mineduc' => 'ALG001',
                    'correo' => 'juan@example.com',
                    'telefono' => '55550101',
                    'fecha_nacimiento' => '2005-03-15',
                    'nacionalidad' => 'Guatemalteca',
                    'tipo_documento' => 'cui',
                    'numero_documento' => '1234567890101',
                    'id_carrera' => $carrera->id_carrera,
                ]
            );

            // 4. Catedratico de prueba
            $catUser = Usuario::firstOrCreate(
                ['username' => 'CAT001'],
                ['password' => bcrypt('lmaria'), 'estado' => 'activo', 'password_change_required' => true]
            );
            if (!$catUser->roles()->where('rol.id_rol', $rolCatedratico->id_rol)->exists()) {
                $catUser->roles()->attach($rolCatedratico->id_rol);
            }
            $catUser->update(['password_change_required' => true]);
            Catedratico::firstOrCreate(
                ['id_usuario' => $catUser->id_usuario],
                [
                    'codigo' => 'CAT001',
                    'nombre' => 'María',
                    'apellido' => 'López',
                    'especialidad' => 'Matemáticas',
                    'correo' => 'maria@example.com',
                    'telefono' => '55550202',
                ]
            );

            // 5. Alumno Hugo
            $hugoUser = Usuario::firstOrCreate(
                ['username' => '32032345'],
                ['password' => bcrypt('mhugoalejandro2026'), 'estado' => 'activo', 'password_change_required' => true]
            );
            if (!$hugoUser->roles()->where('rol.id_rol', $rolAlumno->id_rol)->exists()) {
                $hugoUser->roles()->attach($rolAlumno->id_rol);
            }
            $hugoUser->update(['password_change_required' => true]);
            Alumno::firstOrCreate(
                ['id_usuario' => $hugoUser->id_usuario],
                [
                    'nombre' => 'Hugo Alejandro',
                    'apellido' => 'Mendez Diaz',
                    'codigo_mineduc' => '32032345',
                    'correo' => 'hhugodiaz23@gmail.com',
                    'telefono' => '32032358',
                    'fecha_nacimiento' => '2026-07-05',
                    'nacionalidad' => 'Guatemalteca',
                    'tipo_documento' => 'cui',
                    'numero_documento' => '3203234500101',
                    'id_carrera' => $carrera->id_carrera,
                ]
            );

            // 6. Edificios y Aulas
            $edificioA = Edificio::firstOrCreate(['nombre' => 'Edificio A'], ['ubicacion' => 'Campus Central - Norte']);
            $edificioB = Edificio::firstOrCreate(['nombre' => 'Edificio B'], ['ubicacion' => 'Campus Central - Sur']);
            $edificioC = Edificio::firstOrCreate(['nombre' => 'Edificio C'], ['ubicacion' => 'Campus Central - Este']);

            Aula::firstOrCreate(['nombre_aula' => 'Aula 101'], ['capacidad' => 35, 'id_edificio' => $edificioA->id_edificio]);
            Aula::firstOrCreate(['nombre_aula' => 'Laboratorio C1'], ['capacidad' => 20, 'id_edificio' => $edificioA->id_edificio]);
            Aula::firstOrCreate(['nombre_aula' => 'Auditorio Principal'], ['capacidad' => 150, 'id_edificio' => $edificioB->id_edificio]);
            Aula::firstOrCreate(['nombre_aula' => 'Aula 304'], ['capacidad' => 40, 'id_edificio' => $edificioC->id_edificio]);

            // 7. Cursos
            $cursosPrueba = [
                ['nombre_curso' => 'Matemáticas I', 'descripcion' => 'Cálculo Diferencial e Integral'],
                ['nombre_curso' => 'Programación I', 'descripcion' => 'Lógica de Programación'],
                ['nombre_curso' => 'Física I', 'descripcion' => 'Mecánica Clásica'],
            ];
            foreach ($cursosPrueba as $cursoData) {
                $curso = Curso::firstOrCreate(['nombre_curso' => $cursoData['nombre_curso']], ['descripcion' => $cursoData['descripcion']]);
                $curso->carreras()->syncWithoutDetaching([$carrera->id_carrera]);
            }

            // 8. Periodo Académico
            PeriodoAcademico::firstOrCreate(
                ['nombre' => 'Semestre I 2026'],
                ['fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-06-15', 'estado' => 'inactivo']
            );
            PeriodoAcademico::firstOrCreate(
                ['nombre' => 'Semestre II 2026'],
                ['fecha_inicio' => '2026-07-15', 'fecha_fin' => '2026-12-15', 'estado' => 'activo']
            );

            // 9. Bitácora de Eventos
            $now = Carbon::now();
            Bitacora::firstOrCreate(
                ['descripcion' => 'Inicio de sesión exitoso', 'id_usuario' => $admin->id_usuario],
                ['accion' => 'LOGIN', 'tabla_afectada' => 'autenticación', 'id_registro' => null, 'fecha_hora' => $now->copy()->subMinutes(5)]
            );
            Bitacora::firstOrCreate(
                ['descripcion' => 'Creación de curso (Matemáticas I)', 'id_usuario' => $admin->id_usuario],
                ['accion' => 'CREATE', 'tabla_afectada' => 'cursos', 'id_registro' => 1, 'fecha_hora' => $now->copy()->subMinutes(30)]
            );
            Bitacora::firstOrCreate(
                ['descripcion' => 'Actualización de calificaciones', 'id_usuario' => $catUser->id_usuario],
                ['accion' => 'UPDATE', 'tabla_afectada' => 'notas', 'id_registro' => null, 'fecha_hora' => $now->copy()->subMinutes(45)]
            );
            Bitacora::firstOrCreate(
                ['descripcion' => 'Registro de edificio (Edificio A)', 'id_usuario' => $admin->id_usuario],
                ['accion' => 'CREATE', 'tabla_afectada' => 'edificio', 'id_registro' => $edificioA->id_edificio, 'fecha_hora' => $now->copy()->subHour()]
            );
            Bitacora::firstOrCreate(
                ['descripcion' => 'Creación de usuario (CAT001)', 'id_usuario' => $admin->id_usuario],
                ['accion' => 'CREATE', 'tabla_afectada' => 'usuario', 'id_registro' => $catUser->id_usuario, 'fecha_hora' => $now->copy()->subHours(2)]
            );
        });
    }
}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Avance Programático</title>
    @include('pdf.partials.styles')
    <style>
        .tareas { color: #4b5563; font-size: 9px; margin-top: 3px; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Curso:</strong> {{ $curso }} — {{ $grado }} "{{ $seccion }}" &nbsp;|&nbsp;
        <strong>Periodo:</strong> {{ $periodo }}<br>
        <strong>Catedrático:</strong> {{ $usuario }} &nbsp;|&nbsp;
        <strong>Alumnos inscritos:</strong> {{ $total_alumnos }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="7%" class="center">Semana</th>
                <th width="22%">Unidad / Tema central</th>
                <th width="36%">Temas</th>
                <th width="25%">Competencia</th>
                <th width="10%" class="center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($unidades as $u)
                <tr>
                    <td class="center">{{ $u['numero_semana'] }}</td>
                    <td>
                        <strong>{{ $u['titulo'] }}</strong>
                        @if ($u['tareas']->isNotEmpty())
                            <div class="tareas">Tareas: {{ $u['tareas']->implode('; ') }}</div>
                        @endif
                    </td>
                    <td>{{ $u['temas'] }}</td>
                    <td>{{ $u['competencia'] }}</td>
                    <td class="center">
                        @if($u['estado'] === 'completado')
                            <span class="sga-badge sga-badge-success">COMPLETADO</span>
                        @elseif($u['estado'] === 'en_progreso')
                            <span class="sga-badge sga-badge-warning">EN PROGRESO</span>
                        @else
                            <span class="sga-badge sga-badge-neutral">PLANIFICADO</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="color:#9ca3af; padding:14px;">
                        No hay unidades programadas para este curso.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.footer', ['footerTexto' => 'Documento informativo del avance programático. Generado por ' . $usuario . ' el ' . $fecha_generacion . '.'])
</body>
</html>

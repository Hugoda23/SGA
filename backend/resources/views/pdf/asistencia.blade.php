<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Control de Asistencia</title>
    @include('pdf.partials.styles')
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Curso:</strong> {{ $curso }} &nbsp;|&nbsp;
        <strong>Grado/Sección:</strong> {{ $grado }} - {{ $seccion }} &nbsp;|&nbsp;
        <strong>Fecha:</strong> {{ $fecha }}<br>
        <strong>Total alumnos:</strong> {{ count($alumnos) }} &nbsp;|&nbsp;
        <strong>Presentes:</strong> {{ $resumen['presentes'] }} &nbsp;|&nbsp;
        <strong>Ausentes:</strong> {{ $resumen['ausentes'] }} &nbsp;|&nbsp;
        <strong>Justificados:</strong> {{ $resumen['justificados'] }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="8%" class="center">No.</th>
                <th width="52%">Alumno</th>
                <th width="40%" class="center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $i => $a)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $a['nombre'] }}</td>
                <td class="center">
                    @if($a['estado'] === 'Presente')
                        <span class="sga-badge sga-badge-success">PRESENTE</span>
                    @elseif($a['estado'] === 'Ausente')
                        <span class="sga-badge sga-badge-danger">AUSENTE</span>
                    @elseif($a['estado'] === 'Justificado')
                        <span class="sga-badge sga-badge-warning">JUSTIFICADO</span>
                    @else
                        <span class="sga-badge sga-badge-neutral">SIN REGISTRO</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.footer', ['footerTexto' => 'Documento generado por ' . $usuario . ' el ' . $fecha_generacion . '.'])
</body>
</html>

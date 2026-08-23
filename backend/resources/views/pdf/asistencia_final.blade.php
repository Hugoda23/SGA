<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lista Final de Asistencia</title>
    @include('pdf.partials.styles')
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Curso:</strong> {{ $curso }} &nbsp;|&nbsp;
        <strong>Grado/Sección:</strong> {{ $grado }} - {{ $seccion }} &nbsp;|&nbsp;
        <strong>Total alumnos:</strong> {{ count($alumnos) }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="5%" class="center">No.</th>
                <th width="27%">Alumno</th>
                <th width="9%" class="center">Sesiones</th>
                <th width="9%" class="center">Presentes</th>
                <th width="9%" class="center">Ausentes</th>
                <th width="10%" class="center">Justificados</th>
                <th width="11%" class="center">% Asistencia</th>
                <th width="20%" class="center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $i => $a)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $a['nombre'] }}</td>
                <td class="center">{{ $a['sesiones'] }}</td>
                <td class="center">{{ $a['presentes'] }}</td>
                <td class="center">{{ $a['ausentes'] }}</td>
                <td class="center">{{ $a['justificados'] }}</td>
                <td class="center">{{ $a['pct'] !== null ? $a['pct'] . '%' : '—' }}</td>
                <td class="center">
                    @if($a['estado'] === 'Aprueba')
                        <span class="sga-badge sga-badge-success">APRUEBA</span>
                    @elseif($a['estado'] === 'En riesgo')
                        <span class="sga-badge sga-badge-warning">EN RIESGO</span>
                    @elseif($a['estado'] === 'Reprueba')
                        <span class="sga-badge sga-badge-danger">REPRUEBA</span>
                    @else
                        <span class="sga-badge sga-badge-neutral">SIN REGISTROS</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">Totales</td>
                <td class="center">—</td>
                <td class="center">{{ $totales['presentes'] }}</td>
                <td class="center">{{ $totales['ausentes'] }}</td>
                <td class="center">{{ $totales['justificados'] }}</td>
                <td colspan="2" class="center">
                    Aprueban: {{ $totales['aprueba'] }} · En riesgo: {{ $totales['en_riesgo'] }} · Reprueban: {{ $totales['reprueba'] }}
                </td>
            </tr>
        </tfoot>
    </table>

    @include('pdf.partials.footer', ['footerTexto' => 'Documento generado por ' . $usuario . ' el ' . $fecha_generacion . '.'])
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lista Final de Asistencia</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; color: #1a365d; }
        .header p { margin: 4px 0; color: #4a5568; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1a365d; color: white; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; }
        th.center, td.center { text-align: center; }
        td { border: 1px solid #cbd5e0; padding: 6px; }
        tr:nth-child(even) { background-color: #f7fafc; }
        .aprueba { color: #276749; font-weight: bold; }
        .en-riesgo { color: #b7791f; font-weight: bold; }
        .reprueba { color: #c53030; font-weight: bold; }
        .sin-registro { color: #a0aec0; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 9px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista Final de Asistencia</h1>
        <p>{{ $institucion ?? 'SGA' }} | Curso: {{ $curso }}</p>
        <p>{{ $grado }} - {{ $seccion }} | Total Alumnos: {{ count($alumnos) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="center">No.</th>
                <th width="30%">Alumno</th>
                <th width="9%" class="center">Sesiones</th>
                <th width="9%" class="center">Presentes</th>
                <th width="9%" class="center">Ausentes</th>
                <th width="9%" class="center">Justificados</th>
                <th width="11%" class="center">% Asistencia</th>
                <th width="18%" class="center">Estado</th>
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
                <td class="center {{ $a['clase'] }}">{{ $a['estado'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; font-weight: bold;">Totales</td>
                <td class="center" style="font-weight: bold;">—</td>
                <td class="center" style="font-weight: bold;">{{ $totales['presentes'] }}</td>
                <td class="center" style="font-weight: bold;">{{ $totales['ausentes'] }}</td>
                <td class="center" style="font-weight: bold;">{{ $totales['justificados'] }}</td>
                <td colspan="2" class="center">
                    Aprueban: <strong>{{ $totales['aprueba'] }}</strong> |
                    En riesgo: <strong>{{ $totales['en_riesgo'] }}</strong> |
                    Reprueban: <strong>{{ $totales['reprueba'] }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado por {{ $usuario }} el {{ $fecha_generacion }}
    </div>
</body>
</html>

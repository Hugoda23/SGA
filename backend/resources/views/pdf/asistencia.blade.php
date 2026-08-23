<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Control de Asistencia</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; color: #1a365d; }
        .header p { margin: 4px 0; color: #4a5568; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1a365d; color: white; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { border: 1px solid #cbd5e0; padding: 6px; }
        tr:nth-child(even) { background-color: #f7fafc; }
        .presente { color: #276749; font-weight: bold; }
        .ausente { color: #c53030; font-weight: bold; }
        .justificado { color: #b7791f; font-weight: bold; }
        .sin-registro { color: #a0aec0; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 9px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-presente { background: #c6f6d5; color: #22543d; }
        .badge-ausente { background: #fed7d7; color: #742a2a; }
        .badge-justificado { background: #fefcbf; color: #744210; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Control de Asistencia</h1>
        <p>{{ $institucion ?? 'SGA' }} | Curso: {{ $curso }}</p>
        <p>Fecha: {{ $fecha }} | {{ $grado }} - {{ $seccion }}</p>
        <p>Total Alumnos: {{ count($alumnos) }} | Presentes: {{ $resumen['presentes'] }} | Ausentes: {{ $resumen['ausentes'] }} | Justificados: {{ $resumen['justificados'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">No.</th>
                <th width="52%">Alumno</th>
                <th width="40%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $i => $a)
            <tr>
                <td style="text-align: center;">{{ $i + 1 }}</td>
                <td>{{ $a['nombre'] }}</td>
                <td>
                    @if($a['estado'] === 'Presente')
                        <span class="badge badge-presente">PRESENTE</span>
                    @elseif($a['estado'] === 'Ausente')
                        <span class="badge badge-ausente">AUSENTE</span>
                    @elseif($a['estado'] === 'Justificado')
                        <span class="badge badge-justificado">JUSTIFICADO</span>
                    @else
                        <span class="sin-registro">SIN REGISTRO</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento generado por {{ $usuario }} el {{ $fecha_generacion }}
    </div>
</body>
</html>

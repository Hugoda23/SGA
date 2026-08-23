<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Historial Académico (Kárdex)</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; padding: 20px; font-size: 14px; position: relative; }
        .watermark { position: absolute; top: 30%; left: 15%; font-size: 80px; color: rgba(0,0,0,0.05); transform: rotate(-45deg); z-index: -1; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .summary-panel { background: #f9f9f9; padding: 15px; border: 1px solid #ccc; margin-bottom: 30px; display: table; width: 100%; box-sizing: border-box; }
        .summary-panel div { display: table-cell; width: 33%; }
        .period-header { background-color: #555; color: #fff; padding: 5px 10px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
        table.history { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.history th, table.history td { border: 1px solid #ccc; padding: 6px; }
        table.history th { background-color: #eaeaea; }
        .footer { position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 11px; color: #777; }
        .hash { position: absolute; top: 20px; right: 20px; font-size: 10px; color: #999; word-break: break-all; width: 150px; text-align: right; }
    </style>
</head>
<body>
    <div class="watermark">OFICIAL - SGA</div>
    <div class="hash">Hash Valididador:<br>{{ $hash }}</div>
    
    <div class="header">
        <h1>Sistema de Gestión Académica</h1>
        <p>Certificado de Historial Académico (Kárdex)</p>
        <p>Fecha de Emisión: {{ $fecha }}</p>
    </div>

    <div class="summary-panel">
        <div><strong>Alumno:</strong> {{ $alumno->nombre }} {{ $alumno->apellido }}</div>
        <div><strong>Carnet:</strong> {{ $alumno->id_alumno }}</div>
        <div><strong>Promedio Global:</strong> {{ $promedio_global }} pts</div>
        <div><strong>Créditos aprobados:</strong> {{ $creditos_totales }}</div>
    </div>

    @foreach($historial as $periodo => $cursos)
        <div class="period-header">Ciclo Escolar {{ $periodo }}</div>
        <table class="history">
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th>Calificación</th>
                    <th>Créditos</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cursos as $curso)
                <tr>
                    <td>{{ $curso['nombre'] }}</td>
                    <td style="text-align:center;">{{ $curso['nota'] }}</td>
                    <td style="text-align:center;">{{ $curso['creditos'] }}</td>
                    <td style="text-align:center;">{{ $curso['resultado'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        Este documento carece de validez sin el sello oficial en original. Generado por el Sistema de Gestión Académica.
    </div>
</body>
</html>

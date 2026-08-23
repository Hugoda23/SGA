<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bitácora de Auditoría</title>
    <style>
        @page { margin: 15px; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 10px; position: relative; }
        .watermark { position: absolute; top: 40%; left: 10%; font-size: 60px; color: rgba(255,0,0,0.1); transform: rotate(-30deg); z-index: -1; pointer-events: none; white-space: nowrap; }
        .header { text-align: left; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; }
        table.logs { width: 100%; border-collapse: collapse; }
        table.logs th, table.logs td { border: 1px solid #aaa; padding: 4px; word-wrap: break-word; }
        table.logs th { background-color: #eee; text-align: left; }
        .action-INSERT { background-color: #d4edda; color: #155724; }
        .action-UPDATE { background-color: #fff3cd; color: #856404; }
        .action-DELETE { background-color: #f8d7da; color: #721c24; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 9px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="watermark">CONFIDENCIAL - {{ strtoupper($usuario_generador) }}</div>
    
    <div class="header">
        <h1>Reporte de Bitácora de Auditoría del Sistema</h1>
        <p>Usuario Generador: {{ $usuario_generador }} | Fecha Impresión: {{ $fecha }}</p>
        <p>Total de Registros: {{ count($logs) }} (Últimos 30 días)</p>
    </div>

    <table class="logs">
        <thead>
            <tr>
                <th width="15%">Fecha/Hora</th>
                <th width="15%">Usuario Responsable</th>
                <th width="10%">Acción</th>
                <th width="15%">Módulo</th>
                <th width="45%">Descripción/Detalle</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->fecha_hora }}</td>
                <td>{{ $log->usuario->username ?? 'Sistema' }}</td>
                <td class="action-{{ $log->accion }}"><strong>{{ strtoupper($log->accion) }}</strong></td>
                <td>{{ $log->tabla_afectada ?? 'N/A' }}</td>
                <td>{{ $log->descripcion ?? 'Afectado registro ID: ' . $log->id_registro }}</td>
            </tr>
            @endforeach
            @if(count($logs) == 0)
            <tr>
                <td colspan="6" style="text-align:center;">No hay registros en el rango seleccionado.</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Información Confidencial - Uso Exclusivo de Auditoría Interna.
    </div>
</body>
</html>

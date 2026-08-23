<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bitácora de Auditoría</title>
    @include('pdf.partials.styles')
    <style>
        table.sga-table td { word-wrap: break-word; }
        .accion-CREAR, .accion-INSERT { background-color: #d1fae5 !important; color: #065f46; }
        .accion-ACTUALIZAR, .accion-UPDATE { background-color: #fef3c7 !important; color: #92400e; }
        .accion-ELIMINAR, .accion-DELETE { background-color: #fee2e2 !important; color: #991b1b; }
    </style>
</head>
<body>
    <div class="sga-watermark">CONFIDENCIAL · {{ strtoupper($usuario_generador) }}</div>

    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Usuario generador:</strong> {{ $usuario_generador }} &nbsp;|&nbsp;
        <strong>Total de registros:</strong> {{ count($logs) }} (últimos 30 días)
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="15%">Fecha/Hora</th>
                <th width="15%">Usuario Responsable</th>
                <th width="10%" class="center">Acción</th>
                <th width="15%">Módulo</th>
                <th width="45%">Descripción/Detalle</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->fecha_hora }}</td>
                <td>{{ $log->usuario->username ?? 'Sistema' }}</td>
                <td class="center accion-{{ $log->accion }}"><strong>{{ strtoupper($log->accion) }}</strong></td>
                <td>{{ $log->tabla_afectada ?? 'N/A' }}</td>
                <td>{{ $log->descripcion ?? 'Afectado registro ID: ' . $log->id_registro }}</td>
            </tr>
            @endforeach
            @if(count($logs) == 0)
            <tr>
                <td colspan="5" class="center">No hay registros en el rango seleccionado.</td>
            </tr>
            @endif
        </tbody>
    </table>

    @include('pdf.partials.footer', ['footerTexto' => 'Información confidencial — uso exclusivo de auditoría interna.'])
</body>
</html>

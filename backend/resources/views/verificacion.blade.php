<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Documento</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 560px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .banner { padding: 28px; text-align: center; color: #fff; }
        .banner.ok { background: #059669; }
        .banner.bad { background: #dc2626; }
        .banner h1 { margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .banner p { margin: 8px 0 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 28px; }
        table.info { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.info td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        table.info td:first-child { color: #6b7280; font-weight: 600; width: 40%; }
        .footer { padding: 16px 28px; text-align: center; font-size: 12px; color: #9ca3af; background: #f9fafb; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        @if ($valido)
            <div class="banner ok">
                <h1>Documento Válido</h1>
                <p>Generado por el Sistema de Gestión Académica (SGA)</p>
            </div>
            <div class="body">
                <table class="info">
                    <tr><td>Institución</td><td>{{ $institucion }}</td></tr>
                    <tr><td>Tipo de documento</td><td>{{ strtoupper($tipo_reporte) }}</td></tr>
                    @if ($alumno)
                        <tr><td>Estudiante</td><td>{{ $alumno->nombre }} {{ $alumno->apellido }}</td></tr>
                        @if ($alumno->carrera)
                            <tr><td>Carrera</td><td>{{ $alumno->carrera->nombre_carrera }}</td></tr>
                        @endif
                    @endif
                    <tr><td>Generado por</td><td>{{ $usuario?->username ?? '—' }}</td></tr>
                    <tr><td>Fecha de generación</td><td>{{ \Carbon\Carbon::parse($fecha_generacion)->format('d/m/Y H:i') }}</td></tr>
                </table>
            </div>
        @else
            <div class="banner bad">
                <h1>Documento No Válido</h1>
                <p>No se pudo verificar este documento</p>
            </div>
            <div class="body">
                <p style="text-align:center;color:#6b7280;font-size:15px;">{{ $motivo ?? 'El código proporcionado no corresponde a un documento emitido por el sistema.' }}</p>
            </div>
        @endif
        <div class="footer">
            Esta verificación fue realizada por el Sistema de Gestión Académica (SGA).
        </div>
    </div>
</body>
</html>

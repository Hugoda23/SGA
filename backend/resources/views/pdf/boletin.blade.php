<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boletín de Calificaciones - {{ $alumno->nombre }}</title>
    @include('pdf.partials.styles')
    <style>
        .sga-summary { text-align: right; font-weight: bold; font-size: 14px; color: #204075; margin-bottom: 24px; }
        .sga-signatures { width: 100%; margin-top: 60px; }
        .sga-signatures td { text-align: center; padding-top: 40px; border-top: 1px solid #9ca3af; font-size: 10px; color: #4b5563; }
        .qr-code { position: absolute; bottom: 40px; left: 28px; width: 85px; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Carnet:</strong> {{ $alumno->id_alumno }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Nombre:</strong> {{ $alumno->nombre }} {{ $alumno->apellido }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Asignatura</th>
                <th class="center">Grado / Sección</th>
                <th class="center">Periodo</th>
                <th class="center">Nota Final</th>
                <th class="center">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
                <td>{{ $nota['codigo'] }}</td>
                <td>{{ $nota['nombre'] }}</td>
                <td class="center">{{ $nota['grado_seccion'] ?: '—' }}</td>
                <td class="center">{{ $nota['periodo'] }}</td>
                <td class="center"><strong>{{ $nota['final'] ?? '—' }}</strong></td>
                <td class="center">
                    @if($nota['resultado'] === 'Aprobado')
                        <span class="sga-badge sga-badge-success">APROBADO</span>
                    @elseif($nota['resultado'] === 'Reprobado')
                        <span class="sga-badge sga-badge-danger">REPROBADO</span>
                    @else
                        <span class="sga-badge sga-badge-neutral">SIN NOTA</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sga-summary">Promedio General: {{ $promedio ?? '—' }}</div>

    <table class="sga-signatures">
        <tr>
            <td width="50%">Firma Docente Guía</td>
            <td width="50%">Sello y Firma Dirección</td>
        </tr>
    </table>

    @if($qrCode)
        <div class="qr-code">
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" width="85" height="85">
        </div>
    @endif

    @include('pdf.partials.footer', ['footerTexto' => 'Este documento incluye un código QR de verificación. Generado por el Sistema de Gestión Académica (SGA).'])
</body>
</html>

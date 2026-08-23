<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boletín Oficial de Calificaciones</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; padding: 20px; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #555; }
        .info-grid { width: 100%; margin-bottom: 20px; }
        .info-grid td { padding: 5px; }
        table.grades { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.grades th, table.grades td { border: 1px solid #000; padding: 8px; text-align: center; }
        table.grades th { background-color: #f0f0f0; }
        table.grades td.text-left { text-align: left; }
        .summary { text-align: right; font-weight: bold; font-size: 16px; }
        .footer { width: 100%; margin-top: 50px; text-align: center; }
        .signature-line { width: 200px; border-top: 1px solid #000; margin: 0 auto; padding-top: 5px; }
        .qr-code { position: absolute; bottom: 30px; left: 30px; width: 100px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Gestión Académica</h1>
        <p>Boletín Oficial de Calificaciones</p>
        <p>Fecha de Emisión: {{ $fecha }}</p>
    </div>

    <table class="info-grid">
        <tr>
            <td><strong>Carnet:</strong> {{ $alumno->id_alumno }}</td>
            <td><strong>Nombre:</strong> {{ $alumno->nombre }} {{ $alumno->apellido }}</td>
        </tr>
    </table>

    <table class="grades">
        <thead>
            <tr>
                <th>Código</th>
                <th>Asignatura</th>
                <th>Grado / Sección</th>
                <th>Periodo</th>
                <th>Nota Final</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
                <td>{{ $nota['codigo'] }}</td>
                <td class="text-left">{{ $nota['nombre'] }}</td>
                <td>{{ $nota['grado_seccion'] ?: '—' }}</td>
                <td>{{ $nota['periodo'] }}</td>
                <td><strong>{{ $nota['final'] ?? '—' }}</strong></td>
                <td>{{ $nota['resultado'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        Promedio General: {{ $promedio ?? '—' }}
    </div>

    <table class="footer">
        <tr>
            <td>
                <div class="signature-line">Firma Docente Guía</div>
            </td>
            <td>
                <div class="signature-line">Sello y Firma Dirección</div>
            </td>
        </tr>
    </table>

    <div class="qr-code">
        <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" width="100" height="100">
    </div>
</body>
</html>

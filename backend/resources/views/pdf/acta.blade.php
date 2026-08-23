<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta Oficial de Calificaciones</title>
    <style>
        @page { margin: 20px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header .logo, .header .title, .header .info { display: table-cell; vertical-align: middle; }
        .header .title { text-align: center; }
        .header .title h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header .info { text-align: right; font-size: 10px; }
        .course-info { background: #eee; padding: 10px; margin-bottom: 20px; font-weight: bold; border: 1px solid #ccc; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 1px solid #000; padding: 5px; text-align: center; }
        table.data th { background-color: #ddd; font-weight: bold; }
        table.data td.text-left { text-align: left; }
        .bottom-section { display: table; width: 100%; margin-top: 40px; }
        .stats, .signatures { display: table-cell; vertical-align: top; width: 50%; }
        .stats table { border-collapse: collapse; width: 200px; }
        .stats table th, .stats table td { border: 1px solid #000; padding: 4px; font-size: 10px; }
        .signature-box { text-align: center; width: 200px; float: right; margin-right: 50px; }
        .signature-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">SGA Logo</div>
        <div class="title">
            <h1>ACTA OFICIAL DE CALIFICACIONES</h1>
        </div>
        <div class="info">
            Acta No. ACT-ASG-{{ $asignacion->id_asignacion }}<br>
            Impreso: {{ $fecha }}
        </div>
    </div>

    <div class="course-info">
        Curso: {{ $asignacion->curso->nombre_curso ?? '—' }} | Código: CURSO-{{ $asignacion->id_curso }} | Grado: {{ $asignacion->grado->nombre ?? '—' }} | Sección: {{ $asignacion->seccion->nombre ?? '—' }} | Periodo: {{ $asignacion->periodo->nombre ?? '—' }} | Catedrático: {{ trim(($asignacion->catedratico->nombre ?? '') . ' ' . ($asignacion->catedratico->apellido ?? '')) ?: '—' }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No.</th>
                <th width="80">Carnet</th>
                <th class="text-left">Nombres y Apellidos</th>
                <th width="50">Zona</th>
                <th width="70">Examen Final</th>
                <th width="60">Nota Total</th>
                <th class="text-left">En Letras</th>
                <th width="80">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            <tr>
                <td>{{ $alumno['no'] }}</td>
                <td>{{ $alumno['carnet'] }}</td>
                <td class="text-left">{{ $alumno['nombre'] }}</td>
                <td>{{ $alumno['zona'] }}</td>
                <td>{{ $alumno['examen'] }}</td>
                <td><strong>{{ $alumno['total'] }}</strong></td>
                <td class="text-left">{{ $alumno['letras'] }}</td>
                <td>{{ $alumno['resultado'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="stats">
            <table>
                <tr><th colspan="2">Estadísticas</th></tr>
                <tr><td>Asignados</td><td>{{ $stats['asignados'] }}</td></tr>
                <tr><td>Aprobados</td><td>{{ $stats['aprobados'] }}</td></tr>
                <tr><td>Reprobados</td><td>{{ $stats['reprobados'] }}</td></tr>
            </table>
        </div>
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">Firma Catedrático Titular</div>
            </div>
        </div>
    </div>
</body>
</html>

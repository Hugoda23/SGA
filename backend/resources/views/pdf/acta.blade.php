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
        table.data td.text-left, table.data th.text-left { text-align: left; }
        table.data th .th-sub { font-weight: normal; font-size: 9px; }
        .nota-sin-zonas { font-size: 10px; color: #555; margin-top: -14px; margin-bottom: 18px; font-style: italic; }
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
                <th width="25">No.</th>
                <th width="65">Carnet</th>
                <th class="text-left">Nombres y Apellidos</th>
                @if($usaZonas)
                    @foreach($zonas as $zona)
                        <th width="60">{{ $zona->nombre }}<br><span class="th-sub">({{ number_format($zona->puntos, 0) }} pts)</span></th>
                    @endforeach
                @endif
                <th width="55">Nota Final</th>
                <th class="text-left" width="140">En Letras</th>
                <th width="75">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            <tr>
                <td>{{ $alumno['no'] }}</td>
                <td>{{ $alumno['carnet'] }}</td>
                <td class="text-left">{{ $alumno['nombre'] }}</td>
                @if($usaZonas)
                    @foreach($zonas as $zona)
                        <td>{{ $alumno['total'] === null ? '—' : number_format($alumno['por_zona'][$zona->id_zona] ?? 0, 2) }}</td>
                    @endforeach
                @endif
                <td><strong>{{ $alumno['total'] === null ? '—' : number_format($alumno['total'], 2) }}</strong></td>
                <td class="text-left">{{ $alumno['letras'] }}</td>
                <td>{{ $alumno['resultado'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!$usaZonas)
        <p class="nota-sin-zonas">* Este curso no tiene zonas de evaluación definidas — la nota final se calcula por promedio ponderado directo de las actividades registradas.</p>
    @endif

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

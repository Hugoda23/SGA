<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta Oficial de Calificaciones</title>
    @include('pdf.partials.styles')
    <style>
        table.sga-table th .th-sub { font-weight: normal; font-size: 8px; text-transform: none; }
        .nota-sin-zonas { font-size: 10px; color: #6b7280; margin-top: -10px; margin-bottom: 18px; font-style: italic; }
        .sga-bottom { display: table; width: 100%; margin-top: 30px; }
        .sga-stats, .sga-signatures { display: table-cell; vertical-align: top; width: 50%; }
        .sga-stats table { border-collapse: collapse; width: 220px; }
        .sga-stats th, .sga-stats td { border: 1px solid #dbe4f3; padding: 5px 8px; font-size: 10px; }
        .sga-stats th { background: #3b71ca; color: #fff; text-transform: uppercase; }
        .signature-box { text-align: center; width: 220px; float: right; margin-right: 40px; }
        .signature-line { border-top: 1px solid #4b5563; margin-top: 50px; padding-top: 5px; font-size: 10px; color: #4b5563; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Curso:</strong> {{ $asignacion->curso->nombre_curso ?? '—' }} &nbsp;|&nbsp;
        <strong>Código:</strong> CURSO-{{ $asignacion->id_curso }} &nbsp;|&nbsp;
        <strong>Grado:</strong> {{ $asignacion->grado->nombre ?? '—' }} &nbsp;|&nbsp;
        <strong>Sección:</strong> {{ $asignacion->seccion->nombre ?? '—' }} &nbsp;|&nbsp;
        <strong>Periodo:</strong> {{ $asignacion->periodo->nombre ?? '—' }} &nbsp;|&nbsp;
        <strong>Catedrático:</strong> {{ trim(($asignacion->catedratico->nombre ?? '') . ' ' . ($asignacion->catedratico->apellido ?? '')) ?: '—' }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="25" class="center">No.</th>
                <th width="65">Carnet</th>
                <th>Nombres y Apellidos</th>
                @if($usaZonas)
                    @foreach($zonas as $zona)
                        <th width="60" class="center">{{ $zona->nombre }}<br><span class="th-sub">({{ number_format($zona->puntos, 0) }} pts)</span></th>
                    @endforeach
                @endif
                <th width="55" class="center">Nota Final</th>
                <th width="140">En Letras</th>
                <th width="75" class="center">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            <tr>
                <td class="center">{{ $alumno['no'] }}</td>
                <td class="center">{{ $alumno['carnet'] }}</td>
                <td>{{ $alumno['nombre'] }}</td>
                @if($usaZonas)
                    @foreach($zonas as $zona)
                        <td class="center">{{ $alumno['total'] === null ? '—' : number_format($alumno['por_zona'][$zona->id_zona] ?? 0, 2) }}</td>
                    @endforeach
                @endif
                <td class="center"><strong>{{ $alumno['total'] === null ? '—' : number_format($alumno['total'], 2) }}</strong></td>
                <td>{{ $alumno['letras'] }}</td>
                <td class="center">
                    @if($alumno['resultado'] === 'Aprobado')
                        <span class="sga-badge sga-badge-success">APROBADO</span>
                    @elseif($alumno['resultado'] === 'Reprobado')
                        <span class="sga-badge sga-badge-danger">REPROBADO</span>
                    @else
                        <span class="sga-badge sga-badge-neutral">SIN NOTA</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!$usaZonas)
        <p class="nota-sin-zonas">* Este curso no tiene zonas de evaluación definidas — la nota final se calcula por promedio ponderado directo de las actividades registradas.</p>
    @endif

    <div class="sga-bottom">
        <div class="sga-stats">
            <table>
                <tr><th colspan="2">Estadísticas</th></tr>
                <tr><td>Asignados</td><td class="center">{{ $stats['asignados'] }}</td></tr>
                <tr><td>Aprobados</td><td class="center">{{ $stats['aprobados'] }}</td></tr>
                <tr><td>Reprobados</td><td class="center">{{ $stats['reprobados'] }}</td></tr>
            </table>
        </div>
        <div class="sga-signatures">
            <div class="signature-box">
                <div class="signature-line">Firma Catedrático Titular</div>
            </div>
        </div>
    </div>

    @include('pdf.partials.footer')
</body>
</html>

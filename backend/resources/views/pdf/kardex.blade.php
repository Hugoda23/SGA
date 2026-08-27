<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kárdex - {{ $alumno->nombre }}</title>
    @include('pdf.partials.styles')
    <style>
        .hash { position: absolute; top: 26px; right: 28px; font-size: 8px; color: #9ca3af; word-break: break-all; width: 150px; text-align: right; }
        .sga-summary-panel { display: table; width: 100%; background: #f1f5fb; border: 1px solid #c7d7f0; padding: 10px 12px; margin-bottom: 20px; font-size: 10px; }
        .sga-summary-panel div { display: table-cell; color: #204075; }
        .sga-summary-panel strong { color: #183058; }
        .period-header { background: #3b71ca; color: #fff; padding: 5px 10px; font-weight: bold; margin-top: 18px; margin-bottom: 8px; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="sga-watermark">OFICIAL · SGA</div>
    <div class="hash">Hash validador:<br>{{ $hash }}</div>

    @include('pdf.partials.header')

    <div class="sga-summary-panel">
        <div><strong>Alumno:</strong> {{ $alumno->nombre }} {{ $alumno->apellido }}</div>
        <div><strong>Carnet:</strong> {{ $alumno->id_alumno }}</div>
        <div><strong>Promedio Global:</strong> {{ $promedio_global }} pts</div>
    </div>

    @foreach($historial as $periodo => $cursos)
        <div class="period-header">Ciclo Escolar {{ $periodo }}</div>
        <table class="sga-table">
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th class="center">Calificación</th>
                    <th class="center">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cursos as $curso)
                <tr>
                    <td>{{ $curso['nombre'] }}</td>
                    <td class="center">{{ $curso['nota'] ?? '—' }}</td>
                    <td class="center">
                        @if($curso['resultado'] === 'Aprobado')
                            <span class="sga-badge sga-badge-success">APROBADO</span>
                        @elseif($curso['resultado'] === 'Reprobado')
                            <span class="sga-badge sga-badge-danger">REPROBADO</span>
                        @else
                            <span class="sga-badge sga-badge-neutral">SIN NOTA</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @include('pdf.partials.footer', ['footerTexto' => 'Este documento carece de validez sin el sello oficial en original. Generado por el Sistema de Gestión Académica (SGA).'])
</body>
</html>

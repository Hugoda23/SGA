<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Avance Programático</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .institucion { font-size: 17px; font-weight: bold; color: #4f46e5; }
        .titulo-doc { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { padding: 3px 6px; }
        .meta .label { font-weight: bold; color: #4b5563; width: 120px; }
        table.tabla { width: 100%; border-collapse: collapse; }
        table.tabla th, table.tabla td { border: 1px solid #d1d5db; padding: 5px 7px; vertical-align: top; text-align: left; }
        table.tabla th { background: #eef2ff; color: #312e81; font-size: 10px; text-transform: uppercase; }
        .estado { font-weight: bold; padding: 1px 8px; border-radius: 8px; font-size: 9px; }
        .planificado { background: #e0f2fe; color: #075985; }
        .en_progreso { background: #fef3c7; color: #92400e; }
        .completado { background: #dcfce7; color: #166534; }
        .tareas { color: #374151; font-size: 10px; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="institucion">{{ $institucion }}</div>
        <div class="titulo-doc">AVANCE PROGRAMÁTICO</div>
        <div>{{ $curso }} — {{ $grado }} {{ $seccion }} ({{ $periodo }})</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Curso:</td><td>{{ $curso }} — {{ $grado }} "{{ $seccion }}"</td>
            <td class="label">Período:</td><td>{{ $periodo }}</td>
        </tr>
        <tr>
            <td class="label">Catedrático:</td><td>{{ $usuario }}</td>
            <td class="label">Alumnos inscritos:</td><td>{{ $total_alumnos }}</td>
        </tr>
    </table>

    <table class="tabla">
        <thead>
            <tr>
                <th style="width:7%">Semana</th>
                <th style="width:22%">Unidad / Tema central</th>
                <th style="width:36%">Temas</th>
                <th style="width:25%">Competencia</th>
                <th style="width:10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($unidades as $u)
                <tr>
                    <td style="text-align:center">{{ $u['numero_semana'] }}</td>
                    <td>
                        <strong>{{ $u['titulo'] }}</strong>
                        @if ($u['tareas']->isNotEmpty())
                            <div class="tareas">Tareas: {{ $u['tareas']->implode('; ') }}</div>
                        @endif
                    </td>
                    <td>{{ $u['temas'] }}</td>
                    <td>{{ $u['competencia'] }}</td>
                    <td>
                        <span class="estado {{ $u['estado'] }}">{{ str_replace('_', ' ', ucfirst($u['estado'])) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af; padding:14px;">
                        No hay unidades programadas para este curso.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado por SGA el {{ $fecha_generacion }} · Documento informativo del avance programático.
    </div>
</body>
</html>

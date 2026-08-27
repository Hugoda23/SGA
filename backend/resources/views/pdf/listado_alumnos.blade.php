<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Listado de Alumnos</title>
    @include('pdf.partials.styles')
</head>
<body>
    @include('pdf.partials.header')

    <div class="sga-infobar">
        <strong>Curso:</strong> {{ $curso }} &nbsp;|&nbsp;
        <strong>Grado/Sección:</strong> {{ $grado }} - {{ $seccion }} &nbsp;|&nbsp;
        <strong>Periodo:</strong> {{ $periodo }}<br>
        <strong>Catedrático:</strong> {{ $catedratico }} &nbsp;|&nbsp;
        <strong>Total alumnos:</strong> {{ count($alumnos) }}
    </div>

    <table class="sga-table">
        <thead>
            <tr>
                <th width="8%" class="center">No.</th>
                <th width="42%">Nombre completo</th>
                <th width="18%" class="center">Código</th>
                <th width="18%" class="center">Clave</th>
                <th width="14%" class="center">Firma</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumnos as $i => $a)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $a['nombre'] }}</td>
                <td class="center">{{ $a['codigo'] }}</td>
                <td class="center">{{ $a['clave'] }}</td>
                <td></td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="center">No hay alumnos inscritos en esta asignación.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.footer', ['footerTexto' => 'Documento generado por ' . $usuario . ' el ' . $fecha_generacion . '.'])
</body>
</html>

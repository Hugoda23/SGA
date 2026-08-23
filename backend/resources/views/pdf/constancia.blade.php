<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Constancia de Inscripción - {{ $alumno->nombre }}</title>
    @include('pdf.partials.styles')
    <style>
        body { padding: 10px 20px; }
        .titulo-constancia { text-align: center; font-size: 17px; font-weight: bold; margin: 36px 0; text-transform: uppercase; color: #204075; border-bottom: 2px solid #3b71ca; border-top: 2px solid #3b71ca; padding: 10px 0; }
        .contenido { font-size: 13px; text-align: justify; line-height: 1.8; color: #1f2937; }
        .fecha-emision { text-align: right; margin-top: 50px; font-style: italic; font-size: 11px; color: #4b5563; }
        .sga-signatures { width: 100%; margin-top: 70px; text-align: center; }
        .signature { display: inline-block; width: 260px; border-top: 1px solid #4b5563; padding-top: 8px; font-weight: bold; font-size: 11px; color: #374151; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <div class="titulo-constancia">Constancia de Inscripción</div>

    <div class="contenido">
        <p>
            El infrascrito Director de Registro Académico de la institución, por este medio <strong>HACE CONSTAR</strong> que:
        </p>
        <p>
            El/la alumno(a) <strong>{{ mb_strtoupper($alumno->nombre . ' ' . $alumno->apellido) }}</strong>,
            identificado(a) con el carnet estudiantil número <strong>{{ $alumno->codigo_mineduc ?? $alumno->id_alumno }}</strong>,
            se encuentra formal y debidamente inscrito(a) en los registros oficiales de esta institución educativa para el presente ciclo académico.
        </p>
        <p>
            Y, para los usos legales que al interesado(a) convengan, se extiende, firma y sella la presente constancia.
        </p>
    </div>

    <div class="fecha-emision">Emitido el: {{ $fecha }}</div>

    <div class="sga-signatures">
        <div class="signature">Firma del Director</div>
        <p style="font-size: 10px; margin-top: 8px; color: #6b7280;">Sello Oficial</p>
    </div>

    @include('pdf.partials.footer')
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Constancia Académica - {{ $alumno->nombre }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; padding: 40px 60px; color: #111; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 50px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0; font-style: italic; font-size: 14px; }
        .title { text-align: center; font-size: 20px; font-weight: bold; margin: 40px 0; text-transform: uppercase; text-decoration: underline; }
        .content { font-size: 16px; text-align: justify; }
        .footer { margin-top: 100px; text-align: center; }
        .signature { margin-top: 50px; width: 300px; border-top: 1px solid #111; margin-left: auto; margin-right: auto; padding-top: 10px; font-weight: bold; }
        .date { text-align: right; margin-top: 60px; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Gestión Académica</h1>
        <p>Dirección de Registro y Control Académico</p>
    </div>
    
    <div class="title">
        Constancia de Inscripción
    </div>
    
    <div class="content">
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
    
    <div class="date">
        Emitido el: {{ $fecha }}
    </div>
    
    <div class="footer">
        <div class="signature">
            Firma del Director
        </div>
        <p style="font-size: 12px; margin-top: 10px;">Sello Oficial</p>
    </div>
</body>
</html>

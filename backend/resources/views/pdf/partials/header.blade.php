{{--
    Encabezado institucional compartido.
    Variables esperadas: $institucion, $tituloDoc, $fecha, y opcionalmente $docNumero.
--}}
<div class="sga-header">
    <div class="brand">
        <div class="sistema">Sistema de Gestión Académica</div>
        <div class="institucion">{{ $institucion ?? 'SGA' }}</div>
    </div>
    <div class="doc">
        <div class="titulo-doc">{{ $tituloDoc }}</div>
        <div class="meta">
            @isset($docNumero)
                {{ $docNumero }}<br>
            @endisset
            Generado: {{ $fecha_generacion ?? $fecha ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>
</div>

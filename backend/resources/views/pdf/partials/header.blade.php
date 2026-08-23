{{--
    Encabezado institucional compartido.
    Variables esperadas: $institucion, $tituloDoc, $fecha, y opcionalmente $docNumero, $logoBase64.
--}}
<div class="sga-header">
    <div class="brand">
        @isset($logoBase64)
            <div class="brand-logo">
                <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Logo institucional" class="sga-logo">
            </div>
        @endisset
        <div class="brand-text">
            <div class="sistema">Sistema de Gestión Académica</div>
            <div class="institucion">{{ $institucion ?? 'SGA' }}</div>
        </div>
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

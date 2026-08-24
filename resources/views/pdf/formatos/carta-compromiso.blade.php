{{-- F-PR-OCC-11 R00 — Carta Compromiso del Acreditado --}}
@php
    $codigo = 'F-PR-OCC-11 R00';
    $titulo = 'Carta Compromiso';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $val    = fn($v) => $v ?? '';
    $n      = fn($v) => number_format((float)($v ?? 0), 2);
    $monto  = (float) ($solicitud->monto_solicitado ?? 0);
    $esSustentable = str_contains(strtolower($modalidad?->nombre ?? ''), 'sustentable');
    $diasComprobacion = $esSustentable ? 60 : 45;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
@include('pdf.formatos._styles')
</head>
<body>
<div class="page">
@include('pdf.formatos._header')

<div class="seccion">
    <div class="seccion-titulo">Carta Compromiso del Acreditado</div>
    <div class="texto-declaracion">
        Yo, <strong>{{ $val($solicitud->nombre_completo) }}</strong>, con CURP <strong>{{ $val($solicitud->curp) }}</strong>,
        beneficiario del crédito CREA modalidad <strong>{{ $modalidad?->nombre }}</strong> por un monto de
        <strong>${{ $n($monto) }} M.N.</strong>, otorgado por el Instituto Yucateco de Emprendedores (IYEM), me comprometo a:
    </div>
    <ol style="font-size:9px;line-height:1.8;padding-left:16px;color:#333">
        <li>Destinar el crédito exclusivamente al fin declarado en mi solicitud: <strong>{{ $val($wizard['destino_credito'] ?? '') }}</strong>.</li>
        <li>Presentar ante el IYEM las facturas o comprobantes que acrediten el uso correcto del crédito dentro de un plazo máximo de <strong>{{ $diasComprobacion }} días naturales</strong> contados a partir de la fecha de desembolso.</li>
        <li>Realizar puntualmente los pagos de mi crédito conforme a la tabla de amortización pactada en el contrato de apertura de crédito.</li>
        <li>Notificar al IYEM cualquier cambio de domicilio, teléfono o correo electrónico.</li>
        <li>Permitir las visitas de seguimiento y supervisión que el IYEM considere necesarias para verificar el uso del crédito.</li>
        <li>Entender que el incumplimiento de este compromiso es causal de rescisión del contrato de apertura de crédito.</li>
    </ol>
</div>

<div class="seccion" style="margin-top:20px">
    <div class="campo"><div class="campo-label">Lugar y fecha</div><div class="campo-valor">Mérida, Yucatán, a {{ $fecha }}</div></div>
    <div class="firmas" style="margin-top:30px">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($solicitud->nombre_completo) }}</div><div class="firma-etiqueta">Firma del Acreditado</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento legal — conservar en expediente.</div>
</div>
</body>
</html>

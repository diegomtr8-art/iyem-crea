{{-- F-PR-OCC-10 R00 — Pagaré --}}
@php
    $codigo = 'F-PR-OCC-10 R00';
    $titulo = 'Pagaré';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $n      = fn($v) => number_format((float)($v ?? 0), 2);
    $val    = fn($v) => $v ?? '';
    $monto  = (float) ($solicitud->monto_solicitado ?? 0);
    $plazo  = (int) ($solicitud->plazo_meses ?? 0);
    $av     = $aval ?? null;
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
    <div class="seccion-titulo">Pagaré</div>
    <div class="texto-declaracion">
        Por este pagaré me obligo a pagar incondicionalmente a la orden del <strong>Instituto Yucateco de Emprendedores (IYEM)</strong>,
        en la ciudad de Mérida, Yucatán, la cantidad de <strong>${{ $n($monto) }} M.N.</strong>
        ({{ $val($wizard['destino_credito'] ?? '') }}), que me ha sido entregada en calidad de crédito del Programa CREA,
        modalidad <strong>{{ $modalidad?->nombre }}</strong>, a pagar en un plazo de <strong>{{ $plazo }} meses</strong>
        conforme a la tabla de amortización anexa al contrato de apertura de crédito, la cual forma parte integral de este pagaré.
    </div>
    <div class="texto-declaracion">
        En caso de falta de pago de una o más cuotas conforme a lo pactado, el IYEM podrá dar por vencido anticipadamente el plazo
        y exigir el pago total del saldo insoluto, más los intereses moratorios que correspondan según el contrato de apertura de crédito.
    </div>
</div>

<div class="seccion">
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Acreditado</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">CURP</div><div class="campo-valor" style="font-family:monospace">{{ $val($solicitud->curp) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Aval</div><div class="campo-valor">{{ $val($av?->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">CURP del aval</div><div class="campo-valor" style="font-family:monospace">{{ $val($av?->curp) }}</div></div>
        </div>
    </div>
</div>

<div class="seccion" style="margin-top:20px">
    <div class="campo"><div class="campo-label">Lugar y fecha de suscripción</div><div class="campo-valor">Mérida, Yucatán, a {{ $fecha }}</div></div>
    <div class="firmas" style="margin-top:30px">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($solicitud->nombre_completo) }}</div><div class="firma-etiqueta">Firma del Acreditado</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($av?->nombre_completo) }}</div><div class="firma-etiqueta">Firma del Aval</div></div></div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento legal — conservar en expediente.</div>
</div>
</body>
</html>

{{-- GT-PR-OCC-04 R00 — Instructivo del Proceso de Pago --}}
@php
    $codigo = 'GT-PR-OCC-04 R00';
    $titulo = 'Instructivo del Proceso de Pago';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $val    = fn($v) => $v ?? '';
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
    <div class="seccion-titulo">¿Cómo pagar tu crédito CREA?</div>
    <div class="texto-declaracion">
        Estimado(a) <strong>{{ $val($solicitud->nombre_completo) }}</strong>, a continuación encontrarás las formas disponibles
        para realizar los pagos de tu crédito modalidad <strong>{{ $modalidad?->nombre }}</strong>.
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo-gold">Opción 1 — Transferencia / Depósito bancario</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Banco</div><div class="campo-valor">BBVA Bancomer</div></div>
            <div class="campo"><div class="campo-label">CIE (Referencia de pago)</div><div class="campo-valor" style="font-family:monospace">001776533 (SERVICIOS)</div></div>
            <div class="campo"><div class="campo-label">CLABE interbancaria</div><div class="campo-valor" style="font-family:monospace">012914002017765339</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Beneficiario</div><div class="campo-valor">Instituto Yucateco de Emprendedores</div></div>
            <div class="campo"><div class="campo-label">RFC</div><div class="campo-valor" style="font-family:monospace">IIC991117V18</div></div>
            <div class="campo"><div class="campo-label">Concepto de pago</div><div class="campo-valor">Número de contrato: {{ $val($solicitud->credito?->clave_contrato) }}</div></div>
        </div>
    </div>
    <div class="aviso">Es indispensable incluir el número de contrato en el concepto de pago para que tu abono sea identificado correctamente.</div>
</div>

<div class="seccion">
    <div class="seccion-titulo-gold">Opción 2 — Pago en caja, oficinas IYEM</div>
    <div class="campo"><div class="campo-label">Dirección</div><div class="campo-valor">Av. Principal Industrias No Contaminantes, Tablaje 13613, Sodzil Norte, CP 97110, Mérida, Yucatán</div></div>
    <div class="grid-2" style="margin-top:4px">
        <div class="col"><div class="campo"><div class="campo-label">Horario de caja</div><div class="campo-valor">Lunes a viernes, 9:00 a 14:00 hrs.</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Contacto</div><div class="campo-valor">crea@iyemyucatan.com — WhatsApp 999 234 2693</div></div></div>
    </div>
</div>

<div class="seccion">
    <div class="texto-declaracion">
        Los pagos realizados por transferencia o depósito se reflejan en tu portal ciudadano dentro de 24–48 horas hábiles.
        Consulta tu tabla de amortización y estado de cuenta en cualquier momento desde el Portal Ciudadano CREA.
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }}</div>
</div>
</body>
</html>

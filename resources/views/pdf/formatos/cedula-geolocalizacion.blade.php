{{-- F-PR-OCC-13 R00 — Cédula de Geolocalización --}}
@php
    $codigo = 'F-PR-OCC-13 R00';
    $titulo = 'Cédula de Geolocalización del Negocio';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $val    = fn($v) => $v ?? '';
    $negocio = $wizard['datos_negocio_ext'] ?? [];
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
    <div class="seccion-titulo">I. Datos del Negocio</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre comercial</div><div class="campo-valor">{{ $val($negocio['nombre_comercial'] ?? null) }}</div></div>
            <div class="campo"><div class="campo-label">Giro / Actividad</div><div class="campo-valor">{{ $val($solicitud->giro_comercial) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Municipio</div><div class="campo-valor">{{ $val($negocio['municipio_empresa'] ?? $solicitud->municipio) }}</div></div>
            <div class="campo"><div class="campo-label">Titular</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div>
        </div>
    </div>
    <div class="campo" style="margin-top:4px"><div class="campo-label">Dirección del negocio</div><div class="campo-valor">{{ $val($solicitud->direccion) }}</div></div>
</div>

<div class="seccion">
    <div class="seccion-titulo">II. Geolocalización</div>
    <div class="campo"><div class="campo-label">Enlace de Google Maps</div><div class="campo-valor-box" style="min-height:22px">Pendiente de captura por el beneficiario en el Portal Ciudadano.</div></div>
</div>

<div class="seccion">
    <div class="seccion-titulo">III. Fotografía de Fachada</div>
    <div class="campo-valor-box" style="min-height:140px; text-align:center; color:#999; padding-top:60px">
        Fotografía de fachada pendiente de captura por el beneficiario en el Portal Ciudadano.
    </div>
</div>

<div class="seccion" style="margin-top:16px">
    <div class="firmas">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($solicitud->nombre_completo) }}</div><div class="firma-etiqueta">Firma del Acreditado</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }}</div>
</div>
</body>
</html>

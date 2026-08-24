{{-- F-PR-OCC-15/16 R00 — Carta Declaratoria de No Ser Servidor Público --}}
@php
    $codigo = 'F-PR-OCC-15/16 R00';
    $titulo = 'Declaratoria de No Ser Servidor Público';
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
    <div class="seccion-titulo">Declaración Bajo Protesta de Decir Verdad</div>
    <div class="texto-declaracion">
        Yo, <strong>{{ $val($solicitud->nombre_completo) }}</strong>, con CURP <strong>{{ $val($solicitud->curp) }}</strong>
        y RFC <strong>{{ $val($solicitud->rfc) }}</strong>, solicitante del Programa CREA modalidad
        <strong>{{ $modalidad?->nombre }}</strong> del Instituto Yucateco de Emprendedores (IYEM), declaro bajo protesta
        de decir verdad que:
    </div>
    <ol style="font-size:9px;line-height:1.8;padding-left:16px;color:#333">
        <li>No soy servidor público, ni ocupo cargo, empleo o comisión alguna en la administración pública federal, estatal o municipal.</li>
        <li>No tengo parentesco por consanguinidad o afinidad hasta el cuarto grado, ni relación de negocios, con servidores públicos del IYEM que participen en el proceso de evaluación y aprobación de este crédito.</li>
        <li>La información y documentación proporcionada en mi solicitud es verídica y me hago responsable de cualquier inexactitud.</li>
    </ol>
    <div class="texto-declaracion">
        Manifiesto conocer que la falsedad en esta declaración es causal de rechazo o cancelación inmediata del crédito otorgado,
        sin perjuicio de las responsabilidades legales que correspondan.
    </div>
</div>

<div class="seccion" style="margin-top:20px">
    <div class="campo"><div class="campo-label">Lugar y fecha</div><div class="campo-valor">Mérida, Yucatán, a {{ $fecha }}</div></div>
    <div class="firmas" style="margin-top:30px">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($solicitud->nombre_completo) }}</div><div class="firma-etiqueta">Firma del Solicitante</div></div></div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento legal — conservar en expediente.</div>
</div>
</body>
</html>

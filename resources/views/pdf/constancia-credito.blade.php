<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
    .page { padding: 40px 50px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1a1a1a; padding-bottom: 14px; margin-bottom: 28px; }
    .org-name { font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .org-sub { font-size: 9px; color: #666; margin-top: 2px; }
    .doc-title { text-align: right; }
    .doc-title-main { font-size: 13px; font-weight: bold; }
    .doc-title-sub { font-size: 9px; color: #888; }
    .cuerpo { font-size: 11px; line-height: 1.7; margin-bottom: 26px; text-align: justify; }
    .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #888; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 30px; }
    .dato { margin-bottom: 8px; }
    .dato-label { font-size: 8.5px; color: #888; text-transform: uppercase; }
    .dato-val { font-size: 12px; font-weight: bold; }
    .estatus-badge { display: inline-block; background: #f3f4f6; color: #1a1a1a; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: bold; }
    .firma { margin-top: 50px; text-align: center; }
    .firma-linea { border-top: 1px solid #1a1a1a; width: 240px; margin: 0 auto 6px; }
    .firma-texto { font-size: 9px; color: #666; }
    .footer-doc { margin-top: 30px; border-top: 1px solid #eee; padding-top: 8px; text-align: center; font-size: 8px; color: #999; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="org-name">Instituto Yucateco de Emprendedores</div>
            <div class="org-sub">IYEM — Programa CREA</div>
        </div>
        <div class="doc-title">
            <div class="doc-title-main">Constancia de Crédito</div>
            <div class="doc-title-sub">Generado: {{ $fecha_generacion }}</div>
        </div>
    </div>

    <div class="cuerpo">
        El Instituto Yucateco de Emprendedores (IYEM) hace constar que
        <strong>{{ $credito->acreditado?->nombre_completo }}</strong>
        cuenta con un crédito otorgado dentro del programa CREA, con folio de contrato
        <strong>{{ $credito->clave_contrato }}</strong>, cuyo estatus actual es
        <strong>{{ $credito->estatus }}</strong>.
    </div>

    <div class="section-title">Datos del Crédito</div>
    <div class="grid-2">
        <div class="dato">
            <div class="dato-label">Nombre del Acreditado</div>
            <div class="dato-val">{{ $credito->acreditado?->nombre_completo }}</div>
        </div>
        <div class="dato">
            <div class="dato-label">Folio del Contrato</div>
            <div class="dato-val">{{ $credito->clave_contrato }}</div>
        </div>
        <div class="dato">
            <div class="dato-label">Monto Otorgado</div>
            <div class="dato-val">${{ number_format((float) $credito->monto_otorgado, 2) }}</div>
        </div>
        <div class="dato">
            <div class="dato-label">Estatus</div>
            <div class="dato-val"><span class="estatus-badge">{{ $credito->estatus }}</span></div>
        </div>
    </div>

    <div class="firma">
        <div class="firma-linea"></div>
        <div class="firma-texto">Instituto Yucateco de Emprendedores — Programa CREA</div>
    </div>

    <div class="footer-doc">
        Constancia generada el {{ $fecha_generacion }} &nbsp;|&nbsp; Contrato: {{ $credito->clave_contrato }} &nbsp;|&nbsp; IYEM — Documento informativo, no es comprobante de pago ni estado de cuenta.
    </div>

</div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
    .page { padding: 40px 50px; }
    .header { text-align: center; margin-bottom: 30px; }
    .escudo { font-size: 40px; margin-bottom: 8px; }
    .gov-name { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .gov-sub { font-size: 10px; color: #555; margin-top: 3px; }
    .titulo { text-align: center; margin: 30px 0; border-top: 2px solid #1a1a1a; border-bottom: 2px solid #1a1a1a; padding: 14px 0; }
    .titulo-main { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; }
    .titulo-folio { font-size: 10px; color: #555; margin-top: 4px; }
    .cuerpo { line-height: 2.0; text-align: justify; font-size: 11px; margin: 20px 0; }
    .datos-resaltados { background: #f9f9f9; border: 1px solid #ddd; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
    .dato { display: flex; margin-bottom: 8px; }
    .dato-label { font-weight: bold; min-width: 180px; font-size: 10px; text-transform: uppercase; color: #555; }
    .dato-val { font-size: 11px; }
    .leyenda { margin: 24px 0; font-size: 10px; color: #555; text-align: center; font-style: italic; }
    .firma-area { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
    .firma-box { text-align: center; }
    .firma-linea { border-top: 1px solid #333; margin-top: 50px; padding-top: 6px; }
    .firma-nombre { font-size: 10px; font-weight: bold; }
    .firma-cargo { font-size: 9.5px; color: #666; }
    .sello-area { text-align: center; margin-top: 20px; }
    .sello { display: inline-block; border: 3px solid #1a1a1a; border-radius: 50%; width: 90px; height: 90px; line-height: 90px; font-weight: bold; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; text-align: center; }
    .footer-doc { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; font-size: 8.5px; color: #999; }
    .qr-area { text-align: right; margin-top: -80px; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="gov-name">Gobierno del Estado de Yucatán</div>
        <div class="gov-sub">Instituto Yucateco de Emprendedores — IYEM</div>
        <div class="gov-sub">Programa CREA — Crédito para el Desarrollo Empresarial</div>
    </div>

    <div class="titulo">
        <div class="titulo-main">Constancia de No Adeudo</div>
        <div class="titulo-folio">Folio: CNA-{{ $credito->clave_contrato }}-{{ now()->format('Y') }}</div>
    </div>

    <div class="cuerpo">
        El suscrito, en mi carácter de Director General del <strong>Instituto Yucateco de Emprendedores (IYEM)</strong>, hago constar que el C. <strong>{{ $credito->acreditado?->nombre_completo }}</strong>, con CURP <strong>{{ $credito->acreditado?->curp }}</strong>, con domicilio en <strong>{{ $credito->acreditado?->municipio }}, Yucatán</strong>, ha liquidado en su totalidad el crédito otorgado bajo el Programa <strong>{{ $credito->modalidad?->nombre }}</strong>, según los registros del sistema de gestión del IYEM.
    </div>

    <div class="datos-resaltados">
        <div class="dato"><div class="dato-label">No. de Contrato:</div><div class="dato-val"><strong>{{ $credito->clave_contrato }}</strong></div></div>
        <div class="dato"><div class="dato-label">Monto Otorgado:</div><div class="dato-val">${{ number_format((float)$credito->monto_otorgado, 2) }} MXN</div></div>
        <div class="dato"><div class="dato-label">Fecha de Otorgamiento:</div><div class="dato-val">{{ $credito->fecha_entrega?->format('d/m/Y') }}</div></div>
        <div class="dato"><div class="dato-label">Total Pagado:</div><div class="dato-val"><strong>${{ number_format($totalPagado, 2) }} MXN</strong></div></div>
        <div class="dato"><div class="dato-label">Fecha de Liquidación:</div><div class="dato-val"><strong>{{ $fechaLiquidacion?->format('d/m/Y') }}</strong></div></div>
        <div class="dato"><div class="dato-label">Estatus:</div><div class="dato-val"><strong>LIQUIDADO</strong></div></div>
    </div>

    <div class="cuerpo">
        En virtud de lo anterior, EL INSTITUTO certifica que a la fecha de expedición de este documento, el crédito antes citado se encuentra <strong>totalmente liquidado</strong>, por lo que no existe adeudo pendiente alguno a cargo del beneficiario mencionado por concepto del citado crédito, quedando extinguida toda obligación derivada del mismo.
    </div>

    <div class="leyenda">
        "Esta constancia se expide a petición del interesado para los fines legales que le convengan."
    </div>

    <div class="firma-area">
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">Director General</div>
                <div class="firma-cargo">Instituto Yucateco de Emprendedores</div>
            </div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">Jefe del Departamento de Crédito</div>
                <div class="firma-cargo">IYEM — Programa CREA</div>
            </div>
        </div>
    </div>

    <div class="footer-doc">
        Constancia expedida en Mérida, Yucatán, el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}.<br>
        Folio: CNA-{{ $credito->clave_contrato }}-{{ now()->format('Y') }} &nbsp;|&nbsp; IYEM — Av. Principal, Industrias No Contaminantes, Sodzil Norte, Mérida, Yucatán &nbsp;|&nbsp; iyem@yucatan.gob.mx
    </div>

</div>
</body>
</html>

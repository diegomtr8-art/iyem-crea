<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
    .page { padding: 30px 40px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1a1a1a; padding-bottom: 16px; margin-bottom: 20px; }
    .org-name { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .org-sub { font-size: 10px; color: #666; margin-top: 2px; }
    .folio-box { text-align: right; }
    .folio-label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; }
    .folio-val { font-size: 22px; font-weight: bold; color: #c0392b; }
    .section { margin-bottom: 18px; }
    .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #999; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .field { margin-bottom: 6px; }
    .field-label { font-size: 9px; color: #888; text-transform: uppercase; }
    .field-val { font-size: 12px; font-weight: bold; }
    .amount-box { background: #1a1a1a; color: #fff; border-radius: 8px; padding: 16px 20px; text-align: center; margin: 16px 0; }
    .amount-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6; }
    .amount-val { font-size: 32px; font-weight: bold; letter-spacing: -1px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead tr { background: #f5f5f5; }
    th { padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #666; }
    td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; }
    .text-right { text-align: right; }
    .dist-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 10px; }
    .dist-card { border: 1px solid #eee; border-radius: 6px; padding: 10px; text-align: center; }
    .dist-card-label { font-size: 9px; text-transform: uppercase; color: #999; }
    .dist-card-val { font-size: 14px; font-weight: bold; }
    .mora-color { color: #e67e22; }
    .ord-color { color: #c0392b; }
    .cap-color { color: #2980b9; }
    .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .firma-line { border-top: 1px solid #999; margin-top: 40px; padding-top: 4px; font-size: 9px; color: #888; text-align: center; }
    .note { font-size: 9px; color: #999; margin-top: 12px; text-align: center; }
    .badge { display: inline-block; background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; border-radius: 4px; padding: 2px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="org-name">Instituto Yucateco de Emprendedores</div>
            <div class="org-sub">IYEM — Programa CREA</div>
            <div class="org-sub" style="margin-top:4px">BBVA CIE: 001776533 &nbsp;|&nbsp; CLABE: 012914002017765339</div>
        </div>
        <div class="folio-box">
            <div class="folio-label">Recibo de Pago</div>
            <div class="folio-val">{{ $pago->folio }}</div>
            <div style="font-size:10px;color:#666;margin-top:2px">{{ $pago->fecha_pago->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Acreditado</div>
        <div class="grid-2">
            <div>
                <div class="field">
                    <div class="field-label">Nombre Completo</div>
                    <div class="field-val">{{ $pago->acreditado?->nombre_completo }}</div>
                </div>
                <div class="field">
                    <div class="field-label">RFC</div>
                    <div class="field-val">{{ $pago->acreditado?->rfc ?: '—' }}</div>
                </div>
            </div>
            <div>
                <div class="field">
                    <div class="field-label">Contrato</div>
                    <div class="field-val">{{ $pago->credito?->clave_contrato }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Municipio</div>
                    <div class="field-val">{{ $pago->acreditado?->municipio }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div class="amount-label">Monto Recibido</div>
        <div class="amount-val">${{ number_format((float)$pago->monto_recibido, 2) }} MXN</div>
        <div style="font-size:10px;margin-top:4px;opacity:0.7">{{ $pago->forma_pago }}@if($pago->referencia) &nbsp;|&nbsp; Ref: {{ $pago->referencia }}@endif</div>
    </div>

    <div class="section">
        <div class="section-title">Distribución del Pago</div>
        <div class="dist-grid">
            <div class="dist-card">
                <div class="dist-card-label mora-color">Mora</div>
                <div class="dist-card-val mora-color">${{ number_format((float)$pago->aplicado_mora, 2) }}</div>
            </div>
            <div class="dist-card">
                <div class="dist-card-label ord-color">Interés Ordinario</div>
                <div class="dist-card-val ord-color">${{ number_format((float)$pago->aplicado_ordinario, 2) }}</div>
            </div>
            <div class="dist-card">
                <div class="dist-card-label cap-color">Capital</div>
                <div class="dist-card-val cap-color">${{ number_format((float)$pago->aplicado_capital, 2) }}</div>
            </div>
        </div>
    </div>

    @if(!empty($pago->cuotas_cubiertas))
    <div class="section">
        <div class="section-title">Cuotas Afectadas</div>
        <table>
            <thead>
                <tr>
                    <th>Cuota</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Interés</th>
                    <th class="text-right">Mora</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pago->cuotas_cubiertas as $c)
                <tr>
                    <td><strong>#{{ $c['cuota'] }}</strong></td>
                    <td class="text-right">${{ number_format((float)($c['cap'] ?? 0), 2) }}</td>
                    <td class="text-right">${{ number_format((float)($c['int'] ?? 0), 2) }}</td>
                    <td class="text-right" style="color:#e67e22">${{ number_format((float)($c['mor'] ?? 0), 2) }}</td>
                    <td class="text-right"><strong>${{ number_format(((float)($c['cap']??0)+(float)($c['int']??0)+(float)($c['mor']??0)), 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($pago->observaciones)
    <div class="section">
        <div class="section-title">Observaciones</div>
        <div style="font-size:10px;color:#555;font-style:italic">"{{ $pago->observaciones }}"</div>
    </div>
    @endif

    <div class="footer">
        <div>
            <div class="firma-line">Recibí conforme</div>
            <div style="font-size:9px;color:#999;text-align:center;margin-top:4px">{{ $pago->acreditado?->nombre_completo }}</div>
        </div>
        <div>
            <div class="firma-line">Operador de Caja</div>
            <div style="font-size:9px;color:#999;text-align:center;margin-top:4px">{{ $pago->cajero?->name ?? 'Sistema' }}</div>
        </div>
    </div>

    <div class="note">
        Documento generado el {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ $pago->folio }} &nbsp;|&nbsp; IYEM — Este recibo es comprobante oficial de pago.
    </div>

</div>
</body>
</html>

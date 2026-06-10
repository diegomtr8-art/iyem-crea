<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
    .page { padding: 28px 36px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1a1a1a; padding-bottom: 14px; margin-bottom: 18px; }
    .org-name { font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .org-sub { font-size: 9px; color: #666; margin-top: 2px; }
    .doc-title { text-align: right; }
    .doc-title-main { font-size: 13px; font-weight: bold; }
    .doc-title-sub { font-size: 9px; color: #888; }
    .section { margin-bottom: 16px; }
    .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #888; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 8px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
    .dato { margin-bottom: 5px; }
    .dato-label { font-size: 8.5px; color: #888; text-transform: uppercase; }
    .dato-val { font-size: 11px; font-weight: bold; }
    .kpi-card { background: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .kpi-label { font-size: 8px; text-transform: uppercase; color: #888; margin-bottom: 2px; }
    .kpi-val { font-size: 13px; font-weight: bold; }
    .kpi-verde { color: #16a34a; }
    .kpi-rojo { color: #dc2626; }
    .kpi-azul { color: #2563eb; }
    .kpi-naranja { color: #d97706; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; margin: 6px 0; }
    thead tr { background: #1a1a1a; color: #fff; }
    th { padding: 5px 6px; text-align: left; font-size: 8.5px; text-transform: uppercase; }
    td { padding: 4px 6px; border-bottom: 1px solid #f0f0f0; }
    tr:nth-child(even) { background: #fafafa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .badge-pagado { background: #dcfce7; color: #166534; border-radius: 3px; padding: 1px 5px; font-size: 8px; }
    .badge-pendiente { background: #fef9c3; color: #854d0e; border-radius: 3px; padding: 1px 5px; font-size: 8px; }
    .badge-vencido { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 1px 5px; font-size: 8px; }
    .badge-parcial { background: #dbeafe; color: #1e40af; border-radius: 3px; padding: 1px 5px; font-size: 8px; }
    .badge-condonado { background: #f3e8ff; color: #6b21a8; border-radius: 3px; padding: 1px 5px; font-size: 8px; }
    .saldo-box { background: #1a1a1a; color: #fff; padding: 14px 18px; border-radius: 6px; text-align: center; margin: 12px 0; }
    .saldo-label { font-size: 8.5px; text-transform: uppercase; opacity: 0.6; margin-bottom: 2px; }
    .saldo-val { font-size: 26px; font-weight: bold; }
    .footer-doc { margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px; text-align: center; font-size: 8px; color: #999; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="org-name">Instituto Yucateco de Emprendedores</div>
            <div class="org-sub">IYEM — Programa CREA</div>
            <div class="org-sub">BBVA CIE: 001776533 | CLABE: 012914002017765339</div>
        </div>
        <div class="doc-title">
            <div class="doc-title-main">Estado de Cuenta</div>
            <div class="doc-title-sub">Crédito: {{ $credito->clave_contrato }}</div>
            <div class="doc-title-sub">Generado: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Acreditado</div>
        <div class="grid-2">
            <div>
                <div class="dato"><div class="dato-label">Nombre</div><div class="dato-val">{{ $credito->acreditado?->nombre_completo }}</div></div>
                <div class="dato"><div class="dato-label">CURP</div><div class="dato-val">{{ $credito->acreditado?->curp ?: '—' }}</div></div>
            </div>
            <div>
                <div class="dato"><div class="dato-label">RFC</div><div class="dato-val">{{ $credito->acreditado?->rfc ?: '—' }}</div></div>
                <div class="dato"><div class="dato-label">Municipio</div><div class="dato-val">{{ $credito->acreditado?->municipio }}</div></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Condiciones del Crédito</div>
        <div class="grid-3">
            <div class="dato"><div class="dato-label">Monto Otorgado</div><div class="dato-val">${{ number_format((float)$credito->monto_otorgado, 2) }}</div></div>
            <div class="dato"><div class="dato-label">Plazo</div><div class="dato-val">{{ $credito->plazo_meses }} meses</div></div>
            <div class="dato"><div class="dato-label">Modalidad</div><div class="dato-val">{{ $credito->modalidad?->nombre }}</div></div>
            <div class="dato"><div class="dato-label">Tasa Ordinaria</div><div class="dato-val">{{ number_format((float)$credito->tasa_interes_ordinario, 2) }}% anual</div></div>
            <div class="dato"><div class="dato-label">Tasa Moratoria</div><div class="dato-val">{{ number_format((float)$credito->tasa_interes_moratorio, 2) }}% anual</div></div>
            <div class="dato"><div class="dato-label">Fecha Entrega</div><div class="dato-val">{{ $credito->fecha_entrega?->format('d/m/Y') }}</div></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Resumen de Pagos</div>
        <div class="grid-3">
            <div class="kpi-card">
                <div class="kpi-label">Capital Pagado</div>
                <div class="kpi-val kpi-azul">${{ number_format($resumen['capital_pagado'], 2) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Interés Pagado</div>
                <div class="kpi-val kpi-naranja">${{ number_format($resumen['interes_pagado'], 2) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Mora Pagada</div>
                <div class="kpi-val kpi-rojo">${{ number_format($resumen['mora_pagada'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="saldo-box">
        <div class="saldo-label">Saldo Pendiente Total</div>
        <div class="saldo-val">${{ number_format($resumen['saldo_pendiente'], 2) }} MXN</div>
        <div style="font-size:9px;opacity:0.7;margin-top:4px">Estatus: {{ $credito->estatus }} &nbsp;|&nbsp; Cuotas pagadas: {{ $resumen['cuotas_pagadas'] }} / {{ $credito->plazo_meses }}</div>
    </div>

    <div class="section">
        <div class="section-title">Tabla de Amortización Actualizada</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Vencimiento</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Interés</th>
                    <th class="text-right">Mora</th>
                    <th class="text-right">Cuota</th>
                    <th class="text-right">Restante</th>
                    <th class="text-center">Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($amortizaciones as $a)
                <tr>
                    <td class="text-center">{{ $a->numero_cuota }}</td>
                    <td>{{ $a->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td class="text-right">${{ number_format((float)$a->capital_esperado, 2) }}</td>
                    <td class="text-right">${{ number_format((float)$a->interes_ordinario_esperado, 2) }}</td>
                    <td class="text-right" style="color:#e67e22">${{ number_format((float)($a->moratorio_acumulado ?? 0), 2) }}</td>
                    <td class="text-right">${{ number_format((float)$a->cuota_fija, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format((float)$a->pago_restante, 2) }}</strong></td>
                    <td class="text-center">
                        @if($a->estado === 'Pagado') <span class="badge-pagado">Pagado</span>
                        @elseif($a->estado === 'Condonado') <span class="badge-condonado">Condonado</span>
                        @elseif($a->estado === 'Parcial') <span class="badge-parcial">Parcial</span>
                        @elseif($a->fecha_vencimiento < now()) <span class="badge-vencido">Vencido</span>
                        @else <span class="badge-pendiente">Pendiente</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(count($pagos) > 0)
    <div class="section">
        <div class="section-title">Historial de Pagos</div>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th class="text-right">Monto</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Interés</th>
                    <th class="text-right">Mora</th>
                    <th>Forma</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $p)
                <tr>
                    <td>{{ $p->folio }}</td>
                    <td>{{ $p->fecha_pago->format('d/m/Y') }}</td>
                    <td class="text-right"><strong>${{ number_format((float)$p->monto_recibido, 2) }}</strong></td>
                    <td class="text-right">${{ number_format((float)$p->aplicado_capital, 2) }}</td>
                    <td class="text-right">${{ number_format((float)$p->aplicado_ordinario, 2) }}</td>
                    <td class="text-right">${{ number_format((float)$p->aplicado_mora, 2) }}</td>
                    <td>{{ $p->forma_pago }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer-doc">
        Estado de cuenta generado el {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Contrato: {{ $credito->clave_contrato }} &nbsp;|&nbsp; IYEM — Documento informativo, no es comprobante de pago.
    </div>

</div>
</body>
</html>

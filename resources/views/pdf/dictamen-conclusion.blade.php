<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
    .page { padding: 35px 45px; }
    .header { text-align: center; border-bottom: 3px double #1a1a1a; padding-bottom: 16px; margin-bottom: 20px; }
    .org-name { font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
    .doc-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 8px; color: #c0392b; }
    .doc-subtitle { font-size: 10px; color: #666; margin-top: 3px; }
    .folio-ref { font-size: 10px; color: #888; margin-top: 6px; }
    .section { margin-bottom: 20px; }
    .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; background: #1a1a1a; color: #fff; padding: 5px 10px; margin-bottom: 10px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .field { margin-bottom: 8px; }
    .field-label { font-size: 9px; color: #888; text-transform: uppercase; }
    .field-val { font-size: 12px; font-weight: bold; }
    .stat-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #999; }
    .stat-val { font-size: 16px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead tr { background: #f5f5f5; }
    th { padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #555; border-bottom: 2px solid #ddd; }
    td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; }
    .text-right { text-align: right; }
    tfoot td { font-weight: bold; background: #f9f9f9; border-top: 2px solid #ddd; }
    .sello { border: 2px solid #1a1a1a; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; text-align: center; margin: 0 auto; font-size: 8px; font-weight: bold; text-transform: uppercase; line-height: 1.4; }
    .firma-section { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center; }
    .firma-line { border-top: 1px solid #555; padding-top: 5px; font-size: 9px; color: #555; margin-top: 40px; }
    .legal-note { font-size: 9px; color: #888; margin-top: 16px; text-align: justify; line-height: 1.5; }
    .green { color: #16a34a; }
    .red { color: #c0392b; }
    .blue { color: #2980b9; }
    .orange { color: #e67e22; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="org-name">Instituto Yucateco de Emprendedores — IYEM</div>
        <div class="doc-title">Dictamen de Conclusión de Crédito</div>
        <div class="doc-subtitle">Programa CREA — Crédito a la Palabra</div>
        <div class="folio-ref">Contrato: {{ $credito->clave_contrato }} &nbsp;|&nbsp; Generado: {{ $generadoEn }}</div>
    </div>

    <div class="section">
        <div class="section-title">I. Datos del Acreditado</div>
        <div class="grid-2">
            <div>
                <div class="field"><div class="field-label">Nombre Completo</div><div class="field-val">{{ $credito->acreditado?->nombre_completo }}</div></div>
                <div class="field"><div class="field-label">RFC</div><div class="field-val">{{ $credito->acreditado?->rfc ?: '—' }}</div></div>
                <div class="field"><div class="field-label">CURP</div><div class="field-val">{{ $credito->acreditado?->curp ?: '—' }}</div></div>
            </div>
            <div>
                <div class="field"><div class="field-label">Municipio</div><div class="field-val">{{ $credito->acreditado?->municipio }}</div></div>
                <div class="field"><div class="field-label">Modalidad</div><div class="field-val">{{ $credito->modalidad?->nombre }}</div></div>
                <div class="field"><div class="field-label">Estatus Final</div><div class="field-val green">✓ LIQUIDADO</div></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">II. Condiciones del Crédito</div>
        <div class="grid-3">
            <div class="field"><div class="field-label">Monto Otorgado</div><div class="field-val">${{ number_format((float)$credito->monto_otorgado, 2) }}</div></div>
            <div class="field"><div class="field-label">Plazo</div><div class="field-val">{{ $credito->plazo_meses }} meses</div></div>
            <div class="field"><div class="field-label">Fecha de Entrega</div><div class="field-val">{{ $credito->fecha_entrega ? \Carbon\Carbon::parse($credito->fecha_entrega)->format('d/m/Y') : '—' }}</div></div>
            <div class="field"><div class="field-label">Tasa Ordinaria</div><div class="field-val">{{ (float)$credito->tasa_interes_ordinario }}% anual</div></div>
            <div class="field"><div class="field-label">Tasa Moratoria</div><div class="field-val red">{{ (float)$credito->tasa_interes_moratorio }}% anual</div></div>
            <div class="field"><div class="field-label">Contrato</div><div class="field-val">{{ $credito->clave_contrato }}</div></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. Resumen de Recuperación</div>
        <div class="grid-3">
            <div class="stat-box">
                <div class="stat-label blue">Capital Recuperado</div>
                <div class="stat-val blue">${{ number_format($totalCapital, 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label red">Interés Cobrado</div>
                <div class="stat-val red">${{ number_format($totalOrdinario, 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label orange">Mora Cobrada</div>
                <div class="stat-val orange">${{ number_format($totalMora, 2) }}</div>
            </div>
        </div>
        <div style="margin-top:10px;text-align:right;font-size:13px;font-weight:bold">
            Total Recuperado: ${{ number_format($totalCapital + $totalOrdinario + $totalMora, 2) }} MXN
        </div>
    </div>

    <div class="section">
        <div class="section-title">IV. Historial de Pagos</div>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Forma</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Interés</th>
                    <th class="text-right">Mora</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $p)
                @if((float)$p->monto_recibido > 0)
                <tr>
                    <td>{{ $p->folio }}</td>
                    <td>{{ $p->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $p->forma_pago }}</td>
                    <td class="text-right blue">${{ number_format((float)$p->aplicado_capital, 2) }}</td>
                    <td class="text-right red">${{ number_format((float)$p->aplicado_ordinario, 2) }}</td>
                    <td class="text-right orange">${{ number_format((float)$p->aplicado_mora, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format((float)$p->monto_recibido, 2) }}</strong></td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTALES</td>
                    <td class="text-right blue">${{ number_format($totalCapital, 2) }}</td>
                    <td class="text-right red">${{ number_format($totalOrdinario, 2) }}</td>
                    <td class="text-right orange">${{ number_format($totalMora, 2) }}</td>
                    <td class="text-right">${{ number_format($totalCapital + $totalOrdinario + $totalMora, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="firma-section">
        <div>
            <div class="firma-line">Elaboró</div>
            <div style="font-size:9px;color:#888;margin-top:3px">Operación / Caja</div>
        </div>
        <div>
            <div class="firma-line">Revisó</div>
            <div style="font-size:9px;color:#888;margin-top:3px">Jefatura de Crédito</div>
        </div>
        <div>
            <div class="firma-line">Autorizó</div>
            <div style="font-size:9px;color:#888;margin-top:3px">Dirección General IYEM</div>
        </div>
    </div>

    <div class="legal-note">
        El presente Dictamen de Conclusión certifica que el crédito con número de contrato <strong>{{ $credito->clave_contrato }}</strong>
        otorgado al amparo del Programa CREA del Instituto Yucateco de Emprendedores ha sido cubierto en su totalidad
        conforme a las Reglas de Operación vigentes. Se da por concluida la obligación crediticia y se libera al
        acreditado de cualquier responsabilidad derivada del mismo.
    </div>

</div>
</body>
</html>

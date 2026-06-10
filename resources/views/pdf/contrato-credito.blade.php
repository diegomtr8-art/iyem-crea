<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }
    .page { padding: 28px 36px; }
    .header { text-align: center; border-bottom: 3px double #1a1a1a; padding-bottom: 14px; margin-bottom: 18px; }
    .org-name { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
    .org-sub { font-size: 9px; color: #555; margin-top: 2px; }
    .titulo-contrato { font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 12px 0 4px; letter-spacing: 1px; }
    .folio { font-size: 10px; color: #555; }
    .clausula { margin-bottom: 14px; }
    .clausula-titulo { font-weight: bold; font-size: 10px; text-transform: uppercase; margin-bottom: 4px; }
    .clausula-body { font-size: 9.5px; line-height: 1.6; text-align: justify; }
    .datos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 10px 0; }
    .dato { margin-bottom: 5px; }
    .dato-label { font-size: 8.5px; color: #888; text-transform: uppercase; }
    .dato-val { font-size: 10px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 1px; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; margin: 8px 0; }
    thead tr { background: #1a1a1a; color: #fff; }
    th { padding: 5px 6px; text-align: left; font-size: 8.5px; text-transform: uppercase; }
    td { padding: 4px 6px; border-bottom: 1px solid #eee; }
    tr:nth-child(even) { background: #f9f9f9; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; }
    .firma-box { text-align: center; }
    .firma-linea { border-top: 1px solid #333; margin-top: 45px; padding-top: 4px; }
    .firma-nombre { font-size: 9px; font-weight: bold; }
    .firma-cargo { font-size: 8.5px; color: #666; }
    .sello { border: 2px solid #1a1a1a; border-radius: 50%; width: 80px; height: 80px; margin: 10px auto; display: flex; align-items: center; justify-content: center; font-size: 8px; text-align: center; font-weight: bold; text-transform: uppercase; }
    .destaque { background: #f5f5f5; border-left: 3px solid #1a1a1a; padding: 8px 12px; margin: 10px 0; font-size: 9.5px; }
    .footer-doc { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; text-align: center; font-size: 8px; color: #888; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="org-name">Instituto Yucateco de Emprendedores</div>
        <div class="org-sub">Programa CREA — Crédito para el Desarrollo Empresarial de Yucatán</div>
        <div class="org-sub">Av. Principal s/n, Industrias No Contaminantes, Sodzil Norte, Mérida, Yucatán</div>
        <div class="titulo-contrato">Contrato de Crédito Simple</div>
        <div class="folio">Contrato No. {{ $credito->clave_contrato }} &nbsp;|&nbsp; Modalidad: {{ $credito->modalidad?->nombre }}</div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Partes Contratantes</div>
        <div class="clausula-body">
            Por una parte, el <strong>Instituto Yucateco de Emprendedores (IYEM)</strong>, representado en este acto por su Director General, en adelante "EL OTORGANTE"; y por la otra parte, el/la C. <strong>{{ $credito->acreditado?->nombre_completo }}</strong>, con CURP <strong>{{ $credito->acreditado?->curp }}</strong>, RFC <strong>{{ $credito->acreditado?->rfc }}</strong>, domiciliado en <strong>{{ $credito->acreditado?->direccion_fiscal ?? $credito->acreditado?->municipio }}</strong>, Yucatán, en adelante "EL ACREDITADO".
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Primera — Objeto del Contrato</div>
        <div class="clausula-body">
            EL OTORGANTE concede a EL ACREDITADO un crédito simple bajo el Programa <strong>{{ $credito->modalidad?->nombre }}</strong> por la cantidad de <strong>${{ number_format((float)$credito->monto_otorgado, 2) }} M.N. ({{ $montoLetras }})</strong>, para ser destinado a: actividades productivas y/o capital de trabajo en el giro declarado por EL ACREDITADO, de conformidad con las reglas de operación del programa.
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Segunda — Condiciones Financieras</div>
        <div class="clausula-body">
            <div class="datos-grid">
                <div>
                    <div class="dato"><div class="dato-label">Monto Otorgado</div><div class="dato-val">${{ number_format((float)$credito->monto_otorgado, 2) }} MXN</div></div>
                    <div class="dato"><div class="dato-label">Tasa de Interés Ordinario Anual</div><div class="dato-val">{{ number_format((float)$credito->tasa_interes_ordinario, 2) }}%</div></div>
                    <div class="dato"><div class="dato-label">Tasa de Interés Moratorio Anual</div><div class="dato-val">{{ number_format((float)$credito->tasa_interes_moratorio, 2) }}%</div></div>
                </div>
                <div>
                    <div class="dato"><div class="dato-label">Plazo</div><div class="dato-val">{{ $credito->plazo_meses }} meses</div></div>
                    <div class="dato"><div class="dato-label">Fecha de Entrega</div><div class="dato-val">{{ $credito->fecha_entrega?->format('d/m/Y') }}</div></div>
                    <div class="dato"><div class="dato-label">Modalidad</div><div class="dato-val">{{ $credito->modalidad?->nombre }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Tercera — Plan de Amortización</div>
        <div class="clausula-body">
            EL ACREDITADO se compromete a liquidar el crédito en {{ $credito->plazo_meses }} pagos mensuales conforme al siguiente calendario:
        </div>
        <table>
            <thead>
                <tr>
                    <th class="text-center">No.</th>
                    <th>Fecha</th>
                    <th class="text-right">Saldo Inicial</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Interés</th>
                    <th class="text-right">Cuota</th>
                    <th class="text-right">Saldo Final</th>
                </tr>
            </thead>
            <tbody>
                @php $saldoFinal = (float)$credito->monto_otorgado; @endphp
                @foreach($amortizaciones as $a)
                <tr>
                    <td class="text-center">{{ $a->numero_cuota }}</td>
                    <td>{{ $a->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td class="text-right">${{ number_format((float)$a->saldo_insoluto, 2) }}</td>
                    <td class="text-right">${{ number_format((float)$a->capital_esperado, 2) }}</td>
                    <td class="text-right">${{ number_format((float)$a->interes_ordinario_esperado, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format((float)$a->cuota_fija, 2) }}</strong></td>
                    <td class="text-right">${{ number_format(max(0, (float)$a->saldo_insoluto - (float)$a->capital_esperado), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Cuarta — Mora y Penalizaciones</div>
        <div class="clausula-body">
            En caso de que EL ACREDITADO no cubra en tiempo y forma sus pagos, se causarán intereses moratorios a la tasa del <strong>{{ number_format((float)$credito->tasa_interes_moratorio, 2) }}% anual</strong> sobre el saldo vencido, calculados a partir del día siguiente a la fecha de vencimiento, con un período de gracia de 5 (cinco) días naturales. El año comercial se calculará sobre base de 360 días.
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Quinta — Forma de Pago</div>
        <div class="clausula-body">
            Los pagos podrán realizarse mediante: (a) Depósito o transferencia bancaria a la cuenta BBVA CIE 001776533, CLABE 012914002017765339, a nombre del Instituto Yucateco de Emprendedores; (b) Pago en caja en las instalaciones de IYEM, presentando este contrato.
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Sexta — Declaraciones del Acreditado</div>
        <div class="clausula-body">
            EL ACREDITADO declara bajo protesta de decir verdad que: (a) los datos personales y del negocio proporcionados son verídicos; (b) los recursos del crédito serán destinados exclusivamente al objeto declarado; (c) ha leído y entendido las condiciones del presente contrato; (d) autoriza al IYEM a consultar su información en instancias gubernamentales para verificación de datos.
        </div>
    </div>

    <div class="clausula">
        <div class="clausula-titulo">Séptima — Causas de Vencimiento Anticipado</div>
        <div class="clausula-body">
            Se tendrá por vencido anticipadamente el presente contrato en caso de: (a) destino diferente al pactado; (b) datos falsos en la solicitud; (c) tres o más cuotas vencidas consecutivas; (d) cualquier acto que perjudique los intereses del IYEM.
        </div>
    </div>

    <div class="destaque">
        Leído y firmado el presente contrato en la ciudad de Mérida, Yucatán, a los {{ $credito->fecha_contrato ? $credito->fecha_contrato->format('d') : now()->format('d') }} días del mes de {{ $credito->fecha_contrato ? $credito->fecha_contrato->locale('es')->isoFormat('MMMM') : now()->locale('es')->isoFormat('MMMM') }} de {{ $credito->fecha_contrato ? $credito->fecha_contrato->format('Y') : now()->format('Y') }}.
    </div>

    <div class="firmas">
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $credito->acreditado?->nombre_completo }}</div>
                <div class="firma-cargo">EL ACREDITADO</div>
                <div class="firma-cargo">CURP: {{ $credito->acreditado?->curp }}</div>
            </div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">Director General</div>
                <div class="firma-cargo">Instituto Yucateco de Emprendedores</div>
                <div class="firma-cargo">EL OTORGANTE</div>
            </div>
        </div>
    </div>

    <div class="footer-doc">
        Contrato No. {{ $credito->clave_contrato }} &nbsp;|&nbsp; Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; IYEM — Instituto Yucateco de Emprendedores &nbsp;|&nbsp; Documento con valor legal.
    </div>

</div>
</body>
</html>

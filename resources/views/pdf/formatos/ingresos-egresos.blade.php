{{-- F-PR-OCC-05 R00 — Estado de Ingresos y Egresos --}}
@php
    $codigo = 'F-PR-OCC-05 R00';
    $titulo = 'Estado de Ingresos y Egresos';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $ie     = $wizard['ingresos_egresos'] ?? [];
    $val    = fn($v) => $v ?? '';
    $n      = fn($v) => number_format((float)($v ?? 0), 2);

    // Cálculos automáticos
    $ventas         = (float)($ie['ventas'] ?? 0);
    $costo          = (float)($ie['costo_producto'] ?? 0);
    $utilBruta      = $ventas - $costo;
    $gastos         = [
        'Energía eléctrica'        => (float)($ie['gastos_electricidad'] ?? 0),
        'Agua'                     => (float)($ie['gastos_agua'] ?? 0),
        'Teléfono'                 => (float)($ie['gastos_telefono'] ?? 0),
        'Gas'                      => (float)($ie['gastos_gas'] ?? 0),
        'Mano de obra (solicitante)'=> (float)($ie['gastos_mano_obra'] ?? 0),
        'Nómina de empleados'      => (float)($ie['gastos_nomina'] ?? 0),
        'Renta del local'          => (float)($ie['gastos_renta_local'] ?? 0),
    ];
    $otrosGastos    = $ie['otros_gastos'] ?? [];
    $totalGastosBase= array_sum($gastos);
    $totalOtros     = array_sum(array_column($otrosGastos, 'importe'));
    $totalGastos    = $totalGastosBase + $totalOtros;
    $utilAnteImp    = $utilBruta - $totalGastos;
    $impuestos      = (float)($ie['impuestos'] ?? 0);
    $utilNeta       = $utilAnteImp - $impuestos;
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
    <div class="grid-2">
        <div class="col"><div class="campo"><div class="campo-label">Nombre del solicitante</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div></div>
        <div class="col">
            <div class="grid-2">
                <div class="col"><div class="campo"><div class="campo-label">Periodo del</div><div class="campo-valor">{{ $val($ie['periodo_del'] ?? '') }}</div></div></div>
                <div class="col"><div class="campo"><div class="campo-label">al</div><div class="campo-valor">{{ $val($ie['periodo_al'] ?? '') }}</div></div></div>
            </div>
        </div>
    </div>
</div>

{{-- INGRESOS --}}
<div class="seccion">
    <div class="seccion-titulo">I. Ingresos</div>
    <table>
        <thead><tr><th>Concepto</th><th class="text-right">Importe mensual ($)</th></tr></thead>
        <tbody>
            <tr><td>Ventas / Servicios</td><td class="text-right bold">${{ $n($ventas) }}</td></tr>
            <tr><td>Costo del producto / servicio</td><td class="text-right">${{ $n($costo) }}</td></tr>
            <tr style="background:#e8f5e9"><td class="bold">Utilidad bruta</td><td class="text-right bold color-verde">${{ $n($utilBruta) }}</td></tr>
        </tbody>
    </table>
</div>

{{-- GASTOS DE OPERACIÓN --}}
<div class="seccion">
    <div class="seccion-titulo">II. Gastos de Operación</div>
    <table>
        <thead><tr><th>Concepto</th><th class="text-right">Importe mensual ($)</th></tr></thead>
        <tbody>
            @foreach($gastos as $concepto => $importe)
            <tr><td>{{ $concepto }}</td><td class="text-right">${{ $n($importe) }}</td></tr>
            @endforeach
            @foreach($otrosGastos as $og)
            <tr><td>Otros: {{ $og['concepto'] ?? '' }}</td><td class="text-right">${{ $n($og['importe'] ?? 0) }}</td></tr>
            @endforeach
            <tr style="background:#fafafa"><td class="bold">Total gastos de operación</td><td class="text-right bold">${{ $n($totalGastos) }}</td></tr>
        </tbody>
    </table>
</div>

{{-- UTILIDAD --}}
<div class="seccion">
    <div class="seccion-titulo">III. Utilidad del Negocio</div>
    <table>
        <thead><tr><th>Concepto</th><th class="text-right">Importe mensual ($)</th></tr></thead>
        <tbody>
            <tr><td>Utilidad bruta</td><td class="text-right">${{ $n($utilBruta) }}</td></tr>
            <tr><td>(-) Total gastos de operación</td><td class="text-right">${{ $n($totalGastos) }}</td></tr>
            <tr style="background:#e8f5e9"><td class="bold">Utilidad antes de impuestos</td><td class="text-right bold">${{ $n($utilAnteImp) }}</td></tr>
            <tr><td>(-) Impuestos</td><td class="text-right">${{ $n($impuestos) }}</td></tr>
            <tr style="background:#006847;color:#fff"><td class="bold">UTILIDAD NETA MENSUAL</td><td class="text-right bold">${{ $n($utilNeta) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="aviso">
    Nota: Este estado de ingresos y egresos fue elaborado por el solicitante con base en la operación real de su negocio. El IYEM podrá verificar la información proporcionada.
</div>

<div class="firmas" style="margin-top:16px">
    <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $solicitud->nombre_completo }}</div><div class="firma-etiqueta">Nombre y Firma del Solicitante</div></div></div>
    <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
    <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Vo.Bo. Coordinación CREA</div></div></div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento para uso interno, no transferible.</div>
</div>
</body>
</html>

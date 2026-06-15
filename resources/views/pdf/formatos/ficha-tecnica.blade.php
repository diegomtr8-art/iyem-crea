{{-- F-PR-OCC-04 R00 — Ficha Técnica --}}
@php
    $codigo = 'F-PR-OCC-04 R00';
    $titulo = 'Ficha Técnica';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $neg    = $wizard['datos_negocio_ext'] ?? [];
    $prov   = $wizard['proveedores'] ?? [];
    $clien  = $wizard['clientes'] ?? [];
    $deudas = $wizard['deudas_negocio'] ?? [];
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

{{-- ANTECEDENTES DEL NEGOCIO --}}
<div class="seccion">
    <div class="seccion-titulo">I. Antecedentes del Negocio</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre del solicitante</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">Nombre comercial del negocio</div><div class="campo-valor">{{ $val($neg['nombre_comercial'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Municipio de la empresa</div><div class="campo-valor">{{ $val($neg['municipio_empresa'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Giro / Actividad específica</div><div class="campo-valor">{{ $val($solicitud->giro_comercial) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Fecha de inicio del negocio</div><div class="campo-valor">{{ $val($neg['fecha_inicio_negocio'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Régimen fiscal</div><div class="campo-valor">{{ $val($neg['regimen_fiscal'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Propiedad intelectual / patente</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ ($neg['propiedad_intelectual'] ?? false) ? 'check-si' : '' }}">{{ ($neg['propiedad_intelectual'] ?? false) ? '✓' : '' }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !($neg['propiedad_intelectual'] ?? false) ? 'check-si' : '' }}">{{ !($neg['propiedad_intelectual'] ?? false) ? '✓' : '' }}</span> No</div>
                </div>
                @if($neg['propiedad_intelectual'] ?? false)<div style="font-size:8px">Detalle: {{ $neg['detalle_pi'] ?? '' }}</div>@endif
            </div>
            <div class="campo"><div class="campo-label">Monto de ventas mensual aprox.</div><div class="campo-valor bold">${{ number_format((float)($neg['ventas_mensuales'] ?? 0), 2) }}</div></div>
        </div>
    </div>
</div>

{{-- DESCRIPCIÓN DEL NEGOCIO --}}
<div class="seccion">
    <div class="seccion-titulo">II. Descripción del Negocio</div>
    <div class="campo"><div class="campo-label">Descripción general</div><div class="campo-valor-box" style="min-height:36px">{{ $val($solicitud->descripcion_negocio) }}</div></div>
    <div class="campo mt-2"><div class="campo-label">Descripción del proceso de operación</div><div class="campo-valor-box" style="min-height:28px">{{ $val($neg['proceso_operacion'] ?? '') }}</div></div>
    <div class="grid-2" style="margin-top:6px">
        <div class="col"><div class="campo"><div class="campo-label">Mobiliario y/o maquinaria</div><div class="campo-valor-box">{{ $val($neg['mobiliario'] ?? '') }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Recursos humanos (funciones)</div><div class="campo-valor-box">{{ $val($neg['recursos_humanos'] ?? '') }}</div></div></div>
    </div>
    @if($wizard['productos'] ?? '')
    <div class="campo mt-2"><div class="campo-label">Productos / Servicios</div><div class="campo-valor-box">{{ $wizard['productos'] }}</div></div>
    @endif
    @if($wizard['distribucion'] ?? '')
    <div class="campo mt-2"><div class="campo-label">Distribución / Sectores</div><div class="campo-valor-box">{{ $wizard['distribucion'] }}</div></div>
    @endif
</div>

{{-- PROVEEDORES --}}
<div class="seccion">
    <div class="seccion-titulo">III. Principales Proveedores</div>
    <table>
        <thead>
            <tr>
                <th>Nombre del proveedor</th>
                <th>Antigüedad</th>
                <th>Insumo / producto</th>
                <th class="text-right">Compras mensuales ($)</th>
                <th>Política de compra</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prov as $p)
            <tr>
                <td>{{ $p['nombre'] ?? '' }}</td>
                <td>{{ $p['antiguedad'] ?? '' }}</td>
                <td>{{ $p['insumo'] ?? '' }}</td>
                <td class="text-right">${{ number_format((float)($p['compras_mensuales'] ?? 0), 2) }}</td>
                <td>{{ $p['politica'] ?? '' }}</td>
            </tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- CLIENTES --}}
<div class="seccion">
    <div class="seccion-titulo">IV. Principales Clientes</div>
    <table>
        <thead>
            <tr>
                <th>Nombre del cliente</th>
                <th>Sector / Mercado</th>
                <th class="text-right">Ventas mensuales aprox. ($)</th>
                <th>Política de venta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clien as $c)
            <tr>
                <td>{{ $c['nombre'] ?? '' }}</td>
                <td>{{ $c['sector'] ?? '' }}</td>
                <td class="text-right">${{ number_format((float)($c['ventas_mensuales'] ?? 0), 2) }}</td>
                <td>{{ $c['politica'] ?? '' }}</td>
            </tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- DEUDAS --}}
<div class="seccion">
    <div class="seccion-titulo">V. Deudas y Obligaciones del Negocio</div>
    <table>
        <thead>
            <tr>
                <th>Institución / Acreedor</th>
                <th class="text-right">Monto ($)</th>
                <th>Vencimiento</th>
                <th>Garantía</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deudas as $d)
            <tr>
                <td>{{ $d['nombre'] ?? '' }}</td>
                <td class="text-right">${{ number_format((float)($d['monto'] ?? 0), 2) }}</td>
                <td>{{ $d['vencimiento'] ?? '' }}</td>
                <td>{{ $d['garantia'] ?? '' }}</td>
            </tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="firmas">
    <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $solicitud->nombre_completo }}</div><div class="firma-etiqueta">Nombre y Firma del Solicitante</div></div></div>
    <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
    <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Vo.Bo. Coordinación CREA</div></div></div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento para uso interno, no transferible.</div>
</div>
</body>
</html>

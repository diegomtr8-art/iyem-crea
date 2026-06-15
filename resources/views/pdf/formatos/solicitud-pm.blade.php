{{-- F-PR-OCC-03 R00 — Solicitud de Crédito Persona Moral --}}
@php
    $codigo = 'F-PR-OCC-03 R00';
    $titulo = 'Solicitud de Crédito — Persona Moral';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $ext    = $wizard['datos_personales_ext'] ?? [];
    $neg    = $wizard['datos_negocio_ext'] ?? [];
    $moral  = $wizard['datos_persona_moral'] ?? [];
    $dest   = $wizard['destino_credito_tabla'] ?? [];
    $apoyos = $wizard['apoyos_gobierno'] ?? [];
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
    <div class="seccion-titulo">I. Datos de la Persona Moral</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Razón social</div><div class="campo-valor">{{ $val($moral['razon_social'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">RFC de la empresa</div><div class="campo-valor" style="font-family:monospace">{{ $val($moral['rfc'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de constitución</div><div class="campo-valor">{{ $val($moral['fecha_constitucion'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Giro / Actividad</div><div class="campo-valor">{{ $val($solicitud->giro_comercial) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Municipio</div><div class="campo-valor">{{ $val($moral['municipio'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Colonia</div><div class="campo-valor">{{ $val($moral['colonia'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">C.P.</div><div class="campo-valor">{{ $val($moral['cp'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Teléfono</div><div class="campo-valor">{{ $val($moral['telefono'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Correo</div><div class="campo-valor">{{ $val($moral['correo'] ?? '') }}</div></div>
        </div>
    </div>
    <div class="campo"><div class="campo-label">Dirección</div><div class="campo-valor">{{ $val($moral['domicilio'] ?? '') }}</div></div>
</div>

<div class="seccion">
    <div class="seccion-titulo">II. Datos del Representante Legal</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre del representante legal</div><div class="campo-valor">{{ $val($moral['rep_legal'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">CURP del representante</div><div class="campo-valor" style="font-family:monospace">{{ $val($moral['curp_rep'] ?? '') }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre completo (titular)</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">CURP (titular)</div><div class="campo-valor" style="font-family:monospace">{{ $val($solicitud->curp) }}</div></div>
        </div>
    </div>
    <div class="grid-3" style="margin-top:4px">
        <div class="col"><div class="campo"><div class="campo-label">Teléfono celular</div><div class="campo-valor">{{ $val($solicitud->telefono) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Correo</div><div class="campo-valor">{{ $val($solicitud->correo) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Municipio de residencia</div><div class="campo-valor">{{ $val($solicitud->municipio) }}</div></div></div>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">III. Datos del Proyecto / Negocio</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre comercial</div><div class="campo-valor">{{ $val($neg['nombre_comercial'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de inicio del negocio</div><div class="campo-valor">{{ $val($neg['fecha_inicio_negocio'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Régimen fiscal</div><div class="campo-valor">{{ $val($neg['regimen_fiscal'] ?? '') }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Alta en SAT</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $solicitud->alta_sat ? 'check-si' : '' }}">{{ $solicitud->alta_sat ? '✓' : '' }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !$solicitud->alta_sat ? 'check-si' : '' }}">{{ !$solicitud->alta_sat ? '✓' : '' }}</span> No</div>
                </div>
            </div>
            <div class="campo"><div class="campo-label">Propiedad intelectual / patente</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ ($neg['propiedad_intelectual'] ?? false) ? 'check-si' : '' }}">{{ ($neg['propiedad_intelectual'] ?? false) ? '✓' : '' }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !($neg['propiedad_intelectual'] ?? false) ? 'check-si' : '' }}">{{ !($neg['propiedad_intelectual'] ?? false) ? '✓' : '' }}</span> No</div>
                </div>
            </div>
            <div class="campo"><div class="campo-label">Monto ventas mensual aprox.</div><div class="campo-valor">${{ number_format((float)($neg['ventas_mensuales'] ?? 0), 2) }}</div></div>
        </div>
    </div>
    <div class="campo mt-2"><div class="campo-label">Descripción del negocio</div><div class="campo-valor-box">{{ $val($solicitud->descripcion_negocio) }}</div></div>
</div>

<div class="seccion">
    <div class="seccion-titulo">IV. Destino del Crédito</div>
    <table>
        <thead><tr><th>Concepto</th><th class="text-right">Importe cotizado ($)</th></tr></thead>
        <tbody>
            @forelse($dest as $item)
            <tr><td>{{ $item['concepto'] ?? '' }}</td><td class="text-right">${{ number_format((float)($item['importe'] ?? 0), 2) }}</td></tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="grid-3" style="margin-top:8px">
        <div class="col"><div class="campo"><div class="campo-label">Importe total del proyecto</div><div class="campo-valor bold">${{ number_format((float)($wizard['importe_total_proyecto'] ?? 0), 2) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Monto solicitado</div><div class="campo-valor bold">${{ number_format((float)($solicitud->monto_solicitado ?? 0), 2) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Plazo</div><div class="campo-valor bold">{{ $solicitud->plazo_meses }} meses</div></div></div>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">V. Créditos y/o Apoyos de Gobierno Previos</div>
    <table>
        <thead><tr><th>Dependencia</th><th>Destino del recurso</th><th class="text-right">Monto ($)</th></tr></thead>
        <tbody>
            @forelse($apoyos as $a)
            <tr><td>{{ $a['dependencia'] ?? '' }}</td><td>{{ $a['destino'] ?? '' }}</td><td class="text-right">${{ number_format((float)($a['monto'] ?? 0), 2) }}</td></tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="seccion">
    <div class="texto-declaracion">
        Bajo protesta de decir verdad, manifiesto que los datos asentados en esta solicitud son correctos y que la persona moral a quien represento conoce y acepta las condiciones del Programa CREA del Instituto Yucateco de Emprendedores, comprometiéndome a presentar la documentación requerida y a sujetarme a las Reglas de Operación vigentes.
    </div>
    <div class="firmas">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $val($moral['rep_legal'] ?? $solicitud->nombre_completo) }}</div><div class="firma-etiqueta">Nombre y Firma del Representante Legal</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Sello de Recepción</div></div></div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento para uso interno, no transferible.</div>
</div>
</body>
</html>

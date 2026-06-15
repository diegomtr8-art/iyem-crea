{{-- F-PR-OCC-01 R00 — Solicitud de Crédito Persona Física Artesanal --}}
@php
    $codigo = 'F-PR-OCC-01 R00';
    $titulo = 'Solicitud de Crédito — Persona Física Artesanal';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $ext    = $wizard['datos_personales_ext'] ?? [];
    $neg    = $wizard['datos_negocio_ext'] ?? [];
    $dest   = $wizard['destino_credito_tabla'] ?? [];
    $apoyos = $wizard['apoyos_gobierno'] ?? [];
    $si = fn($v) => $v ? '✓' : '';
    $no = fn($v) => !$v ? '✓' : '';
    $val = fn($v) => $v ?? '';
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

{{-- DATOS DEL SOLICITANTE --}}
<div class="seccion">
    <div class="seccion-titulo">I. Datos del Solicitante</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre completo</div><div class="campo-valor">{{ $val($solicitud->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">CURP</div><div class="campo-valor" style="font-family:monospace;letter-spacing:1px">{{ $val($solicitud->curp) }}</div></div>
            <div class="campo"><div class="campo-label">RFC</div><div class="campo-valor" style="font-family:monospace">{{ $val($solicitud->rfc) }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de nacimiento</div><div class="campo-valor">{{ $solicitud->fecha_nacimiento?->format('d/m/Y') }}</div></div>
            <div class="campo"><div class="campo-label">Lugar de nacimiento</div><div class="campo-valor">{{ $val($ext['lugar_nacimiento'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Edad</div><div class="campo-valor">{{ $solicitud->fecha_nacimiento ? now()->diffInYears($solicitud->fecha_nacimiento) : '' }} años</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Sexo</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $solicitud->sexo === 'M' ? 'check-si' : '' }}">{{ $solicitud->sexo === 'M' ? '✓' : '' }}</span> Masculino</div>
                    <div class="check-item"><span class="check-box {{ $solicitud->sexo === 'F' ? 'check-si' : '' }}">{{ $solicitud->sexo === 'F' ? '✓' : '' }}</span> Femenino</div>
                </div>
            </div>
            <div class="campo"><div class="campo-label">Habla maya</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $solicitud->mayahablante ? 'check-si' : '' }}">{{ $si($solicitud->mayahablante) }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !$solicitud->mayahablante ? 'check-si' : '' }}">{{ $no($solicitud->mayahablante) }}</span> No</div>
                </div>
            </div>
            <div class="campo"><div class="campo-label">Discapacidad</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $solicitud->discapacidad ? 'check-si' : '' }}">{{ $si($solicitud->discapacidad) }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !$solicitud->discapacidad ? 'check-si' : '' }}">{{ $no($solicitud->discapacidad) }}</span> No</div>
                </div>
                @if($solicitud->discapacidad && ($ext['discapacidad_tipo'] ?? ''))
                <div class="campo-valor" style="font-size:8px;margin-top:2px">Especifique: {{ $ext['discapacidad_tipo'] }}</div>
                @endif
            </div>
            <div class="campo"><div class="campo-label">Estado civil</div><div class="campo-valor">{{ $val($ext['estado_civil'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Régimen matrimonial</div><div class="campo-valor">{{ $val($ext['regimen_matrimonial'] ?? '') }}</div></div>
        </div>
    </div>
</div>

{{-- DOMICILIO --}}
<div class="seccion">
    <div class="seccion-titulo">II. Domicilio</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Calle y número</div><div class="campo-valor">{{ $val($solicitud->direccion) }}</div></div>
            <div class="campo"><div class="campo-label">Colonia</div><div class="campo-valor">{{ $val($ext['colonia'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Código Postal</div><div class="campo-valor">{{ $val($ext['cp'] ?? '') }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Municipio</div><div class="campo-valor">{{ $val($solicitud->municipio) }}</div></div>
            <div class="campo"><div class="campo-label">Domicilio</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ ($ext['domicilio_propio'] ?? true) ? 'check-si' : '' }}">{{ ($ext['domicilio_propio'] ?? true) ? '✓' : '' }}</span> Propio</div>
                    <div class="check-item"><span class="check-box {{ !($ext['domicilio_propio'] ?? true) ? 'check-si' : '' }}">{{ !($ext['domicilio_propio'] ?? true) ? '✓' : '' }}</span> Rentado</div>
                </div>
            </div>
            @if(!($ext['domicilio_propio'] ?? true))
            <div class="campo"><div class="campo-label">Renta mensual</div><div class="campo-valor">${{ number_format((float)($ext['renta_mensual'] ?? 0), 2) }}</div></div>
            @endif
        </div>
    </div>
    <div class="grid-3" style="margin-top:4px">
        <div class="col"><div class="campo"><div class="campo-label">Teléfono celular</div><div class="campo-valor">{{ $val($solicitud->telefono) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Teléfono fijo</div><div class="campo-valor">{{ $val($ext['telefono_fijo'] ?? '') }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Correo electrónico</div><div class="campo-valor">{{ $val($solicitud->correo) }}</div></div></div>
    </div>
</div>

{{-- DATOS CÓNYUGE (si aplica) --}}
@if($ext['nombre_conyuge'] ?? '')
<div class="seccion">
    <div class="seccion-titulo">III. Datos del Cónyuge</div>
    <div class="grid-2">
        <div class="col"><div class="campo"><div class="campo-label">Nombre completo</div><div class="campo-valor">{{ $ext['nombre_conyuge'] }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">CURP</div><div class="campo-valor" style="font-family:monospace">{{ $val($ext['curp_conyuge'] ?? '') }}</div></div></div>
    </div>
</div>
@endif

{{-- DATOS DEL NEGOCIO --}}
<div class="seccion">
    <div class="seccion-titulo">IV. Datos del Negocio / Proyecto</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre comercial</div><div class="campo-valor">{{ $val($neg['nombre_comercial'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Giro / Actividad</div><div class="campo-valor">{{ $val($solicitud->giro_comercial) }}</div></div>
            <div class="campo"><div class="campo-label">RFC del negocio</div><div class="campo-valor" style="font-family:monospace">{{ $val($solicitud->rfc) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Municipio del negocio</div><div class="campo-valor">{{ $val($neg['municipio_empresa'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de inicio del negocio</div><div class="campo-valor">{{ $val($neg['fecha_inicio_negocio'] ?? '') }}</div></div>
            <div class="campo"><div class="campo-label">Dado de alta en SAT</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $solicitud->alta_sat ? 'check-si' : '' }}">{{ $solicitud->alta_sat ? '✓' : '' }}</span> Sí</div>
                    <div class="check-item"><span class="check-box {{ !$solicitud->alta_sat ? 'check-si' : '' }}">{{ !$solicitud->alta_sat ? '✓' : '' }}</span> No</div>
                </div>
            </div>
        </div>
    </div>
    <div class="campo mt-2"><div class="campo-label">Descripción del negocio o proyecto</div><div class="campo-valor-box">{{ $val($solicitud->descripcion_negocio) }}</div></div>
</div>

{{-- DESTINO DEL CRÉDITO --}}
<div class="seccion">
    <div class="seccion-titulo">V. Destino del Crédito</div>
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
        <div class="col"><div class="campo"><div class="campo-label">Monto a solicitar</div><div class="campo-valor bold">${{ number_format((float)($solicitud->monto_solicitado ?? 0), 2) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Plazo solicitado</div><div class="campo-valor bold">{{ $solicitud->plazo_meses }} meses</div></div></div>
    </div>
</div>

{{-- APOYOS GOBIERNO --}}
<div class="seccion">
    <div class="seccion-titulo">VI. Créditos y/o Apoyos de Gobierno Previos</div>
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

{{-- REFERENCIA PERSONAL --}}
<div class="seccion">
    <div class="seccion-titulo">VII. Referencia Personal (No familiar)</div>
    <div class="grid-3">
        <div class="col"><div class="campo"><div class="campo-label">Nombre</div><div class="campo-valor">{{ $val($ext['referencia_nombre'] ?? '') }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Teléfono</div><div class="campo-valor">{{ $val($ext['referencia_telefono'] ?? '') }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Código Postal</div><div class="campo-valor">{{ $val($ext['referencia_cp'] ?? '') }}</div></div></div>
    </div>
</div>

{{-- FIRMA Y DECLARACIÓN --}}
<div class="seccion">
    <div class="texto-declaracion">
        Bajo protesta de decir verdad, manifiesto que los datos asentados en esta solicitud son correctos y que conozco y acepto las condiciones del Programa CREA del Instituto Yucateco de Emprendedores, comprometiéndome a presentar la documentación requerida y a sujetarme a las Reglas de Operación vigentes.
    </div>
    <div class="firmas">
        <div class="firma-col">
            <div class="firma-linea">
                <div class="bold">{{ $solicitud->nombre_completo }}</div>
                <div class="firma-etiqueta">Nombre y Firma del Solicitante</div>
            </div>
        </div>
        <div class="firma-col">
            <div class="firma-linea">
                <div>&nbsp;</div>
                <div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div>
            </div>
        </div>
        <div class="firma-col">
            <div class="firma-linea">
                <div>&nbsp;</div>
                <div class="firma-etiqueta">Sello de Recepción</div>
            </div>
        </div>
    </div>
</div>

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento para uso interno, no transferible.</div>
</div>
</body>
</html>

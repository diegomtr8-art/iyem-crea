{{-- F-PR-OCC-07 R00 — Declaración de Bienes Patrimoniales Aval (Emprendedores / Sustentable) --}}
@php
    $codigo = 'F-PR-OCC-07 R00';
    $titulo = 'Declaración de Bienes Patrimoniales — Aval';
    $folio  = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);
    $av     = $aval ?? null;
    $bienes = $av?->bienes_inmuebles ?? [];
    $hipot  = $av?->hipotecas_creditos ?? [];
    $otras  = $av?->otras_deudas ?? [];
    $refs   = $av?->referencias_personales ?? [];
    $n      = fn($v) => number_format((float)($v ?? 0), 2);
    $si     = fn($v) => $v ? '✓' : '';
    $no     = fn($v) => !$v ? '✓' : '';
    $val    = fn($v) => $v ?? '';
    $totalDeudas = array_sum(array_column($hipot, 'monto')) + array_sum(array_column($otras, 'monto'));
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

@if(!$av)
<div style="text-align:center;padding:20px;color:#999">No se proporcionaron datos del aval.</div>
@else

<div class="seccion">
    <div class="seccion-titulo">I. Datos Personales del Aval</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Nombre completo</div><div class="campo-valor">{{ $val($av->nombre_completo) }}</div></div>
            <div class="campo"><div class="campo-label">CURP</div><div class="campo-valor" style="font-family:monospace;letter-spacing:1px">{{ $val($av->curp) }}</div></div>
            <div class="campo"><div class="campo-label">RFC</div><div class="campo-valor" style="font-family:monospace">{{ $val($av->rfc) }}</div></div>
            <div class="campo"><div class="campo-label">Fecha de nacimiento</div><div class="campo-valor">{{ $av->fecha_nacimiento?->format('d/m/Y') }}</div></div>
            <div class="campo"><div class="campo-label">Edad</div><div class="campo-valor">{{ $val($av->edad) }} años</div></div>
            <div class="campo"><div class="campo-label">Sexo</div>
                <div class="check-row">
                    <div class="check-item"><span class="check-box {{ $av->sexo === 'M' ? 'check-si' : '' }}">{{ $av->sexo === 'M' ? '✓' : '' }}</span> M</div>
                    <div class="check-item"><span class="check-box {{ $av->sexo === 'F' ? 'check-si' : '' }}">{{ $av->sexo === 'F' ? '✓' : '' }}</span> F</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Correo electrónico</div><div class="campo-valor">{{ $val($av->correo) }}</div></div>
            <div class="campo"><div class="campo-label">Teléfono celular</div><div class="campo-valor">{{ $val($av->telefono_celular) }}</div></div>
            <div class="campo"><div class="campo-label">Teléfono fijo</div><div class="campo-valor">{{ $val($av->telefono_fijo) }}</div></div>
            <div class="campo"><div class="campo-label">Estado civil</div><div class="campo-valor">{{ $val($av->estado_civil) }}</div></div>
            <div class="campo"><div class="campo-label">Régimen matrimonial</div><div class="campo-valor">{{ $val($av->regimen_matrimonial) }}</div></div>
            <div class="campo"><div class="campo-label">Nombre del cónyuge</div><div class="campo-valor">{{ $val($av->nombre_conyuge) }}</div></div>
            <div class="campo"><div class="campo-label">Dependientes económicos</div><div class="campo-valor">{{ $val($av->dependientes_economicos) }}</div></div>
        </div>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">II. Domicilio del Aval</div>
    <div class="campo"><div class="campo-label">Dirección</div><div class="campo-valor">{{ $val($av->domicilio) }}</div></div>
    <div class="grid-4" style="margin-top:4px">
        <div class="col"><div class="campo"><div class="campo-label">Colonia</div><div class="campo-valor">{{ $val($av->colonia) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">C.P.</div><div class="campo-valor">{{ $val($av->cp) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Municipio residencia</div><div class="campo-valor">{{ $val($av->municipio_residencia) }}</div></div></div>
        <div class="col"><div class="campo"><div class="campo-label">Municipio nacimiento</div><div class="campo-valor">{{ $val($av->municipio_nacimiento) }}</div></div></div>
    </div>
    <div class="campo" style="margin-top:4px"><div class="campo-label">Domicilio</div>
        <div class="check-row">
            <div class="check-item"><span class="check-box {{ $av->domicilio_propio ? 'check-si' : '' }}">{{ $si($av->domicilio_propio) }}</span> Propio</div>
            <div class="check-item"><span class="check-box {{ !$av->domicilio_propio ? 'check-si' : '' }}">{{ $no($av->domicilio_propio) }}</span> Rentado</div>
            @if(!$av->domicilio_propio)<div class="check-item">Renta mensual: <strong>${{ $n($av->renta_mensual) }}</strong></div>@endif
        </div>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">III. Situación Laboral del Aval</div>
    <div class="grid-2">
        <div class="col">
            <div class="campo"><div class="campo-label">Ocupación</div><div class="campo-valor">{{ $val($av->ocupacion) }}</div></div>
            <div class="campo"><div class="campo-label">Lugar de trabajo</div><div class="campo-valor">{{ $val($av->lugar_laboral) }}</div></div>
        </div>
        <div class="col">
            <div class="campo"><div class="campo-label">Antigüedad laboral</div><div class="campo-valor">{{ $val($av->antiguedad_laboral) }}</div></div>
            <div class="campo"><div class="campo-label">Fecha inicio actividades laborales</div><div class="campo-valor">{{ $av->fecha_inicio_actividades?->format('d/m/Y') }}</div></div>
        </div>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">IV. Bienes y Derechos (Propiedades Inmobiliarias)</div>
    <table>
        <thead><tr><th>Descripción del inmueble</th><th>Ubicación</th><th class="text-right">Valor estimado ($)</th></tr></thead>
        <tbody>
            @forelse($bienes as $b)
            <tr><td>{{ $b['descripcion'] ?? '' }}</td><td>{{ $b['ubicacion'] ?? '' }}</td><td class="text-right">${{ $n($b['valor'] ?? 0) }}</td></tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="seccion">
    <div class="seccion-titulo">V. Deudas y Obligaciones</div>
    <div class="seccion-titulo-gold" style="margin-top:6px">Hipotecas y Créditos Bancarios</div>
    <table>
        <thead><tr><th>Institución</th><th class="text-right">Monto ($)</th><th>Vencimiento</th><th>Garantía</th></tr></thead>
        <tbody>
            @forelse($hipot as $h)
            <tr><td>{{ $h['nombre'] ?? '' }}</td><td class="text-right">${{ $n($h['monto'] ?? 0) }}</td><td>{{ $h['vencimiento'] ?? '' }}</td><td>{{ $h['garantia'] ?? '' }}</td></tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="seccion-titulo-gold" style="margin-top:6px">Otras Deudas</div>
    <table>
        <thead><tr><th>Acreedor</th><th class="text-right">Monto ($)</th><th>Vencimiento</th></tr></thead>
        <tbody>
            @forelse($otras as $od)
            <tr><td>{{ $od['nombre'] ?? '' }}</td><td class="text-right">${{ $n($od['monto'] ?? 0) }}</td><td>{{ $od['vencimiento'] ?? '' }}</td></tr>
            @empty
            <tr class="fila-vacia"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforelse
            <tr style="background:#006847;color:#fff"><td class="bold">Total Deudas</td><td class="text-right bold">${{ $n($totalDeudas) }}</td><td></td></tr>
        </tbody>
    </table>
</div>

@if(count($refs) > 0)
<div class="seccion">
    <div class="seccion-titulo">VI. Referencias Personales del Aval (No familiares)</div>
    <table>
        <thead><tr><th>Nombre</th><th>Teléfono</th><th>C.P.</th></tr></thead>
        <tbody>
            @foreach($refs as $r)
            <tr><td>{{ $r['nombre'] ?? '' }}</td><td>{{ $r['telefono'] ?? '' }}</td><td>{{ $r['cp'] ?? '' }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="seccion" style="margin-top:10px">
    <div class="texto-declaracion">
        Bajo protesta de decir verdad, el suscrito declara que la información contenida en este documento es correcta y verdadera, comprometiéndose a ser aval solidario del crédito solicitado por <strong>{{ $solicitud->nombre_completo }}</strong> ante el Programa CREA del Instituto Yucateco de Emprendedores. El aval declara no tener parentesco hasta 2° grado con el solicitante y que cuenta con domicilio comprobable en el Estado de Yucatán.
    </div>
    <div class="firmas">
        <div class="firma-col"><div class="firma-linea"><div class="bold">{{ $av->nombre_completo }}</div><div class="firma-etiqueta">Nombre y Firma del Aval</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Nombre y Firma del Asesor CREA</div></div></div>
        <div class="firma-col"><div class="firma-linea"><div>&nbsp;</div><div class="firma-etiqueta">Sello de Recepción</div></div></div>
    </div>
</div>
@endif

<div class="footer">{{ $codigo }} | Programa CREA — IYEM | Generado: {{ $fecha }} | Documento para uso interno, no transferible.</div>
</div>
</body>
</html>

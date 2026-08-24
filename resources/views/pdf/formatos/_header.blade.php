{{-- Header institucional IYEM reutilizable en todos los formatos --}}
@php
    $escudoPath = public_path('img/logos/escudo-yucatan.png');
    $iyemPath   = public_path('img/logos/logo-iyem.png');
@endphp
<table class="header-table">
    <tr>
        <td class="header-col-izq">
            @if(file_exists($escudoPath))
                <img src="{{ $escudoPath }}" class="header-logo">
            @endif
        </td>
        <td class="header-center">
            <div class="iyem-nombre">Instituto Yucateco de Emprendedores</div>
            <div class="iyem-sub">IYEM — Gobierno del Estado de Yucatán</div>
            <div class="iyem-title-crea">Programa CREA</div>
        </td>
        <td class="header-col-der">
            @if(file_exists($iyemPath))
                <img src="{{ $iyemPath }}" class="header-logo">
            @endif
            <div class="doc-box">
                <div class="doc-codigo">{{ $codigo }}</div>
                <div class="doc-titulo">{{ $titulo }}</div>
                <div class="doc-fecha">Fecha: {{ $fecha }}</div>
                @if(isset($folio))
                <div class="doc-folio">Folio: {{ $folio }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>
<div class="header-divider"></div>

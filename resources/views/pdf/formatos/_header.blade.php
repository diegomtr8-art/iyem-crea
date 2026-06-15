{{-- Header institucional IYEM reutilizable en todos los formatos --}}
<div class="header">
    <div class="header-left">
        <div class="iyem-nombre">Instituto Yucateco de Emprendedores</div>
        <div class="iyem-sub">IYEM — Gobierno del Estado de Yucatán</div>
        <div class="iyem-prog">Programa CREA</div>
    </div>
    <div class="header-right">
        <div class="doc-codigo">{{ $codigo }}</div>
        <div class="doc-titulo">{{ $titulo }}</div>
        <div class="doc-fecha">Fecha: {{ $fecha }}</div>
        @if(isset($folio))
        <div class="doc-folio">Folio: {{ $folio }}</div>
        @endif
    </div>
</div>
<div class="header-divider"></div>

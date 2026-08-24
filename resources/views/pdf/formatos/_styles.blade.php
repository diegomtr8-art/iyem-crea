{{-- Estilos CSS compartidos para todos los formatos IYEM --}}
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9.5px; color: #1a1a1a; background: #fff; }
    .page { padding: 22px 28px; }

    /* Header — tabla de 3 columnas: escudo | nombre institucional | logo IYEM + doc-box */
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .header-table td { border: none; padding: 0; vertical-align: middle; }
    .header-col-izq { width: 60px; text-align: left; }
    .header-col-der { width: 90px; text-align: right; }
    .header-logo { width: 55px; height: auto; }
    .header-center { text-align: center; }
    .iyem-nombre { font-size: 11px; font-weight: bold; color: #006847; text-transform: uppercase; letter-spacing: 0.5px; }
    .iyem-sub { font-size: 8px; color: #555; margin-top: 1px; }
    .iyem-prog { font-size: 8px; color: #006847; font-weight: bold; margin-top: 1px; }
    .iyem-title-crea { font-size: 14px; font-weight: bold; color: #006847; letter-spacing: 1px; margin-top: 1px; }
    .doc-box { text-align: right; background: #f5f5f5; border: 1px solid #ddd; padding: 4px 6px; border-radius: 3px; margin-top: 4px; }
    .doc-codigo { font-size: 8px; color: #888; }
    .doc-titulo { font-size: 10px; font-weight: bold; color: #1a1a1a; margin-top: 2px; }
    .doc-fecha { font-size: 8px; color: #666; margin-top: 2px; }
    .doc-folio { font-size: 8.5px; color: #006847; font-weight: bold; margin-top: 1px; }
    .header-divider { border-top: 2.5px solid #006847; margin: 6px 0 12px 0; }

    /* Secciones */
    .seccion { margin-bottom: 14px; }
    .seccion-titulo {
        background: #006847;
        color: #fff;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 3px 8px;
        margin-bottom: 6px;
    }
    .seccion-titulo-gold {
        background: #C89B2A;
        color: #fff;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 3px 8px;
        margin-bottom: 6px;
    }

    /* Grid */
    .grid-2 { display: table; width: 100%; }
    .grid-2 .col { display: table-cell; vertical-align: top; width: 50%; padding-right: 10px; }
    .grid-2 .col:last-child { padding-right: 0; }
    .grid-3 { display: table; width: 100%; }
    .grid-3 .col { display: table-cell; vertical-align: top; width: 33.33%; padding-right: 8px; }
    .grid-3 .col:last-child { padding-right: 0; }
    .grid-4 { display: table; width: 100%; }
    .grid-4 .col { display: table-cell; vertical-align: top; width: 25%; padding-right: 8px; }
    .grid-4 .col:last-child { padding-right: 0; }

    /* Campo */
    .campo { margin-bottom: 5px; }
    .campo-label { font-size: 7.5px; color: #006847; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    .campo-valor {
        font-size: 9.5px;
        border-bottom: 1px solid #ccc;
        min-height: 14px;
        padding-bottom: 1px;
        padding-top: 1px;
        color: #1a1a1a;
        word-break: break-word;
    }
    .campo-valor-box {
        font-size: 9px;
        border: 1px solid #ccc;
        min-height: 28px;
        padding: 3px 4px;
        color: #1a1a1a;
        word-break: break-word;
    }

    /* Checkbox */
    .check-row { display: table; width: 100%; margin: 2px 0; }
    .check-item { display: table-cell; padding-right: 12px; font-size: 9px; white-space: nowrap; }
    .check-box { display: inline-block; width: 10px; height: 10px; border: 1px solid #555; vertical-align: middle; text-align: center; font-size: 8px; line-height: 10px; margin-right: 3px; }
    .check-si { background: #006847; color: #fff; }

    /* Tablas */
    table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin: 4px 0; }
    thead tr { background: #006847; color: #fff; }
    th { padding: 3px 5px; text-align: left; font-size: 8px; font-weight: bold; }
    td { padding: 3px 5px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
    tr.fila-vacia td { background: #f9fafb; min-height: 14px; }
    tr:nth-child(even) td { background: #f7f7f7; }

    /* Footer */
    .footer { margin-top: 16px; border-top: 1px solid #ddd; padding-top: 6px; font-size: 7.5px; color: #999; text-align: center; }

    /* Firmas */
    .firmas { display: table; width: 100%; margin-top: 20px; }
    .firma-col { display: table-cell; text-align: center; width: 33.33%; padding: 0 8px; }
    .firma-linea { border-top: 1px solid #555; margin-top: 24px; padding-top: 3px; font-size: 8px; }
    .firma-etiqueta { font-size: 7.5px; color: #666; }

    /* Declaración */
    .texto-declaracion { font-size: 8.5px; color: #333; line-height: 1.5; margin-bottom: 10px; text-align: justify; }

    /* Aviso */
    .aviso { font-size: 7.5px; color: #666; background: #f5f5f5; border: 1px solid #ddd; padding: 4px 6px; margin-top: 8px; }

    .bold { font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .mt-2 { margin-top: 8px; }
    .color-verde { color: #006847; }
</style>

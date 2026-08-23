{{--
    Hoja de estilos compartida por todos los reportes PDF del SGA.
    Un solo lugar para el look institucional (color de marca, tipografía,
    tablas, badges de estado) — cada reporte solo agrega, en su propio
    <style>, las reglas que sean específicas de su contenido.
--}}
<style>
    @page { margin: 26px 28px 34px 28px; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #1f2937;
        margin: 0;
    }

    /* ---- Encabezado institucional ---- */
    .sga-header { display: table; width: 100%; border-bottom: 3px solid #3b71ca; padding-bottom: 10px; margin-bottom: 18px; }
    .sga-header .brand, .sga-header .doc { display: table-cell; vertical-align: middle; }
    .sga-header .doc { text-align: right; }
    .sga-header .sistema { font-size: 8.5px; color: #6590d5; letter-spacing: 1.5px; text-transform: uppercase; }
    .sga-header .institucion { font-size: 15px; font-weight: bold; color: #204075; text-transform: uppercase; margin-top: 1px; }
    .sga-header .titulo-doc { font-size: 13px; font-weight: bold; color: #285192; text-transform: uppercase; }
    .sga-header .meta { font-size: 9px; color: #6b7280; margin-top: 3px; line-height: 1.5; }

    /* ---- Barra de contexto (curso / alumno / periodo) ---- */
    .sga-infobar { background: #f1f5fb; border: 1px solid #c7d7f0; padding: 8px 12px; margin-bottom: 16px; font-size: 10px; color: #204075; }
    .sga-infobar strong { color: #183058; }

    /* ---- Tablas ---- */
    table.sga-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.sga-table th { background: #3b71ca; color: #fff; padding: 7px 6px; font-size: 9px; text-transform: uppercase; text-align: left; }
    table.sga-table th.center { text-align: center; }
    table.sga-table td { border: 1px solid #dbe4f3; padding: 6px; font-size: 10px; text-align: left; }
    table.sga-table td.center { text-align: center; }
    table.sga-table tbody tr:nth-child(even) { background: #f7f9fd; }
    table.sga-table tfoot td { font-weight: bold; background: #eef2fb; }

    /* ---- Badges de estado (mismo lenguaje visual en todos los reportes) ---- */
    .sga-badge { display: inline-block; padding: 2px 9px; border-radius: 9px; font-size: 9px; font-weight: bold; }
    .sga-badge-success { background: #d1fae5; color: #065f46; }
    .sga-badge-warning { background: #fef3c7; color: #92400e; }
    .sga-badge-danger { background: #fee2e2; color: #991b1b; }
    .sga-badge-neutral { background: #e5e7eb; color: #374151; }

    /* ---- Pie de página ---- */
    .sga-footer { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 8.5px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 6px; }

    /* ---- Marca de agua opcional ---- */
    .sga-watermark { position: absolute; top: 35%; left: 12%; font-size: 70px; color: rgba(59, 113, 202, 0.06); transform: rotate(-30deg); z-index: -1; white-space: nowrap; }
</style>

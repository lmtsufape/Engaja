<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* ===== Paginação: repete cabeçalho e não corta linha no meio ===== */
        table { page-break-inside: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; }

        /* ===== Cabeçalho padrão de documento (x-pdf.header) ===== */
        .pdf-header { background: #421944; color: #ffffff; padding: 10px 14px; margin-bottom: 14px; }
        .pdf-header__title { font-size: 15px; font-weight: 700; margin: 0 0 2px 0; }
        .pdf-header__subtitle { font-size: 10px; opacity: 0.9; margin-bottom: 2px; }
        .pdf-header__stamp { font-size: 9px; opacity: 0.8; }
        /* Bloco de contexto: desacoplado da faixa, fora dela, em prosa natural */
        .pdf-header__context { background: #fcfaff; border-left: 4px solid #421944; padding: 7px 12px; margin: -8px 0 14px 0; font-size: 10px; line-height: 1.45; color: #374151; page-break-inside: avoid; }
        .pdf-header__context strong { color: #421944; }

        /* ===== Tabela padrão (visual AG-Grid em CSS) ===== */
        .pdf-table { width: 100%; border-collapse: collapse; }
        .pdf-table th { background: #421944; color: #fff; font-size: 10px; font-weight: 700; padding: 5px 6px; text-align: left; }
        .pdf-table th.text-end, .pdf-table td.text-end { text-align: right; }
        .pdf-table th.text-center, .pdf-table td.text-center { text-align: center; }
        .pdf-table td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; font-size: 10px; }
        .pdf-table tbody tr:nth-child(even) td { background: #f9fafb; }
        .pdf-table tbody tr:last-child td { border-bottom: none; }
        .pdf-table .row-index { width: 28px; text-align: center; font-weight: 700; color: #9ca3af; }
        .pdf-table .subtotal-row td { background: #ece3ee; font-weight: 700; }
        .pdf-table--compact th { padding: 3px 5px; }
        .pdf-table--compact td { padding: 2px 5px; }

        @yield('styles')
    </style>
</head>
<body>
    @php
        $headerPath = public_path('images/Alfa-Eja Header.png');
        $footerPath = public_path('images/Alfa-Eja Footer.png');
    @endphp
    @if(file_exists($headerPath))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($headerPath)) }}" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none" aria-hidden="true">
    @endif
    @if(file_exists($footerPath))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($footerPath)) }}" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none" aria-hidden="true">
    @endif
    @yield('content')
</body>
</html>

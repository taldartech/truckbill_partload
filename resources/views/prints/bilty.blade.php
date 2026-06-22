<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Bilty - {{ $bilty->bilty_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; margin: 0; padding: 8px; color: #000; }
        .print-page { width: 100%; max-width: 920px; margin: 0 auto; page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .print-copy { height: calc(50vh - 6px); min-height: 520px; margin-bottom: 6px; }
        .print-copy:last-child { margin-bottom: 0; }
        .lr-sheet {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            position: relative;
            padding: 4px 4px 18px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
        }
        .lr-fixed-top { flex: 0 0 auto; }
        .lr-items-wrap {
            flex: 1 1 auto;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        .lr-fixed-bottom { flex: 0 0 auto; }
        .lr-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .lr-table td, .lr-table th { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
        .lr-table th { font-weight: bold; text-align: center; }
        .no-border { border: none !important; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; line-height: 1.1; }
        .company-sub { font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .branch-text { font-size: 8px; line-height: 1.25; margin-top: 2px; }
        .gstin-title { font-size: 10px; font-weight: bold; text-align: center; }
        .gstin-value { font-size: 11px; font-weight: bold; text-align: center; }
        .from-to-label { font-weight: bold; font-size: 10px; line-height: 1.2; }
        .from-to-value { font-size: 12px; font-weight: bold; }
        .header-table .logo-cell { width: 52px; vertical-align: middle; padding: 2px; }
        .header-table .company-cell { width: auto; }
        .header-table .gstin-cell { width: 18%; text-align: center; vertical-align: middle; }
        .header-table .from-to-cell { width: 14%; vertical-align: middle; padding: 2px 3px; }
        .field-label { font-weight: bold; font-size: 8px; white-space: nowrap; }
        .field-value { min-height: 14px; font-size: 9px; }
        .items-table {
            width: 100%;
            height: 100%;
            flex: 1 1 auto;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000;
        }
        .items-table tbody {
            height: 100%;
        }
        .items-table thead th {
            border: 1px solid #000;
            font-weight: bold;
        }
        .items-table tbody td {
            border-top: none;
            border-bottom: none;
            border-right: none;
            border-left: 1px solid #000;
            vertical-align: top;
            padding: 2px 4px;
        }
        .items-table tbody td:first-child {
            border-left: none;
        }
        .items-table tbody tr.items-fill {
            height: 100%;
        }
        .items-table tbody tr.items-fill td {
            height: 100%;
        }
        .items-head { text-align: center; font-size: 8px; }
        .copy-stamp {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            padding: 4px 0;
        }
        .checkbox-row { font-size: 7px; white-space: nowrap; }
        .checkbox-box {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #000;
            text-align: center;
            line-height: 8px;
            font-size: 8px;
            margin-right: 2px;
            vertical-align: middle;
        }
        .checkbox-box.checked::after { content: '✓'; font-weight: bold; }
        .risk-text { font-size: 7px; font-weight: bold; text-align: center; padding: 2px 0; }
        .charges-side td { font-size: 8px; padding: 1px 3px; }
        .charges-side .label { font-weight: bold; text-transform: uppercase; }
        .charges-side .money { text-align: right; width: 70px; }
        .grand-row td { font-weight: bold; }
        .note-text { font-size: 7px; line-height: 1.25; }
        .footer-sign { text-align: right; font-size: 13px; padding-top: 8px; }
        .footer-sign .entered-by-line { font-size: 10px; margin-bottom: 24px; }
        .footer-sign .for-line { font-weight: bold; margin-top: 0; }
        .brand-border {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: right;
            font-size: 9px;
            /* border-top: 1px solid #000; */
            padding: 2px;
        }
        .branch-logo { max-height: 40px; max-width: 48px; display: block; margin: 0 auto; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .w-8 { width: 8%; }
        .w-12 { width: 12%; }
        .w-15 { width: 15%; }
        .w-20 { width: 20%; }
        .w-25 { width: 25%; }
        .w-30 { width: 30%; }
        .w-35 { width: 35%; }
        .w-40 { width: 40%; }
        .w-50 { width: 50%; }
        .w-58 { width: 58%; }
        .w-42 { width: 42%; }

        @media print {
            body { padding: 0; }
            .print-page { max-width: none; }
            .print-copy { height: calc(50vh - 3mm); min-height: 0; margin-bottom: 2mm; }
        }
        .border-top-none td, .border-top-none th { border-top: none !important; }
        .border-bottom-none td, .border-bottom-none th { border-bottom: none !important; }
        .border-right-none td, .border-right-none th { border-right: none !important; }
        .border-left-none td, .border-left-none th { border-left: none !important; }
    </style>
</head>
<body onload="window.print()">
    @foreach($copyPages as $pageCopies)
        <div class="print-page">
            @foreach($pageCopies as $copy)
                @include('prints.partials.bilty-copy', [
                    'copyLabel' => $copy['label'],
                    'showAmount' => $copy['show_amount'],
                ])
            @endforeach
        </div>
    @endforeach
</body>
</html>

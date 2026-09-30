<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Bilty - {{ $bilty->bilty_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; margin: 0; padding: 8px; color: #000; }
        .print-page { width: 100%; max-width: 980px; margin: 0 auto; page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .print-copy { height: calc(50vh - 6px); min-height: 520px; margin-bottom: 6px; }
        .print-copy:last-child { margin-bottom: 0; }
        .bi-sheet {
            width: 100%;
            height: 100%;
            border: 1.5px solid #000;
            position: relative;
            padding: 3px 4px 3px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
        }
        .bi-fixed-top { flex: 0 0 auto; }
        .bi-body { flex: 1 1 auto; min-height: 90px; display: flex; flex-direction: column; }
        .bi-fixed-bottom { flex: 0 0 auto; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        .no-border td, .no-border th { border: none !important; }
        .border-top-none td, .border-top-none th { border-top: none !important; }
        .border-bottom-none td, .border-bottom-none th { border-bottom: none !important; }
        .border-left-none td, .border-left-none th { border-left: none !important; }
        .border-right-none td, .border-right-none th { border-right: none !important; }

        .bi-header { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .bi-header td { border: none; padding: 1px 2px; vertical-align: top; }
        .bi-logo { width: 64px; text-align: center; vertical-align: middle; }
        .bi-logo img { max-height: 56px; max-width: 60px; display: block; margin: 0 auto; }
        .bi-company-name { font-size: 19px; font-weight: bold; line-height: 1.1; text-transform: uppercase; }
        .bi-company-meta { font-size: 10px; line-height: 1.35; margin-top: 1px; }
        .bi-doc-title { font-size: 15px; font-weight: bold; text-align: right; line-height: 1.2; }
        .bi-payment-box {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 3px 12px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            min-width: 120px;
            margin-top: 2px;
        }
        .bi-copy-label { font-size: 12px; font-weight: bold; margin-top: 2px; }

        .bi-meta td { font-size: 11px; line-height: 1.4; }
        .lbl { font-weight: bold; }
        .val { font-weight: normal; }
        .bi-party-name { font-weight: bold; font-size: 12px; }

        .bi-grid-wrap {
            position: relative;
            flex: 1 1 auto;
            min-height: 70px;
            display: flex;
            flex-direction: column;
        }
        .bi-grid {
            width: 100%;
            height: 100%;
            flex: 1 1 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .bi-col-item { width: 40%; }
        .bi-col-pkg { width: 10.5%; }
        .bi-col-wt { width: 10.5%; }
        .bi-col-chg { width: 26%; }
        .bi-col-amt { width: 13%; }
        .bi-grid thead th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            padding: 3px 4px;
            border: 1px solid #000;
        }
        .bi-grid tbody td {
            border: 1px solid #000;
            border-top: none;
            padding: 3px 4px;
            font-size: 10px;
            vertical-align: top;
        }
        .bi-grid tbody tr.bi-fill td {
            height: 100%;
            border-bottom: none;
            padding: 0;
            line-height: 0;
        }
        .bi-grid tfoot td {
            border: 1px solid #000;
            font-weight: bold;
            padding: 3px 4px;
            font-size: 13px;
            vertical-align: middle;
            background: #fff;
        }
        .bi-stamp {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 13%;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 2;
        }
        .bi-stamp span {
            transform: rotate(-90deg);
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            white-space: nowrap;
            opacity: 0.85;
            border: 2px solid #000;
            padding: 4px 10px;
        }

        /* Pad rows for charges without drawing extra horizontal item
         * rows, but keep the Item / Packages / Weight column separators. */
        .bi-item-blank {
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }

        .bi-lower td { font-size: 10px; padding: 3px 5px; line-height: 1.35; }
        .bi-terms {
            font-size: 8px;
            line-height: 1.3;
            padding: 3px 5px;
        }
        .bi-sign-row td { border-top: none; }
        .bi-sign-for { font-size: 12px; font-weight: bold; text-align: right; }
        .bi-footer-meta {
            font-size: 8px;
            margin-top: 5px;
        }
        .bi-footer-meta a { color: #000; text-decoration: none; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .w-18 { width: 18%; }
        .w-22 { width: 22%; }
        .w-20 { width: 20%; }
        .w-28 { width: 28%; }
        .w-30 { width: 30%; }
        .w-34 { width: 34%; }
        .w-36 { width: 36%; }
        .w-40 { width: 40%; }
        .w-50 { width: 50%; }
        .w-60 { width: 60%; }
        .w-70 { width: 70%; }
        .w-72 { width: 72%; }

        @media print {
            body { padding: 0; }
            .print-page { max-width: none; }
            .print-copy { height: calc(50vh - 3mm); min-height: 0; margin-bottom: 2mm; }
        }

        .bi-footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
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

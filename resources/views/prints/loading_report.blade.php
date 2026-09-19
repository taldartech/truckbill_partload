<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Loading Report - {{ $loading_report->date ? date('d-m-Y', strtotime($loading_report->date)) : '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 16px 20px;
            color: #000;
        }
        .print-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            border: 1px solid #000;
        }
        .company-name {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0 0 4px;
            text-transform: uppercase;
            padding: 0 10px;
        }
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 14px;
            text-transform: uppercase;
            padding: 0 10px;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 12px;
            padding: 0 10px;
        }
        .meta-col {
            width: 48%;
        }
        .meta-line {
            margin-bottom: 4px;
        }
        .meta-line strong {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        thead th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 4px;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        thead th.col-sn,
        thead th.col-nugs,
        thead th.col-weight {
            text-align: center;
        }
        thead th.col-weight {
            text-align: right;
        }
        tbody td {
            padding: 4px 4px;
            vertical-align: top;
            border: none;
        }
        tbody tr.data-row td {
            border-bottom: none;
        }
        .station-header td {
            font-weight: bold;
            font-size: 13px;
            padding-top: 10px;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .subtotal-row td {
            border-top: 1px solid #000;
            padding-top: 4px;
            padding-bottom: 8px;
            font-weight: bold;
        }
        .grand-total-row td {
            border-top: 2px solid #000;
            padding-top: 6px;
            font-weight: bold;
            font-size: 13px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .col-sn { width: 5%; }
        .col-bilty { width: 12%; }
        .col-marka { width: 10%; }
        .col-nugs { width: 7%; }
        .col-goods { width: 18%; }
        .col-station { width: 12%; }
        .col-consignee { width: 24%; }
        .col-weight { width: 10%; }
        .outer-layout {
        padding: 3px 4px 3px;
        border: 1px solid black;
        max-width: 820px;
        margin: 0 auto;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="outer-layout">
    <div class="print-container">
        <h1 class="company-name">{{ $company->name ?? 'TRANSPORT COMPANY' }}</h1>
        <h2 class="report-title">Loading Report</h2>

        <div class="meta">
            <div class="meta-col">
                <div class="meta-line"><strong>Date:-</strong> {{ $loading_report->date ? date('d/m/Y', strtotime($loading_report->date)) : '—' }}</div>
                <div class="meta-line"><strong>Station To:-</strong> {{ $loading_report->toStation->name ?? '—' }}</div>
            </div>
            <div class="meta-col" style="text-align: right;">
                <div class="meta-line"><strong>Truck No :-</strong> {{ $loading_report->truck->truck_number ?? '—' }}</div>
                <div class="meta-line"><strong>Driver Name :-</strong> {{ $loading_report->driver_name ?? '—' }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-sn">S.N.</th>
                    <th class="col-bilty">GR/Bilty No.</th>
                    <th class="col-marka">Pvt. Marka</th>
                    <th class="col-nugs">Nugs</th>
                    <th class="col-goods">Goods Name</th>
                    <th class="col-station">To Station</th>
                    <th class="col-consignee">Consignee Name</th>
                    <th class="col-weight">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach($printData['groups'] as $group)
                    <tr class="station-header">
                        <td colspan="8">{{ $group['station_name'] }}</td>
                    </tr>
                    @foreach($group['rows'] as $row)
                    <tr class="data-row">
                        <td class="text-center col-sn">{{ $row['serial'] }}</td>
                        <td class="col-bilty">{{ $row['bilty_number'] }}</td>
                        <td class="col-marka">{{ $row['private_marka'] ?: '—' }}</td>
                        <td class="text-center col-nugs">{{ number_format($row['nugs'], 0) }}</td>
                        <td class="col-goods">{{ $row['goods_name'] }}</td>
                        <td class="col-station">{{ $row['to_station'] }}</td>
                        <td class="col-consignee">{{ $row['consignee_name'] }}</td>
                        <td class="text-right col-weight">{{ number_format($row['weight'], 0) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="3"></td>
                        <td class="text-center">{{ number_format($group['subtotal']['nugs'], 0) }}</td>
                        <td colspan="3"></td>
                        <td class="text-right">{{ number_format($group['subtotal']['weight'], 0) }}</td>
                    </tr>
                @endforeach

                @if($printData['groups']->isNotEmpty())
                <tr class="grand-total-row">
                    <td colspan="3"></td>
                    <td class="text-center">{{ number_format($printData['grand_total']['nugs'], 0) }}</td>
                    <td colspan="3"></td>
                    <td class="text-right">{{ number_format($printData['grand_total']['weight'], 0) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        @if($loading_report->remark)
        <p style="margin-top: 14px;"><strong>Remark:</strong> {{ $loading_report->remark }}</p>
        @endif

    </div>
    @include('partials.print-brand-footer')
</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Loading Report - {{ $report['date'] ? date('d-m-Y', strtotime($report['date'])) : '' }}</title>
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

        thead { display: table-header-group; }
        thead th.report-header {
            border: none;
            padding: 0;
            font-weight: normal;
            font-size: 12px;
            text-transform: none;
            text-align: left;
        }
        tbody tr { page-break-inside: avoid; break-inside: avoid; }
        tbody.station-group { page-break-inside: avoid; break-inside: avoid; }
        tr.station-header { page-break-after: avoid; break-after: avoid; }
        .page-frame,
        .page-brand { display: none; }
        tfoot { display: table-footer-group; }
        tr.page-bottom-spacer td { height: 8px; padding: 0; }

        @page { margin: 8mm; }

        @media print {
            body { padding: 0; }
            .print-container {
                max-width: none;
                border: none;
                padding: 0 10px;
            }
            thead th.report-header { padding-top: 10px; }
            .page-frame {
                display: block;
                position: fixed;
                inset: 0;
                border: 1px solid #000;
                pointer-events: none;
            }
            tr.page-bottom-spacer td { height: 24px; }
            .screen-brand { display: none; }
            .page-brand {
                display: block;
                position: fixed;
                right: 10px;
                bottom: 4px;
            }
            .page-brand .print-brand-footer { margin-top: 0 !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page-frame"></div>
    <div class="page-brand">@include('partials.print-brand-footer')</div>
    <div class="print-container">
        <table>
            <thead>
                <tr>
                    <th colspan="8" class="report-header">
                        <h1 class="company-name">{{ $company->name ?? 'TRANSPORT COMPANY' }}</h1>
                        <h2 class="report-title">Loading Report</h2>

                        <div class="meta">
                            <div class="meta-col">
                                <div class="meta-line"><strong>Date:-</strong> {{ $report['date'] ? date('d/m/Y', strtotime($report['date'])) : '—' }}</div>
                                <div class="meta-line"><strong>Station To:-</strong> {{ $report['to_station'] ?? '—' }}</div>
                            </div>
                            <div class="meta-col" style="text-align: right;">
                                <div class="meta-line"><strong>Truck No :-</strong> {{ $report['truck_number'] ?? '—' }}</div>
                                <div class="meta-line"><strong>Driver Name :-</strong> {{ $report['driver_name'] ?? '—' }}</div>
                            </div>
                        </div>
                    </th>
                </tr>
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
            <tfoot>
                <tr class="page-bottom-spacer"><td colspan="8"></td></tr>
            </tfoot>
            @foreach($printData['groups'] as $group)
                <tbody class="station-group">
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
                </tbody>
            @endforeach

            @if($printData['groups']->isNotEmpty())
            <tbody>
                <tr class="grand-total-row">
                    <td colspan="3"></td>
                    <td class="text-center">{{ number_format($printData['grand_total']['nugs'], 0) }}</td>
                    <td colspan="3"></td>
                    <td class="text-right">{{ number_format($printData['grand_total']['weight'], 0) }}</td>
                </tr>
            </tbody>
            @endif

            @if($report['remark'])
            <tbody>
                <tr>
                    <td colspan="8" style="padding-top: 14px;"><strong>Remark:</strong> {{ $report['remark'] }}</td>
                </tr>
            </tbody>
            @endif
        </table>

        <div class="screen-brand">@include('partials.print-brand-footer')</div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Main Challan - {{ $main_challan->main_challan_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .print-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 10px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header h3 { margin: 5px 0 0; font-size: 14px; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .col-6 { width: 50%; box-sizing: border-box; }
        .col-12 { width: 100%; box-sizing: border-box; }
        .box { padding: 5px; border: 1px solid #000; height: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th, table td { border: 1px solid #000; padding: 5px; text-align: left; vertical-align: top; }
        table th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; display: flex; justify-content: space-between; padding-top: 50px; }
        .signature { text-align: center; width: 30%; border-top: 1px solid #000; padding-top: 5px; }
        .summary-table td { border: none; padding: 2px 5px; }
        .summary-table tr td:first-child { font-weight: bold; }
        .totals-box { border: 1px solid #000; padding: 8px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .totals-row.total { font-weight: bold; font-size: 14px; margin-top: 6px; padding-top: 6px; border-top: 1px solid #000; }

        @media print {
            body { padding: 0; }
            .print-container { border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header">
            <h1>{{ $company->name ?? 'TRANSPORT COMPANY' }}</h1>
            <h3>MAIN CHALLAN</h3>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <strong>Main Challan No:</strong> {{ $main_challan->main_challan_number ?? '—' }}<br>
                    <strong>Date:</strong> {{ $main_challan->date ? date('d-M-Y', strtotime($main_challan->date)) : '—' }}<br>
                    <strong>From:</strong> {{ $main_challan->fromStation->name ?? '—' }}<br>
                    <strong>To:</strong> {{ $main_challan->toStation->name ?? '—' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <strong>Truck No:</strong> {{ $main_challan->truck->truck_number ?? '—' }}<br>
                    <strong>Driver:</strong> {{ $main_challan->driver->name ?? '—' }}<br>
                    @if($main_challan->status)
                        <strong>Status:</strong> {{ $main_challan->status }}
                    @endif
                </div>
            </div>
        </div>

        <h4>Linked Challans</h4>
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="5%">Sr.</th>
                    <th width="12%">Challan No.</th>
                    <th width="15%">Station (To)</th>
                    <th class="text-center" width="8%">Nugs</th>
                    <th class="text-center" width="8%">Weight</th>
                    <th class="text-right" width="10%">To Pay</th>
                    <th class="text-right" width="10%">Paid/Adv</th>
                    <th class="text-right" width="10%">Crossing</th>
                    <th class="text-right" width="10%">Cartage</th>
                    <th class="text-right" width="10%">Labour</th>
                </tr>
            </thead>
            <tbody>
                @forelse($printData['rows'] as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['challan_number'] }}</td>
                    <td>{{ $row['to_station'] }}</td>
                    <td class="text-center">{{ number_format($row['nugs'], 0) }}</td>
                    <td class="text-center">{{ number_format($row['weight'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($row['to_pay'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($row['paid_amount'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($row['crossing'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($row['cartage'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($row['labour'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No challans linked.</td>
                </tr>
                @endforelse
                @if($printData['rows']->isNotEmpty())
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-center">{{ number_format($printData['totals']['nugs'], 0) }}</td>
                    <td class="text-center">{{ number_format($printData['totals']['weight'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($printData['totals']['to_pay'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($printData['totals']['paid_amount'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($printData['totals']['crossing'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($printData['totals']['cartage'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($printData['totals']['labour'], 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none; height: auto;">
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr><td>Total Weight</td><td class="text-right">{{ number_format($main_challan->total_weight ?? $printData['totals']['weight'], 2) }}</td></tr>
                        <tr><td>Total Nugs</td><td class="text-right">{{ number_format($main_challan->total_packages ?? $printData['totals']['nugs'], 0) }}</td></tr>
                        <tr><td>To Pay Amount</td><td class="text-right">₹ {{ number_format($main_challan->to_pay_amount ?? $printData['totals']['to_pay'], 2) }}</td></tr>
                        <tr><td>Paid Amt / Adv. Rec.</td><td class="text-right">₹ {{ number_format($main_challan->paid_amount ?? $printData['totals']['paid_amount'], 2) }}</td></tr>
                        <tr><td>Crossing Freight</td><td class="text-right">₹ {{ number_format($main_challan->crossing_freight ?? $printData['totals']['crossing'], 2) }}</td></tr>
                        <tr><td>Cartage / Dalali / PF</td><td class="text-right">₹ {{ number_format($main_challan->cartage_dalali_pf ?? $printData['totals']['cartage'], 2) }}</td></tr>
                        @if((float) ($main_challan->gst ?? 0) != 0)
                        <tr><td>GST Tax</td><td class="text-right">₹ {{ number_format($main_challan->gst, 2) }}</td></tr>
                        @endif
                    </table>
                    <br>
                    <strong>Less:</strong>
                    <table class="summary-table" style="width: 100%;">
                        @if((float) ($main_challan->labour_charge ?? 0) != 0)
                        <tr><td>Labour Commission</td><td class="text-right">₹ {{ number_format($main_challan->labour_charge, 2) }}</td></tr>
                        @endif
                        @if((float) ($main_challan->kat ?? 0) != 0)
                        <tr><td>Kat</td><td class="text-right">₹ {{ number_format($main_challan->kat, 2) }}</td></tr>
                        @endif
                        @if((float) ($main_challan->home_delivery_charge ?? 0) != 0)
                        <tr><td>Home Delivery</td><td class="text-right">₹ {{ number_format($main_challan->home_delivery_charge, 2) }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
            <div class="col-6">
                <div class="totals-box">
                    <div class="totals-row"><span>Truck Freight</span><span>₹ {{ number_format($main_challan->truck_freight ?? 0, 2) }}</span></div>
                    <div class="totals-row"><span>Cash Received</span><span>₹ {{ number_format($main_challan->cash_received ?? 0, 2) }}</span></div>
                    <div class="totals-row"><span>Cash Paid</span><span>₹ {{ number_format($main_challan->cash_paid ?? 0, 2) }}</span></div>
                    <div class="totals-row total"><span>TOTAL</span><span>₹ {{ number_format($main_challan->total_amount ?? 0, 2) }} {{ $main_challan->total_balance_type ?? '' }}</span></div>
                    @if($main_challan->pay_by)
                    <div class="totals-row"><span>To (Account)</span><span>{{ $main_challan->pay_by }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        @if($main_challan->remark)
        <div class="row" style="margin-top: 10px;">
            <div class="col-12">
                <div class="box">
                    <strong>Remark:</strong> {{ $main_challan->remark }}
                </div>
            </div>
        </div>
        @endif

        <div class="footer">
            <div class="signature">Driver Signature</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>

        @include('partials.print-brand-footer')
    </div>
</body>
</html>

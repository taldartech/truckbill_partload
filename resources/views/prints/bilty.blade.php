<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Bilty - {{ $bilty->bilty_number }}</title>
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
        .muted { color: #444; }
        .info-line { margin-bottom: 3px; }

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
            <h3>CONSIGNMENT NOTE / LORRY RECEIPT</h3>
            <p style="margin:2px 0;">Phone: {{ $branch->contact_number ?? $company->contact_number ?? '__________' }} | GSTIN: {{ ($branch && $branch->has_gst) ? ($branch->gst_number ?? '__________') : '__________' }}</p>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <div class="info-line"><strong>Bilty No:</strong> {{ $bilty->bilty_number ?? '—' }}</div>
                    <div class="info-line"><strong>Date:</strong> {{ $bilty->date ? date('d-M-Y', strtotime($bilty->date)) : '—' }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <div class="info-line"><strong>Payment Type:</strong> {{ $bilty->payment_type ?? '—' }}</div>
                    @if($bilty->vendor && in_array(strtolower((string) ($bilty->cartage_type ?? '')), ['due']))
                        <div class="info-line"><strong>Cartage Vendor:</strong> {{ $bilty->vendor->name }}</div>
                    @endif
                    @if($bilty->ewayBills->isNotEmpty())
                        @foreach($bilty->ewayBills as $eway)
                            <div class="info-line">
                                <strong>E-Way Bill:</strong> {{ $eway->eway_bill_no ?? '—' }}
                                @if($eway->eway_bill_valid_till)
                                    | <strong>Valid Till:</strong> {{ date('d-M-Y', strtotime($eway->eway_bill_valid_till)) }}
                                @endif
                            </div>
                        @endforeach
                    @elseif($bilty->eway_bill_no)
                        <div class="info-line"><strong>E-Way Bill:</strong> {{ $bilty->eway_bill_no }}</div>
                        @if($bilty->eway_bill_expiry_date)
                            <div class="info-line"><strong>Valid Till:</strong> {{ date('d-M-Y', strtotime($bilty->eway_bill_expiry_date)) }}</div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="row" style="margin-top:-1px;">
            <div class="col-6">
                <div class="box" style="border-right: none; border-top: none;">
                    <strong>CONSIGNOR</strong><br>
                    {{ $bilty->consignor->name ?? '—' }}<br>
                    @if($bilty->consignor?->address)
                        <span class="muted">{{ $bilty->consignor->address }}</span><br>
                    @endif
                    @if($bilty->consignor?->mobile)
                        <span class="muted">Mobile: {{ $bilty->consignor->mobile }}</span><br>
                    @endif
                    <strong>GSTIN:</strong> {{ $bilty->consignor->gst_number ?? '—' }} <br>
                    <strong>Address:</strong> {{ $bilty->consignor->address ?? '—' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box" style="border-top: none;">
                    <strong>CONSIGNEE</strong><br>
                    {{ $bilty->consignee->name ?? '—' }}<br>
                    @if($bilty->consignee?->address)
                        <span class="muted">{{ $bilty->consignee->address }}</span><br>
                    @endif
                    @if($bilty->consignee?->mobile)
                        <span class="muted">Mobile: {{ $bilty->consignee->mobile }}</span><br>
                    @endif
                    <strong>GSTIN:</strong> {{ $bilty->consignee->gst_number ?? '—' }} <br>
                    <strong>Address:</strong> {{ $bilty->consignee->address ?? '—' }}
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Goods</th>
                    <th>Packing</th>
                    <th class="text-right">Packages</th>
                    <th class="text-right">Actual Wt (Kg)</th>
                    <th class="text-right">Charged Wt (Kg)</th>
                    <th>Frt. Type</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($printData['detail_rows'] as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail['good'] }}</td>
                        <td>{{ $detail['package_type'] }}</td>
                        <td class="text-right">{{ $detail['no_of_packages'] ?? '—' }}</td>
                        <td class="text-right">{{ $detail['gross_weight'] !== null ? number_format((float) $detail['gross_weight'], 2) : '—' }}</td>
                        <td class="text-right">{{ $detail['charged_weight'] !== null ? number_format((float) $detail['charged_weight'], 2) : '—' }}</td>
                        <td>{{ $detail['rate_type'] }}</td>
                        <td class="text-right">{{ $detail['rate'] !== null ? number_format((float) $detail['rate'], 2) : '—' }}</td>
                        <td class="text-right">₹ {{ number_format($detail['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center muted">No goods details</td>
                    </tr>
                @endforelse
                @if($printData['detail_rows']->isNotEmpty())
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ number_format($printData['detail_totals']['no_of_packages'], 0) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($printData['detail_totals']['gross_weight'], 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($printData['detail_totals']['charged_weight'], 2) }}</strong></td>
                        <td colspan="2"></td>
                        <td class="text-right"><strong>₹ {{ number_format($printData['detail_totals']['amount'], 2) }}</strong></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none; height: auto;">
                    @if($bilty->private_marka)
                        <div class="info-line"><strong>Private Marka:</strong> {{ $bilty->private_marka }}</div>
                    @endif
                    @if($bilty->remark)
                        <div class="info-line"><strong>Remark:</strong> {{ $bilty->remark }}</div>
                    @endif
                    @if($bilty->delivery_at)
                        <div class="info-line"><strong>Delivery At:</strong> {{ $bilty->delivery_at }}</div>
                    @endif
                    @if($bilty->weight_actual)
                        <div class="info-line"><strong>Weight Actual:</strong> {{ number_format((float) $bilty->weight_actual, 2) }} Kg</div>
                    @endif
                    @if($bilty->declared_value)
                        <div class="info-line"><strong>Declared Value:</strong> ₹ {{ number_format((float) $bilty->declared_value, 2) }}</div>
                    @endif
                    @if ($bilty->invoice_number)
                        <div class="info-line"><strong>Invoice No:</strong> {{ $bilty->invoice_number }}</div>
                    @endif
                    @if($bilty->gst_liability_by)
                        <div class="info-line"><strong>GST Liability By:</strong> {{ $bilty->gst_liability_by }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <table style="margin-bottom: 0;">
                    @forelse($printData['charges'] as $charge)
                        <tr>
                            <td><strong>{{ $charge['label'] }}</strong></td>
                            <td class="text-right">₹ {{ number_format($charge['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center muted">No charges</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td><strong>Total Amount</strong></td>
                        <td class="text-right"><strong>₹ {{ number_format($printData['total_amount'], 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="signature">Driver / Received By</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>

        @include('partials.print-brand-footer')
    </div>
</body>
</html>

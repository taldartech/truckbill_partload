<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bilty - {{ $bilty->bilty_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .print-container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #000; padding: 10px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header h3 { margin: 5px 0 0; font-size: 14px; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .col-6 { width: 50%; }
        .col-4 { width: 33.33%; }
        .col-12 { width: 100%; }
        .bordered { border: 1px solid #000; }
        .box { padding: 5px; border: 1px solid #000; height: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th, table td { border: 1px solid #000; padding: 5px; text-align: left; }
        table th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; display: flex; justify-content: space-between; padding-top: 50px; }
        .signature { text-align: center; width: 30%; border-top: 1px solid #000; padding-top: 5px; }
        
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
            <p style="margin:2px 0;">Phone: {{ $company->phone ?? '__________' }} | GSTIN: {{ $company->gst_number ?? '__________' }}</p>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <strong>Bilty No:</strong> {{ $bilty->bilty_number }}<br>
                    <strong>Date:</strong> {{ date('d-M-Y', strtotime($bilty->date)) }}<br>
                    <strong>From:</strong> {{ $bilty->fromStation->name ?? '____' }}<br>
                    <strong>To:</strong> {{ $bilty->toStation->name ?? '____' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    @if($bilty->ewayBills->isNotEmpty())
                        @foreach($bilty->ewayBills as $eway)
                            <strong>E-Way Bill:</strong> {{ $eway->eway_bill_no ?? 'N/A' }}
                            @if($eway->eway_bill_valid_till)
                                | <strong>Valid Till:</strong> {{ date('d-M-Y', strtotime($eway->eway_bill_valid_till)) }}
                            @endif
                            <br>
                        @endforeach
                    @elseif($bilty->eway_bill_no)
                        <strong>E-Way Bill:</strong> {{ $bilty->eway_bill_no }}<br>
                        @if($bilty->eway_bill_expiry_date)
                            <strong>Valid Till:</strong> {{ date('d-M-Y', strtotime($bilty->eway_bill_expiry_date)) }}<br>
                        @endif
                    @else
                        <strong>E-Way Bill:</strong> N/A<br>
                    @endif
                    <strong>Payment Type:</strong> {{ $bilty->payment_type }}
                </div>
            </div>
        </div>

        <div class="row" style="margin-top:-1px;">
            <div class="col-6">
                <div class="box" style="border-right: none; border-top: none;">
                    <strong>CONSIGNOR:</strong><br>
                    {{ $bilty->consignor->name ?? '____' }}<br>
                    GSTIN: {{ $bilty->consignor->gst_number ?? '____' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box" style="border-top: none;">
                    <strong>CONSIGNEE:</strong><br>
                    {{ $bilty->consignee->name ?? '____' }}<br>
                    GSTIN: {{ $bilty->consignee->gst_number ?? '____' }}
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Articles/Goods</th>
                    <th>Pkg Type</th>
                    <th>Packages</th>
                    <th>Actual Wt (Kg)</th>
                    <th>Charged Wt (Kg)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bilty->biltyDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->good->name ?? 'Goods' }}</td>
                    <td>{{ $detail->packageType->name ?? 'Pkg' }}</td>
                    <td>{{ $detail->no_of_packages }}</td>
                    <td>{{ $detail->gross_weight }}</td>
                    <td>{{ $detail->charged_weight ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none; height: auto;">
                    <strong>Remarks:</strong><br>
                    {{ $bilty->remark ?? 'None' }}
                    <br><br><br>
                </div>
            </div>
            <div class="col-6">
                <table style="margin-bottom: 0;">
                    <tr><td><strong>Freight</strong></td><td class="text-right">₹ {{ number_format($bilty->freight, 2) }}</td></tr>
                    <tr><td><strong>Bilty Charge</strong></td><td class="text-right">₹ {{ number_format($bilty->bilty_charge, 2) }}</td></tr>
                    <tr><td><strong>Loading/Other</strong></td><td class="text-right">₹ {{ number_format($bilty->other_charge, 2) }}</td></tr>
                    <tr><td><strong>Total</strong></td><td class="text-right"><strong>₹ {{ number_format($bilty->amount, 2) }}</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="signature">Driver / Received By</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>
    </div>
</body>
</html>

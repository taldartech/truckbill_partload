<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Receipt - {{ $receipt->delivery_receipt_no }}</title>
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
        .box { padding: 5px; border: 1px solid #000; height: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th, table td { border: 1px solid #000; padding: 5px; text-align: left; }
        table th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
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
            <h3>DELIVERY RECEIPT (BILL)</h3>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <strong>Receipt No:</strong> {{ $receipt->delivery_receipt_no }}<br>
                    <strong>Date:</strong> {{ date('d-M-Y', strtotime($receipt->date)) }}<br>
                    <strong>Bilty / GR No:</strong> {{ $receipt->gr_no_bilty_no }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <strong>Consignee:</strong> {{ $receipt->consignee->name ?? '____' }}<br>
                    <strong>Mode of Payment:</strong> {{ $receipt->mode_of_payment }}<br>
                    <strong>Payment Due From:</strong> {{ $receipt->payment_due_from }}
                </div>
            </div>
        </div>

        <h4>Charges Breakdown</h4>
        <table style="width: 50%; margin: 0 auto;">
            <tbody>
                <tr><td>Freight</td><td class="text-right">₹ {{ number_format($receipt->freight, 2) }}</td></tr>
                <tr><td>Handling Charge</td><td class="text-right">₹ {{ number_format($receipt->handling_charge, 2) }}</td></tr>
                <tr><td>Risk/DD Charge</td><td class="text-right">₹ {{ number_format($receipt->risk_dd_charge, 2) }}</td></tr>
                <tr><td>Delivery Charge</td><td class="text-right">₹ {{ number_format($receipt->delivery_charge, 2) }}</td></tr>
                <tr><td>Demurrage</td><td class="text-right">₹ {{ number_format($receipt->demurrage, 2) }}</td></tr>
                <tr><td>Other Charge</td><td class="text-right">₹ {{ number_format($receipt->other_charge, 2) }}</td></tr>
                <tr style="background-color: #f0f0f0;"><td><strong>Sub Total</strong></td><td class="text-right"><strong>₹ {{ number_format($receipt->total_amount, 2) }}</strong></td></tr>
                <tr><td>GST</td><td class="text-right">₹ {{ number_format($receipt->gst, 2) }}</td></tr>
                <tr><td>Less</td><td class="text-right">- ₹ {{ number_format($receipt->less, 2) }}</td></tr>
                <tr style="font-size: 16px;"><td><strong>FINAL AMOUNT</strong></td><td class="text-right"><strong>₹ {{ number_format($receipt->final_amount, 2) }}</strong></td></tr>
            </tbody>
        </table>

        <div class="footer">
            <div class="signature">Receiver Signature</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>
    </div>
</body>
</html>

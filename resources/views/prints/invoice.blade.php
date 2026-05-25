<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; margin: 0; padding: 20px; }
        .print-container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #000; padding: 20px; box-sizing: border-box; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 28px; text-transform: uppercase; color: #333; }
        .header h3 { margin: 5px 0 0; font-size: 16px; background-color: #f0f0f0; display: inline-block; padding: 5px 15px; border: 1px solid #000; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
        .col-6 { width: 50%; }
        .box { padding: 10px; border: 1px solid #000; height: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #000; padding: 8px; text-align: left; }
        table th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; padding-top: 50px; }
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
            <h3>TAX INVOICE</h3>
            <p style="margin:5px 0;">Phone: {{ $company->phone ?? '__________' }} | GSTIN: {{ $company->gst_number ?? '__________' }}</p>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_no }}<br><br>
                    <strong>Date:</strong> {{ date('d-M-Y', strtotime($invoice->date)) }}<br><br>
                    <strong>Status:</strong> {{ $invoice->status }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <strong>BILL TO:</strong><br><br>
                    <strong>{{ $invoice->party->name ?? '____' }}</strong><br>
                    GSTIN: {{ $invoice->party->gst_number ?? '____' }}<br>
                    Phone: {{ $invoice->party->phone ?? '____' }}
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:5%;">Sr.</th>
                    <th style="width:25%;">Bilty / GR No</th>
                    <th style="width:15%;">Date</th>
                    <th style="width:40%;">Particulars (Route)</th>
                    <th style="width:15%;" class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->bilty->bilty_number ?? 'N/A' }}</td>
                    <td>{{ date('d-M-y', strtotime($detail->bilty->date ?? $invoice->date)) }}</td>
                    <td>
                        {{ $detail->bilty->fromStation->name ?? '____' }} to {{ $detail->bilty->toStation->name ?? '____' }}
                    </td>
                    <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>GST:</strong></td>
                    <td class="text-right">{{ number_format($invoice->gst_amount, 2) }}</td>
                </tr>
                <tr style="background-color: #f4f4f4; font-size: 16px;">
                    <td colspan="4" class="text-right"><strong>NET PAYABLE:</strong></td>
                    <td class="text-right"><strong>₹ {{ number_format($invoice->net_amount, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-bottom: 30px;">
            <strong>Amount in Words:</strong> Rupees {{ \NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($invoice->net_amount) }} Only.
        </div>

        <div class="row">
            <div class="col-12" style="font-size: 11px;">
                <strong>Terms & Conditions:</strong><br>
                1. All payments to be made by A/c Payee Cheque / RTGS / NEFT only.<br>
                2. Interest @ 24% p.a. will be charged if bill is not paid within 15 days.<br>
                3. Subject to jurisdiction of {{ $company->city ?? 'Local' }} Courts only.
            </div>
        </div>

        <div class="footer">
            <div class="signature">Receiver's Signature & Seal</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}<br><br><br>Auth. Signatory</div>
        </div>
    </div>
</body>
</html>

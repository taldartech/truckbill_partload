<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Challan - {{ $challan->challan_no }}</title>
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
            <h3>TRIP CHALLAN / MANIFEST</h3>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none;">
                    <strong>Challan No:</strong> {{ $challan->challan_no }}<br>
                    <strong>Date:</strong> {{ date('d-M-Y', strtotime($challan->date)) }}<br>
                    <strong>From:</strong> {{ $challan->fromStation->name ?? '____' }}<br>
                    <strong>To:</strong> {{ $challan->toStation->name ?? '____' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <strong>Truck No:</strong> {{ $challan->truck->truck_no ?? '____' }}<br>
                    <strong>Driver:</strong> {{ $challan->driver->name ?? '____' }}<br>
                    <strong>Broker:</strong> {{ $challan->broker_name ?? 'N/A' }}
                </div>
            </div>
        </div>

        <h4>Loaded Bilties</h4>
        <table>
            <thead>
                <tr>
                    <th>Bilty No</th>
                    <th>Consignee</th>
                    <th>To Station</th>
                    <th>Packages</th>
                    <th>Weight</th>
                    <th>To Pay Frt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($challan->details as $detail)
                <tr>
                    <td>{{ $detail->bilty->bilty_number ?? '____' }}</td>
                    <td>{{ $detail->bilty->consignee->name ?? '____' }}</td>
                    <td>{{ $detail->bilty->toStation->name ?? '____' }}</td>
                    <td>{{ $detail->bilty->biltyDetails->sum('no_of_packages') ?? 0 }}</td>
                    <td>{{ $detail->bilty->biltyDetails->sum('gross_weight') ?? 0 }}</td>
                    <td class="text-right">₹ {{ number_format($detail->bilty->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none; height: auto;">
                    <strong>Remarks:</strong><br>
                    {{ $challan->remark ?? 'None' }}
                </div>
            </div>
            <div class="col-6">
                <table style="margin-bottom: 0;">
                    <tr><td><strong>Truck Freight</strong></td><td class="text-right">₹ {{ number_format($challan->truck_freight, 2) }}</td></tr>
                    <tr><td><strong>Advance</strong></td><td class="text-right">₹ {{ number_format($challan->advance, 2) }}</td></tr>
                    <tr><td><strong>Commission</strong></td><td class="text-right">₹ {{ number_format($challan->commission, 2) }}</td></tr>
                    <tr><td><strong>Balance</strong></td><td class="text-right"><strong>₹ {{ number_format($challan->balance, 2) }}</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="signature">Driver Signature</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Challan - {{ $challan->challan_number }}</title>
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
        .badge-booking { color: #0d6efd; font-weight: bold; }
        .badge-crossing { color: #0dcaf0; font-weight: bold; }

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
                    <strong>Challan No:</strong> {{ $challan->challan_number ?? '—' }}<br>
                    <strong>Date:</strong> {{ $challan->date ? date('d-M-Y', strtotime($challan->date)) : '—' }}<br>
                    <strong>From:</strong> {{ $challan->fromStation->name ?? '—' }}<br>
                    <strong>To:</strong> {{ $challan->toStation->name ?? '—' }}
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <strong>Truck No:</strong> {{ $challan->truck->truck_number ?? '—' }}<br>
                    <strong>Driver:</strong> {{ $challan->driver->name ?? '—' }}<br>
                    @if($challan->status)
                        <strong>Status:</strong> {{ $challan->status }}
                    @endif
                </div>
            </div>
        </div>

        <h4>Loaded Goods</h4>
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="5%">Sr.</th>
                    <th width="12%">GR/Bilty No.</th>
                    <th width="12%">Type</th>
                    <th width="15%">Station (To)</th>
                    <th class="text-center" width="8%">Nugs</th>
                    <th class="text-center" width="8%">Weight</th>
                    <th width="15%">Goods</th>
                    <th class="text-right" width="10%">Freight</th>
                    <th class="text-center" width="8%">B.O.B.</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalNugs = 0;
                    $totalWeight = 0;
                    $totalBookingFreight = 0;
                    $totalCrossingFreight = 0;
                @endphp
                @forelse($challan->details as $index => $detail)
                    @if($detail->bilty_id && $detail->bilty)
                        @php
                            $nugs = $detail->bilty->biltyDetails->sum('no_of_packages');
                            $weight = $detail->bilty->biltyDetails->sum('gross_weight');
                            $freight = $detail->bilty->freight ?? 0;
                            $totalNugs += $nugs;
                            $totalWeight += $weight;
                            $totalBookingFreight += $freight;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $detail->bilty->bilty_number ?? '—' }}</td>
                            <td class="badge-booking">Booking</td>
                            <td>{{ $detail->bilty->toStation->name ?? '—' }}</td>
                            <td class="text-center">{{ number_format($nugs, 0) }}</td>
                            <td class="text-center">{{ number_format($weight, 2) }}</td>
                            <td>{{ $detail->bilty->biltyDetails->first()?->good?->name ?? '—' }}</td>
                            <td class="text-right">₹ {{ number_format($freight, 2) }}</td>
                            <td class="text-center">{{ $detail->bilty->payment_type ?? '—' }}</td>
                        </tr>
                    @elseif($detail->inward_register_detail_id && $detail->inwardRegisterDetail)
                        @php
                            $inward = $detail->inwardRegisterDetail;
                            $nugs = $inward->no_of_packages ?? 0;
                            $weight = $inward->weight ?? 0;
                            $freight = $inward->freight ?? 0;
                            $totalNugs += $nugs;
                            $totalWeight += $weight;
                            $totalCrossingFreight += $freight;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $inward->gr_no ?? 'GR-' . $inward->id }}</td>
                            <td class="badge-crossing">Crossing</td>
                            <td>{{ $inward->toStation->name ?? '—' }}</td>
                            <td class="text-center">{{ number_format($nugs, 0) }}</td>
                            <td class="text-center">{{ number_format($weight, 2) }}</td>
                            <td>{{ $inward->good->name ?? '—' }}</td>
                            <td class="text-right">₹ {{ number_format($freight, 2) }}</td>
                            <td class="text-center">{{ $inward->basis_of_booking ?? '—' }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No goods loaded.</td>
                    </tr>
                @endforelse
                @if($challan->details->isNotEmpty())
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="4" class="text-right">Total</td>
                    <td class="text-center">{{ number_format($totalNugs, 0) }}</td>
                    <td class="text-center">{{ number_format($totalWeight, 2) }}</td>
                    <td></td>
                    <td class="text-right">₹ {{ number_format($totalBookingFreight + $totalCrossingFreight, 2) }}</td>
                    <td></td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="box" style="border-right: none; height: auto;">
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr><td>Total Weight</td><td class="text-right">{{ number_format($challan->total_weight ?? $totalWeight, 2) }}</td></tr>
                        <tr><td>Total Nugs</td><td class="text-right">{{ number_format($challan->total_packages ?? $totalNugs, 0) }}</td></tr>
                        <tr><td>To Pay Amount</td><td class="text-right">₹ {{ number_format($challan->to_pay_amount ?? 0, 2) }}</td></tr>
                        <tr><td>Paid Amt / Adv. Rec.</td><td class="text-right">₹ {{ number_format($challan->paid_amount ?? 0, 2) }}</td></tr>
                        <tr><td>Booking Freight</td><td class="text-right">₹ {{ number_format($challan->booking_freight ?? $totalBookingFreight, 2) }}</td></tr>
                        <tr><td>Crossing Freight</td><td class="text-right">₹ {{ number_format($challan->crossing_freight ?? $totalCrossingFreight, 2) }}</td></tr>
                        <tr><td>Cartage / Dalali / PF</td><td class="text-right">₹ {{ number_format($challan->cartage_dalali_pf ?? 0, 2) }}</td></tr>
                        <tr><td>GST Tax</td><td class="text-right">₹ {{ number_format($challan->gst ?? 0, 2) }}</td></tr>
                        <tr><td>Labour Comm.</td><td class="text-right">₹ {{ number_format($challan->labour_charge ?? 0, 2) }}</td></tr>
                        <tr><td>Kat</td><td class="text-right">₹ {{ number_format($challan->kat ?? 0, 2) }}</td></tr>
                        <tr><td>Home Delivery</td><td class="text-right">₹ {{ number_format($challan->home_delivery_charge ?? 0, 2) }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr><td>Truck Freight</td><td class="text-right">₹ {{ number_format($challan->truck_freight ?? 0, 2) }}</td></tr>
                        <tr><td>Cash Receive</td><td class="text-right">₹ {{ number_format($challan->cash_received ?? 0, 2) }}</td></tr>
                        <tr><td>Cash Paid</td><td class="text-right">₹ {{ number_format($challan->cash_payment ?? 0, 2) }}</td></tr>
                        <tr style="font-weight: bold; font-size: 14px;">
                            <td>TOTAL</td>
                            <td class="text-right">₹ {{ number_format($challan->total_amount ?? 0, 2) }}</td>
                        </tr>
                    </table>
                    @if($challan->remark)
                        <p style="margin-top: 10px;"><strong>Remark:</strong> {{ $challan->remark }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="signature">Driver Signature</div>
            <div class="signature">For {{ $company->name ?? 'Company' }}</div>
        </div>
    </div>
</body>
</html>

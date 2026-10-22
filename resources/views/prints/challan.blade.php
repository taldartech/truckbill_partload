<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Challan - {{ $challan->challan_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .print-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 10px; box-sizing: border-box; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header-top { display: flex; align-items: center; gap: 12px; }
        .header-logo { flex: 0 0 auto; }
        .header-logo img { max-height: 64px; max-width: 72px; display: block; }
        .header-company { flex: 1 1 auto; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header h3 { margin: 5px 0 0; font-size: 14px; }
        .header-doc-title { text-align: center; margin-top: 6px; font-size: 14px; font-weight: bold; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .col-1 { width: 8.33%; box-sizing: border-box; }
        .col-2 { width: 16.66%; box-sizing: border-box; }
        .col-3 { width: 25%; box-sizing: border-box; }
        .col-4 { width: 33.33%; box-sizing: border-box; }
        .col-5 { width: 41.66%; box-sizing: border-box; }
        .col-6 { width: 50%; box-sizing: border-box; }
        .col-7 { width: 58.33%; box-sizing: border-box; }
        .col-8 { width: 66.66%; box-sizing: border-box; }
        .col-9 { width: 75%; box-sizing: border-box; }
        .col-10 { width: 83.33%; box-sizing: border-box; }
        .col-11 { width: 91.66%; box-sizing: border-box; }
        .col-12 { width: 100%; box-sizing: border-box; }
        .box { padding: 5px; border: 1px solid #000; height: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        table th, table td { border: 1px solid #000; padding: 5px; text-align: left; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        table tfoot td { font-weight: bold; }
        .challan-table { font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; display: flex; justify-content: space-between; padding-top: 50px; }
        .signature { text-align: center; border-top: 1px solid #000; padding-top: 5px; }
        .summary-table td { border: none; padding: 2px 5px; }
        .summary-table tr td:first-child { font-weight: bold; }
        .outer-layout {
            padding: 3px 4px 3px;
            border: 1px solid black;
            max-width: 820px;
            margin: 0 auto;
        }
        @media print {
            @page { margin: 10mm; }
            body { padding: 0; }
            .print-container { max-width: none; border: 1px solid #000; padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $branchState = $branch?->state?->name ?? ($branch?->city?->name ?? '');
        $branchAddress = trim(
            collect([$branch?->address, $branch?->city?->name, $branch?->pin_code])
                ->filter()
                ->implode(', '),
        );
        $logoPath = $branch?->resolvedLogo();
        $logoUrl = null;
        if ($logoPath) {
            $logoUrl = str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')
                ? $logoPath
                : url($logoPath);
        }
    @endphp
    <div class="outer-layout">
    <div class="print-container">
        <div class="header">
            <div class="header-top">
                @if ($logoUrl)
                    <div class="header-logo">
                        <img src="{{ $logoUrl }}" alt="Logo">
                    </div>
                @endif
                <div class="header-company">
                    <h1>{{ $company->name }}</h1>
                    <h3>{{ $branchAddress }}, Phone: {{ $branch->contact_number ?? $company->contact_number }}</h3>
                    <div class="header-doc-title">CHALLAN</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-5">
                <strong>Challan No:</strong> {{ $challan->challan_number ?? '—' }}<br>
                <strong>Date:</strong> {{ $challan->date ? date('d-M-Y', strtotime($challan->date)) : '—' }}
            </div>
            <div class="col-4">
                <strong>Truck No:</strong> {{ $challan->truck->truck_number ?? '—' }}<br>
                <strong>Driver:</strong> {{ $challan->driver_name ?? '—' }}<br>
            </div>
            <div class="col-3">
                <strong>From:</strong> {{ $challan->fromStation->name ?? '—' }}<br>
                <strong>To:</strong> {{ $challan->toStation->name ?? '—' }}
            </div>
        </div>

        <table class="challan-table">
            <thead>
                <tr>
                    <th class="text-right" width="4%">S.N.</th>
                    <th width="10%">GR/Bilty No.</th>
                    <th width="17%">Pvt. Marka</th>
                    <th class="text-right" width="5%">Nugs</th>
                    <th width="15%">Item Name</th>
                    <th width="12%">To Station</th>
                    <th width="19%">Consignee Name</th>
                    <th class="text-right" width="8%">Weight</th>
                    <th class="text-right" width="10%">Freight</th>
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
                            <td class="text-right">{{ $index + 1 }}</td>
                            <td>{{ $detail->bilty->bilty_number ?? '—' }}</td>
                            <td>{{ $detail->bilty->private_marka ?? '—' }}</td>
                            <td class="text-right">{{ number_format($nugs, 0) }}</td>
                            <td>{{ $detail->bilty->biltyDetails->map(fn($d) => $d->good?->name)->filter()->unique()->join(', ') ?: '—' }}</td>
                            <td>{{ $detail->bilty->toStation->name ?? '—' }}</td>
                            <td>{{ $detail->bilty->consignee->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($weight, 2) }}</td>
                            <td class="text-right">₹ {{ number_format($freight, 2) }}</td>
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
                            <td class="text-right">{{ $index + 1 }}</td>
                            <td>{{ $inward->gr_no ?? 'GR-' . $inward->id }}</td>
                            <td>{{ $inward->private_marka ?? '—' }}</td>
                            <td class="text-right">{{ number_format($nugs, 0) }}</td>
                            <td>{{ $inward->good->name ?? '—' }}</td>
                            <td>{{ $inward->toStation->name ?? '—' }}</td>
                            <td>{{ $inward->consignee->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($weight, 2) }}</td>
                            <td class="text-right">₹ {{ number_format($freight, 2) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No goods loaded.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($challan->details->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3">Total :-</td>
                        <td class="text-right">{{ number_format($totalNugs, 0) }}</td>
                        <td colspan="3"></td>
                        <td class="text-right">{{ number_format($totalWeight, 2) }}</td>
                        <td class="text-right">₹ {{ number_format($totalBookingFreight + $totalCrossingFreight, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <div class="row">
            <div class="col-6 box" style="border-right: none; height: auto;">
                <div>
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr>
                            <td>Total Booking Freight</td>
                            <td class="text-right">₹ {{ number_format($challan->booking_freight ?? $totalBookingFreight, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Crossing Freight</td>
                            <td class="text-right">₹ {{ number_format($challan->crossing_freight ?? $totalCrossingFreight, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-right">₹ {{ number_format($totalBookingFreight + $challan->crossing_freight, 2) }}</td>
                        </tr>
                        <tr>
                            <td>GST Tax</td>
                            <td class="text-right">₹ {{ number_format($challan->gst ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Paid(Cartage/Dalali/PF)</td>
                            <td class="text-right">₹ {{ number_format($challan->cartage_dalali_pf ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid Bilty</td>
                            <td class="text-right">₹ {{ number_format($challan->paid_amount ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-6 box">
                <div>
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr>
                            <td>Labour</td>
                            <td class="text-right">₹ {{ number_format($challan->labour_charge ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Kat</td>
                            <td class="text-right">₹ {{ number_format($challan->kat ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Home Delivery</td>
                            <td class="text-right">₹ {{ number_format($challan->home_delivery_charge ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Truck Freight</td>
                            <td class="text-right">₹ {{ number_format($challan->truck_freight ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-right">₹ {{ number_format($challan->truck_freight + $challan->labour_charge + $challan->kat + $challan->home_delivery_charge, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Receive</td>
                            <td class="text-right">₹ {{ number_format($challan->cash_received ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Paid</td>
                            <td class="text-right">₹ {{ number_format($challan->cash_payment ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: 10px;">
            <div><strong>Total Amount (Rs.) :-</strong> {{ number_format(abs($challan->total_amount ?? 0), 2) }} {{ $challan->total_amount > 0 ? 'Dr' : 'Cr' }} To {{ $challan->payByAgent->name ?? '—' }}</div>
            <div><strong>Delivery At :-</strong> {{ $challan->deliveryAtAgent->name ?? '—' }}</div>
                <div><strong>Remark :-</strong> {{ $challan->remark ?? '—'}}</div>
        </div>

        <div class="footer">
            <div class="signature" style="width: 27%">Signature of Driver or Truck Owner</div>
            <div class="signature" style="width: 27%">Loaded By</div>
            <div class="signature" style="width: 27%">Prepared By: {{ $challan->creator->name ?? '—' }}</div>
        </div>

    </div>
    @include('partials.print-brand-footer')
    </div>
</body>
</html>

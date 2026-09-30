<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Main Challan - {{ $main_challan->main_challan_number }}</title>
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
        .col-3 { width: 25%; box-sizing: border-box; }
        .col-4 { width: 33.33%; box-sizing: border-box; }
        .col-5 { width: 41.66%; box-sizing: border-box; }
        .col-6 { width: 50%; box-sizing: border-box; }
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

        @media print {
            @page { margin: 10mm; }
            body { padding: 0; }
            .print-container { max-width: none; border: 1px solid #000; padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
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

        $challanRows = collect();
        foreach ($main_challan->details as $mainDetail) {
            $challan = $mainDetail->challan;
            if (! $challan) {
                continue;
            }

            $nugs = (float) ($challan->total_packages ?? 0);
            $weight = (float) ($challan->total_weight ?? 0);
            $bookingFreight = (float) ($challan->booking_freight ?? 0);

            if ($challan->details) {
                $nugsFromDetails = 0;
                $weightFromDetails = 0;
                $bookingFromDetails = 0;

                foreach ($challan->details as $challanDetail) {
                    if ($challanDetail->bilty?->biltyDetails) {
                        $nugsFromDetails += (float) $challanDetail->bilty->biltyDetails->sum('no_of_packages');
                        $weightFromDetails += (float) $challanDetail->bilty->biltyDetails->sum('gross_weight');
                        $bookingFromDetails += (float) ($challanDetail->bilty->freight ?? 0);
                    }

                    if ($challanDetail->inwardRegisterDetail) {
                        $inward = $challanDetail->inwardRegisterDetail;
                        $nugsFromDetails += (float) ($inward->no_of_packages ?? 0);
                        $weightFromDetails += (float) ($inward->weight ?? 0);
                    }
                }

                if ($nugsFromDetails > 0) {
                    $nugs = $nugsFromDetails;
                }
                if ($weightFromDetails > 0) {
                    $weight = $weightFromDetails;
                }
                if ($bookingFreight <= 0 && $bookingFromDetails > 0) {
                    $bookingFreight = $bookingFromDetails;
                }
            }

            $paidFreight = (float) ($challan->paid_amount ?? 0);
            $toPayFreight = (float) ($challan->to_pay_amount ?? 0);
            $labour = (float) ($challan->labour_charge ?? 0);
            $crossing = (float) ($challan->crossing_freight ?? 0);
            $kat = (float) ($challan->kat ?? 0);
            $homeDelivery = (float) ($challan->home_delivery_charge ?? 0);

            // Aligns with main-challan row settlement: to-pay + crossing − labour − kat − HD
            $balanceFreight = $toPayFreight + $crossing - $labour - $kat - $homeDelivery;

            $challanRows->push([
                'challan_number' => $challan->challan_number ?? '—',
                'to_station' => $challan->toStation?->name ?? '—',
                'nugs' => $nugs,
                'weight' => $weight,
                'paid_freight' => $paidFreight,
                'to_pay_freight' => $toPayFreight,
                'labour' => $labour,
                'crossing' => $crossing,
                'kat' => $kat,
                'home_delivery' => $homeDelivery,
                'balance_freight' => $balanceFreight,
                'booking_freight' => $bookingFreight,
            ]);
        }

        $totalNugs = $challanRows->sum('nugs');
        $totalWeight = $challanRows->sum('weight');
        $totalBookingFreight = $challanRows->sum('booking_freight');
        $totalCrossingFreight = $challanRows->sum('crossing');
    @endphp
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
                    <div class="header-doc-title">MAIN CHALLAN</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-5">
                <strong>Main Challan No:</strong> <span style="font-weight: bold; font-size: 13px;">{{ $main_challan->main_challan_number ?? '—' }}</span><br>
                <strong>Date:</strong> <span style="font-weight: bold; font-size: 13px;">{{ $main_challan->date ? date('d-M-Y', strtotime($main_challan->date)) : '—' }}</span>
            </div>
            <div class="col-4">
                <strong>Truck No:</strong> {{ $main_challan->truck->truck_number ?? '—' }}<br>
                <strong>Driver:</strong> {{ $main_challan->driver_name ?? '—' }}<br>
            </div>
            <div class="col-3">
                <strong>From:</strong> {{ $main_challan->fromStation->name ?? '—' }}<br>
                <strong>To:</strong> {{ $main_challan->toStation->name ?? '—' }}
            </div>
        </div>

        <table class="challan-table">
            <thead>
                <tr>
                    <th class="text-right" width="4%">S.N.</th>
                    <th width="12%">Challan No.</th>
                    <th width="14%">To Station</th>
                    <th class="text-right" width="6%">Nugs</th>
                    <th class="text-right" width="8%">Weight</th>
                    <th class="text-right" width="9%">Paid Frt</th>
                    <th class="text-right" width="9%">To Pay Frt</th>
                    <th class="text-right" width="12%">Lab/Cro</th>
                    <th class="text-right" width="12%">Kat/HD</th>
                    <th class="text-right" width="14%">Balance Freight</th>
                </tr>
            </thead>
            <tbody>
                @forelse($challanRows as $index => $row)
                    <tr>
                        <td class="text-right">{{ $index + 1 }}</td>
                        <td>{{ $row['challan_number'] }}</td>
                        <td>{{ $row['to_station'] }}</td>
                        <td class="text-right">{{ number_format($row['nugs'], 0) }}</td>
                        <td class="text-right">{{ number_format($row['weight'], 2) }}</td>
                        <td class="text-right">₹ {{ number_format($row['paid_freight'], 2) }}</td>
                        <td class="text-right">₹ {{ number_format($row['to_pay_freight'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['labour'] + $row['crossing'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['kat'] + $row['home_delivery'], 2) }}</td>
                        <td class="text-right">₹ {{ number_format($row['balance_freight'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No challans linked.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($challanRows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3">Total :-</td>
                        <td class="text-right">{{ number_format($totalNugs, 0) }}</td>
                        <td class="text-right">{{ number_format($totalWeight, 2) }}</td>
                        <td class="text-right">₹ {{ number_format($challanRows->sum('paid_freight'), 2) }}</td>
                        <td class="text-right">₹ {{ number_format($challanRows->sum('to_pay_freight'), 2) }}</td>
                        <td class="text-right">{{ number_format($challanRows->sum('labour') + $challanRows->sum('crossing'), 2) }}</td>
                        <td class="text-right">{{ number_format($challanRows->sum('kat') + $challanRows->sum('home_delivery'), 2) }}</td>
                        <td class="text-right">₹ {{ number_format($challanRows->sum('balance_freight'), 2) }}</td>
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
                            <td class="text-right">₹ {{ number_format($totalBookingFreight, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Crossing Freight</td>
                            <td class="text-right">₹ {{ number_format($main_challan->crossing_freight ?? $totalCrossingFreight, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-right">₹ {{ number_format($totalBookingFreight + ($main_challan->crossing_freight ?? $totalCrossingFreight), 2) }}</td>
                        </tr>
                        <tr>
                            <td>GST Tax</td>
                            <td class="text-right">₹ {{ number_format($main_challan->gst ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Paid(Cartage/Dalali/PF)</td>
                            <td class="text-right">₹ {{ number_format($main_challan->cartage_dalali_pf ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid Bilty</td>
                            <td class="text-right">₹ {{ number_format($main_challan->paid_amount ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-6 box">
                <div>
                    <table class="summary-table" style="width: 100%; margin-bottom: 0;">
                        <tr>
                            <td>Labour</td>
                            <td class="text-right">₹ {{ number_format($main_challan->labour_charge ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Kat</td>
                            <td class="text-right">₹ {{ number_format($main_challan->kat ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Home Delivery</td>
                            <td class="text-right">₹ {{ number_format($main_challan->home_delivery_charge ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Truck Freight</td>
                            <td class="text-right">₹ {{ number_format($main_challan->truck_freight ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-right">₹ {{ number_format(($main_challan->truck_freight ?? 0) + ($main_challan->labour_charge ?? 0) + ($main_challan->kat ?? 0) + ($main_challan->home_delivery_charge ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Receive</td>
                            <td class="text-right">₹ {{ number_format($main_challan->cash_received ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Paid</td>
                            <td class="text-right">₹ {{ number_format($main_challan->cash_paid ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: 10px;">
            <div><strong>Total Amount (Rs.) :-</strong> {{ number_format(abs($main_challan->total_amount ?? 0), 2) }} {{ ($main_challan->total_amount ?? 0) > 0 ? 'Dr' : 'Cr' }}</div>
            <div><strong>Remark :-</strong> {{ $main_challan->remark ?? '—'}}</div>
        </div>

        <div class="footer">
            <div class="signature" style="width: 27%">Signature of Driver or Truck Owner</div>
            <div class="signature" style="width: 27%">Loaded By</div>
            <div class="signature" style="width: 27%">Prepared By: {{ $main_challan->creator->name ?? '—' }}</div>
        </div>

        @include('partials.print-brand-footer')
    </div>
</body>
</html>

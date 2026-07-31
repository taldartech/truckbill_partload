<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Delivery Receipt - {{ $receipt->delivery_receipt_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 8px;
        }
        .sheet {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            /* border: 1px solid #000; */
        }
        .panel {
            width: 100%;
            padding: 8px 10px 10px;
            display: flex;
            flex-direction: column;
            border: 1px solid #000;
        }
        .panel + .panel {
            /* border-top: 1px solid #000; */
        }
        .header {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .header-logo img {
            max-height: 52px;
            max-width: 58px;
            display: block;
        }
        .header-company { flex: 1; text-align: center; }
        .header-company .name {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.15;
            color: #000;
        }
        .header-company .meta {
            font-size: 10px;
            margin-top: 2px;
            line-height: 1.35;
            color: #000;
        }
        .doc-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 4px 0 8px;
            color: #000;
            -webkit-text-stroke: 0.5px #000;
            text-transform: uppercase;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1.35fr 0.9fr;
            gap: 4px 10px;
            margin-bottom: 8px;
        }
        .field {
            display: flex;
            gap: 4px;
            line-height: 1.35;
        }
        .field .label {
            font-weight: 700;
            white-space: nowrap;
        }
        .field .value {
            flex: 1;
            min-height: 14px;
        }
        .charges {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .charges th,
        .charges td {
            border: 1px solid #000;
            padding: 3px 6px;
        }
        .charges th {
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.3px;
        }
        .charges td.amt { text-align: right; width: 38%; }
        .charges tr.total td {
            font-weight: 700;
            border-top-width: 2px;
        }
        .mode-tag {
            float: left;
            font-weight: 700;
            text-transform: uppercase;
        }
        .gate-totals {
            margin-top: 18px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        .gate-totals .line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 12px;
        }
        .gate-totals .sum {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            padding: 4px 0;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
        }
        .panel-body { flex: 1; }
        .panel-footer {
            margin-top: 12px;
            border-top: 1px solid #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-end;
        }
        .bankers {
            font-size: 9px;
            line-height: 1.35;
            max-width: 68%;
        }
        .prepared {
            text-align: right;
            font-size: 10px;
            min-width: 90px;
        }
        .prepared .name {
            font-weight: 700;
            margin-bottom: 2px;
        }
        .muted { color: #000; }

        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            body { padding: 0; color: #000; }
            .sheet { max-width: none; border-color: #000; }
            .panel + .panel { border-top-color: #000; }
            .header,
            .charges th,
            .charges td,
            .gate-totals,
            .panel-footer,
            .doc-title,
            .header-company .name,
            .header-company .meta,
            .field .value {
                color: #000;
                border-color: #000;
            }
            .print-brand-footer { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
@php
    $logoPath = $branch?->resolvedLogo();
    $logoUrl = null;
    if ($logoPath) {
        $logoUrl = str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')
            ? $logoPath
            : url($logoPath);
    }

    $branchAddress = trim(
        collect([
            $branch?->address,
            $branch?->city?->name,
            $branch?->state?->name,
            $branch?->pin_code,
        ])->filter()->implode(', ')
    );

    $phone = $branch?->contact_number ?: ($company?->contact_number ?? '');
    $pan = $company?->pan_no ?? '';

    $bankName = $branch?->bank_name ?: ($company?->bank_name ?? '');
    $accountNo = $branch?->account_number ?: ($company?->account_number ?? '');
    $ifsc = $branch?->ifsc_code ?: ($company?->ifsc_code ?? '');
    $bankers = collect([
        $bankName ? $bankName : null,
        $accountNo ? 'A/c No. : '.$accountNo : null,
        $ifsc ? 'IFSC : '.$ifsc : null,
    ])->filter()->implode(' + ');

    $receiptDate = $receipt->date ? date('d/m/Y', strtotime((string) $receipt->date)) : '—';
    $consignee = $receipt->consignee;
    $consigneeAddress = trim((string) ($consignee?->address ?? ''));
    $consigneeCity = $consignee?->city?->name ?? '';

    $haste = $receipt->haste ?: ($grSource['private_marka'] ?? null);
    $privateMark = $grSource['private_marka'] ?? null;
    $packages = $grSource['packages'];
    $weight = $grSource['weight'];
    $basis = $grSource['basis_of_booking'] ?: match ($receipt->mode_of_payment) {
        'Due' => 'TO PAY',
        'Cash' => 'PAID',
        'Bank' => 'PAID',
        default => $receipt->mode_of_payment,
    };

    $thella = (float) ($receipt->carriage_charge ?? 0);
    $grandTotal = (float) ($receipt->final_amount ?? $receipt->total_amount ?? 0);
    $freightTotal = max($grandTotal - $thella, 0);
    $preparedBy = $branch?->contact_person ?: ($company?->contact_person ?? '');

    $fmt = fn ($n) => number_format((float) ($n ?? 0), 2);
    $fmtQty = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    };
@endphp

<div class="sheet">
    {{-- Delivery Receipt --}}
    <div class="panel">
        <div class="header">
            @if ($logoUrl)
                <div class="header-logo">
                    <img src="{{ $logoUrl }}" alt="Logo">
                </div>
            @endif
            <div class="header-company">
                <div class="name">{{ $company->name ?? '—' }}</div>
                @if ($branchAddress)
                    <div class="meta">{{ $branchAddress }}</div>
                @endif
                <div class="meta">
                    @if ($pan) PAN : {{ $pan }} @endif
                    @if ($pan && $phone) &nbsp; @endif
                    @if ($phone) Mob. : {{ $phone }} @endif
                </div>
            </div>
        </div>

        <div class="doc-title">DELIVERY RECEIPT</div>

        <div class="meta-row">
            <div>Delivery No. : {{ $receipt->delivery_receipt_no }}</div>
            <div>Date : {{ $receiptDate }}</div>
        </div>

        <div class="panel-body">
            <div class="info-grid">
                <div class="field"><span class="label">Consignee :</span><span class="value">{{ $consignee?->name ?? '—' }}</span></div>
                <div class="field"><span class="label">Office Name :</span><span class="value"></span></div>

                <div class="field"><span class="label">Consignor :</span><span class="value">{{ $grSource['consignor'] ?? '—' }}</span></div>
                <div class="field"><span class="label">Date :</span><span class="value">{{ $grSource['gr_date'] ?? '—' }}</span></div>

                <div class="field"><span class="label">Haste Name :</span><span class="value">{{ $haste ?: '—' }}</span></div>
                <div class="field"><span class="label">Rec. Date :</span><span class="value">{{ $grSource['received_date'] ?? '—' }}</span></div>

                <div class="field"><span class="label">G.R. No :</span><span class="value">{{ $receipt->gr_no_bilty_no }}</span></div>
                <div class="field"><span class="label">Nugs :</span><span class="value">{{ $fmtQty($packages) }}</span></div>

                <div class="field"><span class="label">From :</span><span class="value">{{ $grSource['from_station'] ?? '—' }}</span></div>
                <div class="field"><span class="label">Weight :</span><span class="value">{{ $fmtQty($weight) }}</span></div>

                <div class="field"><span class="label">Goods Name :</span><span class="value">{{ $grSource['goods_name'] ?? '—' }}</span></div>
                <div></div>

                <div class="field"><span class="label">Pvt. Mark :</span><span class="value">{{ $privateMark ?: '—' }}</span></div>
                <div></div>
            </div>

            <table class="charges">
                <thead>
                    <tr>
                        <th>PARTICULARS</th>
                        <th>AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basis of Booking</td>
                        <td class="amt">{{ strtoupper((string) $basis) }}</td>
                    </tr>
                    <tr>
                        <td>Freight</td>
                        <td class="amt">{{ $fmt($receipt->freight) }}</td>
                    </tr>
                    <tr>
                        <td>Handling Ch.</td>
                        <td class="amt">{{ $fmt($receipt->handling_charge) }}</td>
                    </tr>
                    <tr>
                        <td>Delivery Cha.</td>
                        <td class="amt">{{ $fmt($receipt->delivery_charge) }}</td>
                    </tr>
                    <tr>
                        <td>Stationery Ch.</td>
                        <td class="amt">{{ $fmt($receipt->stationery_charge) }}</td>
                    </tr>
                    <tr>
                        <td>Demurrage</td>
                        <td class="amt">{{ $fmt($receipt->demurrage) }}</td>
                    </tr>
                    <tr>
                        <td>OSC</td>
                        <td class="amt">{{ $fmt($receipt->other_charge) }}</td>
                    </tr>
                    <tr>
                        <td>Less</td>
                        <td class="amt">{{ $fmt($receipt->less) }}</td>
                    </tr>
                    @if ((float) ($receipt->gst ?? 0) > 0)
                        <tr>
                            <td>GST ({{ $fmt($receipt->gst_rate) }}%)</td>
                            <td class="amt">{{ $fmt($receipt->gst) }}</td>
                        </tr>
                    @endif
                    <tr class="total">
                        <td>
                            TOTAL
                        </td>
                        <td class="amt">{{ $fmt($freightTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="panel-footer">
            <div class="bankers">
                @if ($bankers)
                    <strong>Our Banker's :</strong> {{ $bankers }}
                @endif
            </div>
            <div class="prepared">
                @if ($preparedBy)
                    <div class="name">{{ strtoupper($preparedBy) }}</div>
                @endif
                <div>Prepared By</div>
            </div>
        </div>
    </div>

    {{-- Gate Pass --}}
    <div class="panel">
        <div class="header">
            @if ($logoUrl)
                <div class="header-logo">
                    <img src="{{ $logoUrl }}" alt="Logo">
                </div>
            @endif
            <div class="header-company">
                <div class="name">{{ $company->name ?? '—' }}</div>
                @if ($branchAddress)
                    <div class="meta">{{ $branchAddress }}</div>
                @endif
                <div class="meta">
                    @if ($pan) PAN : {{ $pan }} @endif
                    @if ($pan && $phone) &nbsp; @endif
                    @if ($phone) Mob. : {{ $phone }} @endif
                </div>
            </div>
        </div>

        <div class="doc-title">GATE PASS</div>

        <div class="meta-row">
            <div>Gate Pass No. : {{ $receipt->delivery_receipt_no }}</div>
            <div>Date : {{ $receiptDate }}</div>
        </div>

        <div class="panel-body">
            <div class="info-grid">
                <div class="field" style="grid-column: 1 / -1;"><span class="label">Consignee :</span><span class="value">{{ $consignee?->name ?? '—' }}</span></div>

                <div class="field"><span class="label">Address :</span><span class="value">{{ $consigneeAddress ?: '—' }}</span></div>
                <div class="field"><span class="label">Date :</span><span class="value">{{ $grSource['gr_date'] ?? '—' }}</span></div>

                <div class="field"><span class="label">City :</span><span class="value">{{ $consigneeCity ?: '—' }}</span></div>
                <div></div>

                <div class="field"><span class="label">Haste Name :</span><span class="value">{{ $haste ?: '—' }}</span></div>
                <div class="field"><span class="label">Nugs :</span><span class="value">{{ $fmtQty($packages) }}</span></div>

                <div class="field"><span class="label">G.R. No :</span><span class="value">{{ $receipt->gr_no_bilty_no }}</span></div>
                <div class="field"><span class="label">Weight :</span><span class="value">{{ $fmtQty($weight) }}</span></div>

                <div class="field"><span class="label">From :</span><span class="value">{{ $grSource['from_station'] ?? '—' }}</span></div>
                <div></div>

                <div class="field"><span class="label">Goods Name :</span><span class="value">{{ $grSource['goods_name'] ?? '—' }}</span></div>
                <div></div>

                <div class="field"><span class="label">Pvt. Mark :</span><span class="value">{{ $privateMark ?: '—' }}</span></div>
                <div></div>
            </div>

            <div class="gate-totals">
                <div class="line">
                    <span>TOTAL FREIGHT :</span>
                    <span>{{ strtoupper((string) $receipt->mode_of_payment) }} &nbsp; {{ $fmt($freightTotal) }}</span>
                </div>
                <div class="line">
                    <span>THELLA BHADA :</span>
                    <span>{{ $fmt($thella) }}</span>
                </div>
                <div class="sum">
                    <span>TOTAL :</span>
                    <span>{{ $fmt($grandTotal) }}</span>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <div class="bankers">
                @if ($bankers)
                    <strong>Our Banker's :</strong> {{ $bankers }}
                @endif
            </div>
            <div class="prepared">
                @if ($preparedBy)
                    <div class="name">{{ strtoupper($preparedBy) }}</div>
                @endif
                <div>Prepared By</div>
            </div>
        </div>
    </div>
</div>

@include('partials.print-brand-footer')
</body>
</html>

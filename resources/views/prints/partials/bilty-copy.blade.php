@php
    use App\Support\BiltyPrint;

    $logoPath = $branch?->resolvedLogo();
    $logoUrl = null;
    if ($logoPath) {
        $logoUrl = str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')
            ? $logoPath
            : url($logoPath);
    }

    $branchAddress = trim(
        collect([$branch?->address, $branch?->city?->name, $branch?->pin_code])
            ->filter()
            ->implode(', '),
    );

    $phones = collect([$branch?->contact_number, $company?->contact_number])
        ->filter()
        ->unique()
        ->implode(', ');

    $email = $branch?->email ?: ($company?->email ?? '');

    $transporterGst = ($branch && $branch->has_gst && $branch->gst_number)
        ? $branch->gst_number
        : (($company && $company->has_gst && $company->gst_number) ? $company->gst_number : ($company?->gst_number ?? ''));

    $transporterPan = $company?->pan_no ?? '';

    $itemRows = $printData['detail_rows']->values();
    $chargeRows = collect($printData['sidebar_charges'])->values();
    $bodyRowCount = max($itemRows->count(), $chargeRows->count());
    $totals = $printData['detail_totals'];

    $ewayBillText = $bilty->ewayBills
        ->filter(fn ($eway) => ! empty($eway->eway_bill_no))
        ->map(function ($eway) {
            $text = $eway->eway_bill_no;

            if (! empty($eway->eway_bill_valid_till)) {
                $text .= ' ('.date('d-M-Y', strtotime($eway->eway_bill_valid_till)).')';
            }

            return $text;
        })
        ->implode(', ');

    $formatAmount = fn (?float $amount) => number_format((float) $amount, 2);
    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    };

    $paymentStamp = $showAmount
        ? BiltyPrint::paymentStampLabel($bilty->payment_type)
        : 'TBB';
    $showPaymentStamp = ! $showAmount || BiltyPrint::isPaymentType($bilty->payment_type, 'TBB');

    $termsText = trim((string) ($biltyNote ?? ''));
@endphp

<div class="print-copy">
    <div class="bi-sheet">
        <div class="bi-fixed-top">
            <table class="bi-header">
                <tr>
                    @if ($logoUrl)
                        <td class="bi-logo">
                            <img src="{{ $logoUrl }}" alt="Logo">
                        </td>
                    @endif
                    <td>
                        <div class="bi-company-name">{{ $company->name ?? '—' }}</div>
                        @if ($branchAddress)
                            <div class="bi-company-meta">{{ $branchAddress }}</div>
                        @endif
                        <div class="bi-company-meta">
                            @if ($phones)
                                Ph.: {{ $phones }}
                            @endif
                            @if ($email)
                                @if ($phones)
                                    &nbsp;
                                @endif
                                Email: {{ $email }}
                            @endif
                        </div>
                    </td>
                    <td class="w-22" style="text-align:right;">
                        <div class="bi-doc-title">Bilty / Tax Invoice</div>
                        <div class="bi-payment-box">{{ $paymentStamp }}</div>
                        <div class="bi-copy-label">{{ $copyLabel }}</div>
                    </td>
                </tr>
            </table>

            <table class="bi-meta border-top border-bottom-none">
                <tr>
                    <td class="w-40" style="font-size:13px;">
                        <div>
                            <span class="lbl">Consignor:</span>
                            <span class="bi-party-name">{{ $bilty->consignor->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="lbl">GST:</span>
                            <span class="val">{{ $bilty->consignor->gst_number ?? '' }}</span>
                            &nbsp;&nbsp;
                            <span class="lbl">Mob:</span>
                            <span class="val">{{ $bilty->consignor->mobile ?? '' }}</span>
                        </div>
                        <div><span class="lbl">From Station:</span> <span class="val">{{ $bilty->fromStation->name ?? '—' }}</span></div>

                    </td>
                    <td class="w-40" style="font-size:13px;">
                        <div style="margin-top:3px;">
                            <span class="lbl">Consignee:</span>
                            <span class="bi-party-name">{{ $bilty->consignee->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="lbl">GST:</span>
                            <span class="val">{{ $bilty->consignee->gst_number ?? '' }}</span>
                            &nbsp;&nbsp;
                            <span class="lbl">Mob:</span>
                            <span class="val">{{ $bilty->consignee->mobile ?? '' }}</span>
                        </div>
                        <div><span class="lbl">To Station:</span> <span class="val">{{ $bilty->toStation->name ?? '—' }}</span></div>
                    </td>
                    <td class="w-18" style="font-size:13px;">
                        <div><span class="lbl">Bilty No:</span> <span class="val" style="font-size:16px;font-weight:bold;">{{ $bilty->bilty_number ?? '—' }}</span></div>
                        <div><span class="lbl">Date:</span> <span class="val">{{ $bilty->date ? date('d-M-Y', strtotime($bilty->date)) : '—' }}</span></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bi-body">
            <div class="bi-grid-wrap">
                @if ($showPaymentStamp)
                    <div class="bi-stamp"><span>{{ $paymentStamp }}</span></div>
                @endif
                <table class="bi-grid">
                    <colgroup>
                        <col class="bi-col-item">
                        <col class="bi-col-pkg">
                        <col class="bi-col-wt">
                        <col class="bi-col-chg">
                        <col class="bi-col-amt">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Packages</th>
                            <th>Weight</th>
                            <th>Charges</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($rowIndex = 0; $rowIndex < $bodyRowCount; $rowIndex++)
                            @php
                                $detail = $itemRows[$rowIndex] ?? null;
                                $charge = $chargeRows[$rowIndex] ?? null;
                            @endphp
                            @if ($showAmount || $rowIndex === 0)
                            <tr>
                                <td class="{{ $detail ? '' : 'bi-item-blank' }}">{{ $detail['good'] ?? '' }}</td>
                                <td class="text-right {{ $detail ? '' : 'bi-item-blank' }}">{{ $detail ? $formatQty($detail['no_of_packages']) : '' }}</td>
                                <td class="text-right {{ $detail ? '' : 'bi-item-blank' }}">{{ $detail ? $formatQty($detail['gross_weight']) : '' }}</td>
                                <td>{{ $charge['label'] ?? '' }}</td>
                                <td class="text-right">
                                    @if ($charge && $showAmount)
                                        {{ $formatAmount($charge['amount']) }}
                                    @endif
                                </td>
                            </tr>
                            @endif
                        @endfor
                        <tr class="bi-fill">
                            <td class="bi-item-blank"></td>
                            <td class="text-right bi-item-blank"></td>
                            <td class="text-right bi-item-blank"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-right">Total</td>
                            <td class="text-right">{{ $formatQty($totals['no_of_packages']) }}</td>
                            <td class="text-right">{{ $formatQty($totals['gross_weight']) }}</td>
                            <td class="text-right">Net Amount</td>
                            <td class="text-right">
                                @if ($showAmount)
                                    {{ $formatAmount($printData['total_amount']) }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="bi-fixed-bottom">
            <table class="border-top-none">
                <tr>
                    <td>
                        <span class="lbl">Private Marka:</span>
                        <span class="val">{{ $bilty->private_marka ?: '' }}</span>
                    </td>
                    <td style="width: 22.5%;">
                        <span class="lbl">Invoice No:</span>
                        <span class="val">{{ $bilty->invoice_number ?: '' }}</span>
                    </td>
                    <td style="width: 22.5%;">
                        <span class="lbl">Declared Value:</span>
                        <span class="val">{{ $bilty->declared_value ?: '' }}</span>
                    </td>
                </tr>
            </table>
            <table class="border-top-none" style="font-size:12px;">
                <tr>
                    <td style="width: 30%;">
                        <span class="lbl">Delivery At:</span>
                        <span class="val" style="font-weight: 600; font-size:15px">{{ $bilty->delivery_at ?: '—' }}</span>
                    </td>
                    <td colspan="2">
                        <span class="lbl">E-Way Bill:</span>
                        {{ $ewayBillText }}
                    </td>
                </tr>
            </table>
            <table class="no-border bi-sign-row" style="border:1px solid #000; border-top:none;">
                <tr>
                    <td class="w-60 bi-terms">
                        {!! nl2br(e($termsText)) !!}
                    </td>
                    <td class="bi-sign-for">
                        @if ($bilty->creator)
                            {{ $bilty->creator->name }}
                        @endif
                        <span style="margin-top: 30px; display: block;">For : {{ $company->name ?? 'Company' }}</span>
                        <span class="bi-footer-meta">Digitally managed by TruckBill (<a href="https://www.truckbill.in" target="_blank">www.truckbill.in</a>)</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

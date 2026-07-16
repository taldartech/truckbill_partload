@php
    use App\Support\BiltyPrint;

    $branchLogo = $branch?->resolvedLogo();
    $branchGst = $branch && $branch->has_gst && $branch->gst_number ? $branch->gst_number : '';
    $branchState = $branch?->state?->name ?? ($branch?->city?->name ?? '');
    $branchAddress = trim(
        collect([$branch?->address, $branch?->city?->name, $branch?->pin_code])
            ->filter()
            ->implode(', '),
    );

    $itemRows = $printData['detail_rows']->values();

    $ewayBillRows = $bilty->ewayBills
        ->filter(fn ($eway) => ! empty($eway->eway_bill_no))
        ->values();

    $formatAmount = fn(?float $amount) => number_format((float) $amount, 2);
@endphp

<div class="print-copy">
    <div class="lr-sheet">
        <div class="lr-fixed-top">
            {{-- Header --}}
            <table class="lr-table header-table">
                <tr>
                    @if ($branchLogo)
                        <td class="logo-cell text-center">
                            <img src="{{ $branchLogo }}" alt="Logo" class="branch-logo">
                        </td>
                    @endif
                    <td class="company-cell text-center">
                        <div class="company-name">{{ $company->name ?? 'TRANSPORT COMPANY' }}</div>
                        @if ($branchAddress)
                            <div class="branch-text">{{ $branchAddress }}</div>
                        @endif
                        <div class="branch-text">
                            @if ($branch?->email)
                                E-mail: {{ $branch->email }}
                            @endif
                            @if ($branch?->contact_number)
                                @if ($branch?->email)
                                    ,
                                @endif
                                Ph. {{ $branch->contact_number }}
                            @endif
                        </div>
                    </td>
                    <td class="gstin-cell">
                        <div class="gstin-title">GSTIN :</div>
                        <div class="gstin-value">{{ $branchGst ?: '—' }}</div>
                    </td>
                    <td class="from-to-cell">
                        <div class="from-to-label">From: <span
                                class="from-to-value">{{ $bilty->fromStation->name ?? '—' }}</span></div>
                        <div class="from-to-label">To: <span
                                class="from-to-value">{{ $bilty->toStation->name ?? '—' }}</span></div>
                    </td>
                </tr>
            </table>

            <table class="lr-table border-top-none">
                <tr>
                    <td class="w-40">
                        <span class="field-label">Bilty No.</span>
                        <span class="field-value"
                            style="font-size:12px;font-weight:bold;">{{ $bilty->bilty_number ?? '—' }}</span>
                    </td>
                    <td class="w-20">
                        <span class="field-label">STATE</span>
                        <div class="field-value">{{ $branchState ?: '—' }}</div>
                    </td>
                    <td class="w-20">
                        <span class="field-label">Date</span>
                        <div class="field-value">{{ $bilty->date ? date('d-m-Y', strtotime($bilty->date)) : '—' }}
                        </div>
                    </td>
                    <td class="w-20">
                        <span class="field-label">Delivery at</span>
                        <div class="field-value">{{ $bilty->delivery_at ?? '—' }}</div>
                    </td>
                </tr>
            </table>

            <table class="lr-table border-top-none border-bottom-none">
                <tr>
                    <td class="w-50">
                        <span class="field-label">Consignor M/s.</span>
                        <div class="field-value">{{ $bilty->consignor->name ?? '—' }}</div>
                        <span class="field-label">GSTIN No.</span>
                        <div class="field-value">{{ $bilty->consignor->gst_number ?? '—' }}</div>
                    </td>
                    <td class="w-50">
                        <span class="field-label">Consignee M/s.</span>
                        <div class="field-value">{{ $bilty->consignee->name ?? '—' }}</div>
                        <span class="field-label">GSTIN No.</span>
                        <div class="field-value">{{ $bilty->consignee->gst_number ?? '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items --}}
        <div class="lr-items-wrap">
            <table style="border-bottom: none !important;" class="lr-table items-table border-top-none border-bottom-none">
                <thead>
                    <tr>
                        <th class="w-15 items-head">No. of Article</th>
                        <th class="w-25 items-head">Method of Packing</th>
                        <th class="items-head">Description (SAID TO CONTAIN)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemRows as $detail)
                        <tr>
                            <td class="text-center">{{ $detail['no_of_packages'] ?? '—' }}</td>
                            <td>{{ $detail['package_type'] }}</td>
                            <td>{{ $detail['good'] }}</td>
                        </tr>
                    @endforeach
                    @if ($itemRows->isEmpty())
                        <tr>
                            <td class="text-center">—</td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endif
                    <tr class="items-fill">
                        <td class="w-15"></td>
                        <td class="w-25"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Lower body --}}
        <div class="lr-fixed-bottom">
            <table class="lr-table">
                <tr>
                    <td class="w-58" style="padding:0; border-bottom:none; border-left:none; border-top:none;">
                        <table class="lr-table border-right-none" style="margin:0;">
                            <tr>
                                <td>
                                    <span class="field-label">Invoice No.</span>
                                    <span class="field-value">{{ $bilty->invoice_number ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="field-label">Declared Value Rs.</span>
                                    <span class="field-value">
                                        @if ($bilty->declared_value)
                                            {{ $formatAmount($bilty->declared_value) }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @if ($ewayBillRows->isNotEmpty())
                                <tr>
                                    <td colspan="2" style="font-size:7px; line-height:1.3;">
                                        <span class="field-label">E-Way Bill{{ $ewayBillRows->count() > 1 ? 's' : '' }}:</span>
                                        @foreach ($ewayBillRows as $index => $eway)
                                            @if ($index > 0); @endif
                                            {{ $eway->eway_bill_no }}@if ($eway->eway_bill_valid_till)
                                                ({{ date('d-m-Y', strtotime($eway->eway_bill_valid_till)) }})
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="2">
                                    <span class="field-label">Private Marka (P.M.)</span>
                                    <span class="field-value">{{ $bilty->private_marka ?? '—' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="copy-stamp">{{ $copyLabel }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="checkbox-row">
                                    <strong>Basis of Booking :</strong>
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isPaymentType($bilty->payment_type, 'To Pay') ? 'checked' : '' }}"></span>
                                    TO PAY
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isPaymentType($bilty->payment_type, 'Paid') ? 'checked' : '' }}"></span>
                                    PAID
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isPaymentType($bilty->payment_type, 'TBB') ? 'checked' : '' }}"></span>
                                    TO BE BILLED
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isPaymentType($bilty->payment_type, 'FOC') ? 'checked' : '' }}"></span>
                                    FOC
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="checkbox-row">
                                    <strong>Liability of Pay :</strong>
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isLiability($bilty->gst_liability_by, 'Consignee') ? 'checked' : '' }}"></span>
                                    Consignee
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isLiability($bilty->gst_liability_by, 'Consignor') ? 'checked' : '' }}"></span>
                                    Consigner's
                                    <span
                                        class="checkbox-box {{ BiltyPrint::isLiability($bilty->gst_liability_by, 'Transporter') ? 'checked' : '' }}"></span>
                                    Transportar
                                </td>
                            </tr>
                            @if (!empty($biltyNote))
                                <tr>
                                    <td colspan="2" class="note-text">{!! nl2br(e($biltyNote)) !!}</td>
                                </tr>
                            @endif
                            {{-- <tr>
                            <td colspan="2" class="note-text" style="font-size:6px;">
                                COMPANY IS NOT RESPONSIBLE FOR LEAKAGE OR BREAKAGE
                            </td>
                        </tr> --}}
                        </table>
                    </td>
                    <td class="w-42" style="padding:0; vertical-align:top; border-bottom:none; border-right:none; border-top:none;">
                        <table class="lr-table charges-side border-left-none" style="margin:0; height:100%;">
                            <tr>
                                <td colspan="2" style="font-size:7px;">
                                    Weight Kgs/Mt. Actual / Charged :
                                    {{ number_format($printData['detail_totals']['gross_weight'], 2) }} /
                                    {{ number_format($printData['detail_totals']['charged_weight'], 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-size:7px;">
                                    Rate per Kg. / Mt. :
                                    @if ($showAmount && $itemRows->isNotEmpty())
                                        {{ $itemRows->first()['rate'] !== null ? number_format((float) $itemRows->first()['rate'], 2) : '—' }}
                                    @elseif(!$showAmount)
                                        TBB
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label"></td>
                                <td class="money"><strong>Amount (Rs.)</strong></td>
                            </tr>
                            @if ($showAmount)
                                @foreach ($printData['lr_charge_lines'] as $line)
                                    <tr>
                                        <td class="label">{{ $line['label'] }}</td>
                                        <td class="money">{{ $formatAmount($line['amount']) }}</td>
                                    </tr>
                                @endforeach
                                @if ((float) $printData['charge_total'] != 0)
                                    <tr>
                                        <td class="label">TOTAL</td>
                                        <td class="money">{{ $formatAmount($printData['charge_total']) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $printData['sgst'] != 0)
                                    <tr>
                                        <td class="label">SGST</td>
                                        <td class="money">{{ $formatAmount($printData['sgst']) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $printData['cgst'] != 0)
                                    <tr>
                                        <td class="label">CGST</td>
                                        <td class="money">{{ $formatAmount($printData['cgst']) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $printData['igst'] != 0)
                                    <tr>
                                        <td class="label">IGST</td>
                                        <td class="money">{{ $formatAmount($printData['igst']) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $printData['less_advance'] != 0)
                                    <tr>
                                        <td class="label">LESS ADVANCE</td>
                                        <td class="money">{{ $formatAmount(-1 * $printData['less_advance']) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $printData['total_amount'] != 0)
                                    <tr class="grand-row">
                                        <td class="label">GRAND TOTAL</td>
                                        <td class="money">{{ $formatAmount($printData['total_amount']) }}</td>
                                    </tr>
                                @endif
                            @else
                                <tr>
                                    <td class="label">Total Freight</td>
                                    <td class="money">TBB</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>

            <div class="footer-sign">
                <div class="entered-by-line">@if($bilty->creator) Entered By: {{ $bilty->creator->name }} @endif</div>
                <div class="for-line">For: {{ $company->name ?? 'Company' }}</div>
            </div>
        </div>

        <div class="brand-border">Digitally managed by TruckBill (<a href="https://www.truckbill.in" target="_blank" style="color: #000; text-decoration: none;">www.truckbill.in</a>)</div>
    </div>
</div>

<?php

namespace App\Support;

/**
 * Server-side GST arithmetic shared by bilties, invoices and delivery receipts.
 * All amounts are rounded to paise; CGST/SGST split the tax so they always sum to it.
 */
final class GstCalculator
{
    public static function amount(float $taxable, float $ratePercent): float
    {
        if ($taxable <= 0 || $ratePercent <= 0) {
            return 0.0;
        }

        return round($taxable * $ratePercent / 100, 2);
    }

    /**
     * Unknown states are treated as intra-state (CGST + SGST).
     */
    public static function isInterState(?int $supplierStateId, ?int $recipientStateId): bool
    {
        return $supplierStateId !== null
            && $recipientStateId !== null
            && $supplierStateId !== $recipientStateId;
    }

    /**
     * @return array{cgst: float, sgst: float, igst: float}
     */
    public static function split(float $gstAmount, ?int $supplierStateId, ?int $recipientStateId): array
    {
        $paise = (int) round($gstAmount * 100);

        if ($paise <= 0) {
            return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0];
        }

        if (self::isInterState($supplierStateId, $recipientStateId)) {
            return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => $paise / 100];
        }

        $cgst = intdiv($paise, 2);

        return ['cgst' => $cgst / 100, 'sgst' => ($paise - $cgst) / 100, 'igst' => 0.0];
    }

    public const DELIVERY_RECEIPT_CHARGES = [
        'freight', 'handling_charge', 'delivery_charge', 'stationery_charge',
        'demurrage', 'other_charge', 'carriage_charge',
    ];

    /**
     * Mirrors the delivery receipt form: GST is charged on all charges or on
     * charges other than freight (gst_on = delivery_charge).
     *
     * @param  array<string, mixed>  $receipt
     * @return array{total_amount: float, gst_base: float, gst_rate: float, gst: float, gst_in_total: float, final_amount: float}
     */
    public static function forDeliveryReceipt(array $receipt, float $ratePercent, float $exemptionLimit): array
    {
        $subTotal = round(collect(self::DELIVERY_RECEIPT_CHARGES)
            ->sum(fn (string $field) => (float) ($receipt[$field] ?? 0)), 2);

        $base = ($receipt['gst_on'] ?? null) === 'delivery_charge'
            ? max(round($subTotal - (float) ($receipt['freight'] ?? 0), 2), 0.0)
            : $subTotal;

        $payBy = $receipt['gst_pay_by'] ?? null;
        $rate = $payBy === 'Exempted' ? 0.0 : $ratePercent;
        if ($base <= $exemptionLimit) {
            $rate = 0.0;
        }

        $gst = self::amount($base, $rate);
        $gstInTotal = $payBy === 'Transporter' ? $gst : 0.0;

        return [
            'total_amount' => $subTotal,
            'gst_base' => $base,
            'gst_rate' => $rate,
            'gst' => $gst,
            'gst_in_total' => $gstInTotal,
            'final_amount' => round($subTotal + $gstInTotal - (float) ($receipt['less'] ?? 0), 2),
        ];
    }

    /**
     * Mirrors the bilty form: GST applies to all charges above the exemption limit,
     * is added to the total only when the transporter is liable, and advance is deducted last.
     *
     * @param  array<string, mixed>  $bilty  Bilty attributes (freight, charges, addon_charges, gst, gst_liability_by, less_advance_received)
     * @return array{charges_total: float, gst_rate: float, gst_amount: float, cgst_amount: float, sgst_amount: float, igst_amount: float, gst_in_total: float, amount: float}
     */
    public static function forBilty(array $bilty, float $exemptionLimit, ?int $supplierStateId, ?int $recipientStateId): array
    {
        $addonTotal = collect($bilty['addon_charges'] ?? [])
            ->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));

        $chargesTotal = round(
            (float) ($bilty['freight'] ?? 0)
            + (float) ($bilty['bilty_charge'] ?? 0)
            + (float) ($bilty['surcharge'] ?? 0)
            + (float) ($bilty['handling_charge'] ?? 0)
            + (float) ($bilty['risk_dd_charge'] ?? 0)
            + (float) ($bilty['cartage'] ?? 0)
            + $addonTotal,
            2,
        );

        $liability = $bilty['gst_liability_by'] ?? null;
        $rate = $liability === 'Exempted' ? 0.0 : (float) ($bilty['gst'] ?? 0);
        if ($chargesTotal <= $exemptionLimit) {
            $rate = 0.0;
        }

        $gstAmount = self::amount($chargesTotal, $rate);
        $split = self::split($gstAmount, $supplierStateId, $recipientStateId);
        $gstInTotal = $liability === 'Transporter' ? $gstAmount : 0.0;
        $advance = round((float) ($bilty['less_advance_received'] ?? 0), 2);

        return [
            'charges_total' => $chargesTotal,
            'gst_rate' => $rate,
            'gst_amount' => $gstAmount,
            'cgst_amount' => $split['cgst'],
            'sgst_amount' => $split['sgst'],
            'igst_amount' => $split['igst'],
            'gst_in_total' => $gstInTotal,
            'amount' => round($chargesTotal + $gstInTotal - $advance, 2),
        ];
    }
}

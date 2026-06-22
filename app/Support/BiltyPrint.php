<?php

namespace App\Support;

use App\Models\Bilty;
use App\Models\Branch;

class BiltyPrint
{
    public const COPY_KEYS = ['consignor', 'consignee', 'driver', 'office'];

    public const COPY_LABELS = [
        'consignor' => 'CONSIGNOR COPY',
        'consignee' => 'CONSIGNEE COPY',
        'driver' => 'DRIVER COPY',
        'office' => 'OFFICE COPY',
    ];

    public static function shouldShowAmount(Bilty $bilty, bool $copyShowAmount, ?bool $printShowAmount, bool $askAmountAtPrint): bool
    {
        if (strtoupper((string) ($bilty->payment_type ?? '')) === 'TBB') {
            return false;
        }

        if ($askAmountAtPrint && $printShowAmount === false) {
            return false;
        }

        return $copyShowAmount;
    }

    public static function basisOfBookingLabel(?string $paymentType): string
    {
        return match (strtoupper(trim((string) $paymentType))) {
            'TO PAY' => 'To Pay',
            'PAID' => 'Paid',
            'TBB' => 'To be billed',
            'FOC' => 'FOC',
            default => $paymentType ?: '—',
        };
    }

    public static function isPaymentType(?string $paymentType, string $expected): bool
    {
        return strtoupper(trim((string) $paymentType)) === strtoupper($expected);
    }

    public static function isLiability(?string $liability, string $expected): bool
    {
        return strtoupper(trim((string) $liability)) === strtoupper($expected);
    }

    /**
     * @return array<int, array{key: string, label: string, show_amount: bool}>
     */
    public static function copiesToPrint(Branch $branch, Bilty $bilty, ?bool $printShowAmount = null): array
    {
        $config = $branch->resolvedBiltyPrintConfig();
        $askAmountAtPrint = (bool) ($config['ask_amount_at_print'] ?? false);
        $copies = [];

        foreach (self::COPY_KEYS as $key) {
            $copyConfig = $config['copies'][$key] ?? ['enabled' => false, 'show_amount' => false];

            if (! ($copyConfig['enabled'] ?? false)) {
                continue;
            }

            $copies[] = [
                'key' => $key,
                'label' => self::COPY_LABELS[$key],
                'show_amount' => self::shouldShowAmount(
                    $bilty,
                    (bool) ($copyConfig['show_amount'] ?? false),
                    $printShowAmount,
                    $askAmountAtPrint
                ),
            ];
        }

        if ($copies !== []) {
            return $copies;
        }

        foreach (self::COPY_KEYS as $key) {
            $default = Branch::defaultBiltyPrintConfig()['copies'][$key];

            $copies[] = [
                'key' => $key,
                'label' => self::COPY_LABELS[$key],
                'show_amount' => self::shouldShowAmount(
                    $bilty,
                    (bool) ($default['show_amount'] ?? false),
                    $printShowAmount,
                    $askAmountAtPrint
                ),
            ];
        }

        return $copies;
    }
}

<?php

namespace App\Support;

use App\Models\Bilty;
use App\Models\Branch;
use App\Models\BranchSetting;

class BiltyPrint
{
    public const COPY_KEYS = BranchSetting::COPY_KEYS;

    public const COPY_LABELS = [
        'consignor' => 'CONSIGNOR COPY',
        'consignee' => 'CONSIGNEE COPY',
        'driver' => 'DRIVER COPY',
        'office' => 'OFFICE COPY',
    ];

    /**
     * @param  array{to_pay: bool, paid: bool, tbb: bool, foc: bool}  $showAmountByBob
     */
    public static function shouldShowAmount(Bilty $bilty, array $showAmountByBob): bool
    {
        $bobKey = self::paymentTypeToBobKey($bilty->payment_type);

        if ($bobKey === null) {
            return false;
        }

        return (bool) ($showAmountByBob[$bobKey] ?? false);
    }

    public static function paymentTypeToBobKey(?string $paymentType): ?string
    {
        return match (strtoupper(trim((string) $paymentType))) {
            'TO PAY' => 'to_pay',
            'PAID' => 'paid',
            'TBB' => 'tbb',
            'FOC' => 'foc',
            default => null,
        };
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
    public static function copiesToPrint(Branch $branch, Bilty $bilty): array
    {
        $branch->loadMissing('settings');
        $config = $branch->resolvedBiltyPrintConfig();
        $copies = [];

        foreach (self::COPY_KEYS as $key) {
            $copyConfig = $config['copies'][$key] ?? ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)];

            if (! ($copyConfig['enabled'] ?? false)) {
                continue;
            }

            $copies[] = [
                'key' => $key,
                'label' => self::COPY_LABELS[$key],
                'show_amount' => self::shouldShowAmount(
                    $bilty,
                    $copyConfig['show_amount_by_bob'] ?? BranchSetting::defaultShowAmountByBob(false),
                ),
            ];
        }

        if ($copies !== []) {
            return $copies;
        }

        foreach (self::COPY_KEYS as $key) {
            $default = BranchSetting::defaultBiltyPrintConfig()['copies'][$key];

            $copies[] = [
                'key' => $key,
                'label' => self::COPY_LABELS[$key],
                'show_amount' => self::shouldShowAmount(
                    $bilty,
                    $default['show_amount_by_bob'] ?? BranchSetting::defaultShowAmountByBob(false),
                ),
            ];
        }

        return $copies;
    }
}

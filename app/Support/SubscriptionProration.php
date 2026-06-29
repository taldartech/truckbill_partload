<?php

namespace App\Support;

use Carbon\Carbon;

class SubscriptionProration
{
    public const ANNUAL_DAYS = 365;

    public static function remainingDaysInclusive(Carbon|string $endsOn, ?Carbon $asOf = null): int
    {
        $today = ($asOf ?? now())->copy()->startOfDay();
        $end = Carbon::parse($endsOn)->startOfDay();

        if ($end->lt($today)) {
            return 0;
        }

        return $today->diffInDays($end) + 1;
    }

    public static function prorateAnnualAmount(float $annualAmount, int $remainingDays): float
    {
        if ($annualAmount <= 0 || $remainingDays <= 0) {
            return 0.0;
        }

        $prorated = Money::round(($annualAmount / self::ANNUAL_DAYS) * $remainingDays);

        return min($annualAmount, $prorated);
    }
}

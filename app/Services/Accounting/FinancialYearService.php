<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialYearService
{
    /**
     * Ensure the Indian FY covering $around exists, is marked current, and
     * past years are closed by calendar date (ends_on before today).
     */
    public function ensureCurrent(Company|int $company, ?Carbon $around = null): FinancialYear
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $around ??= now();

        $existing = FinancialYear::query()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $around->toDateString())
            ->whereDate('ends_on', '>=', $around->toDateString())
            ->first();

        if (! $existing) {
            $existing = $this->createIndianFinancialYear($companyId, $around);
        }

        $this->syncStatusByDate($companyId, $existing);

        return $existing->fresh();
    }

    /**
     * Resolve (or auto-create) the FY for a voucher date.
     */
    public function forDate(Company|int $company, Carbon|string $date): FinancialYear
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $carbon = Carbon::parse($date);

        $fy = FinancialYear::query()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $carbon->toDateString())
            ->whereDate('ends_on', '>=', $carbon->toDateString())
            ->first();

        if (! $fy) {
            $fy = $this->createIndianFinancialYear($companyId, $carbon);
        }

        $current = null;
        if ($fy->containsDate(now())) {
            $current = $fy;
        }

        $this->syncStatusByDate($companyId, $current);

        return $fy->fresh();
    }

    public function createIndianFinancialYear(int $companyId, Carbon $around): FinancialYear
    {
        [$startsOn, $endsOn, $name] = $this->indianYearBounds($around);

        return FinancialYear::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
            ],
            [
                'name' => $name,
                'is_current' => false,
                'is_closed' => $endsOn->toDateString() < now()->toDateString(),
            ],
        );
    }

    public function assertOpen(FinancialYear $financialYear): void
    {
        if ($this->isClosedByDate($financialYear) || $financialYear->is_closed) {
            throw new InvalidArgumentException('Financial year is closed.');
        }
    }

    public function isClosedByDate(FinancialYear $financialYear): bool
    {
        return $financialYear->ends_on->toDateString() < now()->toDateString();
    }

    /**
     * Mark past years closed and the active year (covering today) open + current.
     */
    public function syncStatusByDate(int $companyId, ?FinancialYear $preferredCurrent = null): void
    {
        $today = now()->toDateString();

        DB::transaction(function () use ($companyId, $preferredCurrent, $today) {
            $years = FinancialYear::query()
                ->where('company_id', $companyId)
                ->orderBy('starts_on')
                ->get();

            $current = $preferredCurrent;
            if (! $current || ! $current->containsDate(now())) {
                $current = $years->first(
                    fn (FinancialYear $fy) => $fy->starts_on->toDateString() <= $today
                        && $fy->ends_on->toDateString() >= $today,
                );
            }

            foreach ($years as $fy) {
                $shouldClose = $fy->ends_on->toDateString() < $today;
                $shouldBeCurrent = $current && $fy->is($current);

                $updates = [];
                if ((bool) $fy->is_closed !== $shouldClose) {
                    $updates['is_closed'] = $shouldClose;
                }
                if ((bool) $fy->is_current !== $shouldBeCurrent) {
                    $updates['is_current'] = $shouldBeCurrent;
                }

                if ($updates !== []) {
                    $fy->update($updates);
                }
            }
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function indianYearBounds(Carbon $around): array
    {
        $year = $around->month >= 4 ? $around->year : $around->year - 1;
        $startsOn = Carbon::create($year, 4, 1)->startOfDay();
        $endsOn = Carbon::create($year + 1, 3, 31)->startOfDay();
        $name = sprintf('FY %d-%02d', $year, ($year + 1) % 100);

        return [$startsOn, $endsOn, $name];
    }
}

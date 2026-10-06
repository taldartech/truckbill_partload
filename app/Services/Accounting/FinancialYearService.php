<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialYearService
{
    /**
     * Ensure the Indian FY covering $around exists and is marked current.
     * Closing a year is a manual action; dates never close a year on their own.
     */
    public function ensureCurrent(Company|int $company, ?Carbon $around = null): FinancialYear
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $around ??= now();

        $existing = FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $around->toDateString())
            ->whereDate('ends_on', '>=', $around->toDateString())
            ->first();

        if (! $existing) {
            $existing = $this->createIndianFinancialYear($companyId, $around);
        }

        $this->syncCurrentFlag($companyId, $existing);

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
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $carbon->toDateString())
            ->whereDate('ends_on', '>=', $carbon->toDateString())
            ->first();

        if (! $fy) {
            $fy = $this->createIndianFinancialYear($companyId, $carbon);
            $this->syncCurrentFlag($companyId);
        }

        return $fy->fresh();
    }

    public function createIndianFinancialYear(int $companyId, Carbon $around): FinancialYear
    {
        [$startsOn, $endsOn, $name] = $this->indianYearBounds($around);

        return FinancialYear::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $companyId,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
            ],
            [
                'name' => $name,
                'is_current' => false,
                'is_closed' => false,
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    public function assertOpen(FinancialYear $financialYear): void
    {
        if ($financialYear->is_closed) {
            throw ValidationException::withMessages([
                'financial_year_id' => "Financial year {$financialYear->name} is closed. Reopen it before posting or changing entries.",
            ]);
        }
    }

    public function close(FinancialYear $financialYear, ?int $userId = null): FinancialYear
    {
        $financialYear->update([
            'is_closed' => true,
            'closed_by' => $userId ?? Auth::id(),
            'closed_at' => now(),
        ]);

        return $financialYear->fresh();
    }

    public function reopen(FinancialYear $financialYear): FinancialYear
    {
        $financialYear->update([
            'is_closed' => false,
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return $financialYear->fresh();
    }

    /**
     * Mark the year covering today (or $preferredCurrent) as current; never touches is_closed.
     */
    public function syncCurrentFlag(int $companyId, ?FinancialYear $preferredCurrent = null): void
    {
        $today = now()->toDateString();

        DB::transaction(function () use ($companyId, $preferredCurrent, $today) {
            $years = FinancialYear::query()
                ->withoutGlobalScopes()
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
                $shouldBeCurrent = $current && $fy->is($current);

                if ((bool) $fy->is_current !== $shouldBeCurrent) {
                    $fy->update(['is_current' => $shouldBeCurrent]);
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

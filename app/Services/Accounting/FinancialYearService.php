<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialYearService
{
    public function ensureCurrent(Company|int $company, ?Carbon $around = null): FinancialYear
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $around ??= now();

        $existing = FinancialYear::query()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $around->toDateString())
            ->whereDate('ends_on', '>=', $around->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->createIndianFinancialYear($companyId, $around);
    }

    public function forDate(Company|int $company, Carbon|string $date): FinancialYear
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $carbon = Carbon::parse($date);

        $fy = FinancialYear::query()
            ->where('company_id', $companyId)
            ->whereDate('starts_on', '<=', $carbon->toDateString())
            ->whereDate('ends_on', '>=', $carbon->toDateString())
            ->first();

        if ($fy) {
            return $fy;
        }

        return $this->createIndianFinancialYear($companyId, $carbon);
    }

    public function createIndianFinancialYear(int $companyId, Carbon $around): FinancialYear
    {
        $year = $around->month >= 4 ? $around->year : $around->year - 1;
        $startsOn = Carbon::create($year, 4, 1)->startOfDay();
        $endsOn = Carbon::create($year + 1, 3, 31)->startOfDay();
        $name = sprintf('FY %d-%02d', $year, ($year + 1) % 100);

        return DB::transaction(function () use ($companyId, $startsOn, $endsOn, $name) {
            FinancialYear::query()
                ->where('company_id', $companyId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return FinancialYear::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'starts_on' => $startsOn->toDateString(),
                    'ends_on' => $endsOn->toDateString(),
                ],
                [
                    'name' => $name,
                    'is_current' => true,
                    'is_closed' => false,
                ],
            );
        });
    }

    public function assertOpen(FinancialYear $financialYear): void
    {
        if ($financialYear->is_closed) {
            throw new InvalidArgumentException('Financial year is closed.');
        }
    }
}

<?php

namespace App\Services\Accounting;

use App\Models\Company;

class EnsureCompanyAccounting
{
    public function __construct(
        private ChartOfAccountsService $chartOfAccounts,
        private FinancialYearService $financialYears,
    ) {}

    public function handle(Company|int $company): void
    {
        $this->chartOfAccounts->ensureDefaults($company);
        $this->financialYears->ensureCurrent($company);
    }
}

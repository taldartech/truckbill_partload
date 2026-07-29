<?php

namespace App\Services\Accounting;

use App\Models\Company;

class EnsureCompanyLedgerAccounts
{
    public function __construct(private LedgerAccountResolver $resolver) {}

    public function handle(Company|int $company): void
    {
        $this->resolver->ensureSystemAccounts($company);
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Services\TenantContext;

trait ResolvesTenant
{
    protected function tenant(): TenantContext
    {
        return app(TenantContext::class);
    }

    protected function companyId(): int
    {
        return $this->tenant()->requireCompanyId();
    }

    protected function branchId(): int
    {
        return $this->tenant()->requireBranchId();
    }

    protected function stripTenantFields(array $validated): array
    {
        unset($validated['company_id'], $validated['branch_id'], $validated['owner_id']);

        return $validated;
    }

    protected function activeBranch(): ?Branch
    {
        return $this->tenant()->activeBranch();
    }
}

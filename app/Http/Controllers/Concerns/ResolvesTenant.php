<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Services\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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

    /**
     * An `exists` rule limited to rows of the active company.
     */
    protected function existsInCompany(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('company_id', (int) ($this->tenant()->activeCompanyId() ?? 0));
    }
}

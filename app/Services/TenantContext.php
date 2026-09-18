<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenantContext
{
    public function user(): ?User
    {
        return Auth::user();
    }

    public function isMasterAdmin(): bool
    {
        return (bool) $this->user()?->hasRole(UserRole::MasterAdmin->value);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->user()?->hasRole(UserRole::SuperAdmin->value);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isMasterAdmin() || $this->isSuperAdmin();
    }

    public function sessionBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId ? (int) $branchId : null;
    }

    public function sessionCompanyId(): ?int
    {
        $companyId = session('active_company_id');

        return $companyId ? (int) $companyId : null;
    }

    public function isActingAsAdmin(): bool
    {
        return $this->isSuperAdmin() && $this->sessionBranchId() !== null;
    }

    public function activeBranchId(): ?int
    {
        if ($this->isMasterAdmin()) {
            return $this->sessionBranchId();
        }

        if ($this->isSuperAdmin()) {
            return $this->sessionBranchId();
        }

        return $this->user()?->branch_id ? (int) $this->user()->branch_id : null;
    }

    public function activeCompanyId(): ?int
    {
        if ($this->isMasterAdmin()) {
            return $this->sessionCompanyId() ?? $this->user()?->company_id;
        }

        if ($this->isSuperAdmin()) {
            if ($this->sessionCompanyId()) {
                return $this->sessionCompanyId();
            }

            return null;
        }

        return $this->user()?->company_id ? (int) $this->user()->company_id : null;
    }

    public function ownedCompanyIds(): array
    {
        if (! $this->isSuperAdmin() || ! $this->user()) {
            return [];
        }

        return Company::query()
            ->where('user_id', $this->user()->id)
            ->pluck('id')
            ->all();
    }

    public function activeOwnerId(): ?int
    {
        if ($this->isMasterAdmin()) {
            return null;
        }

        if ($this->isSuperAdmin()) {
            return $this->user()?->id ? (int) $this->user()->id : null;
        }

        $companyId = $this->user()?->company_id;

        if (! $companyId) {
            return null;
        }

        $ownerId = Company::query()
            ->whereKey($companyId)
            ->value('user_id');

        return $ownerId ? (int) $ownerId : null;
    }

    public function shouldApplyOwnerScope(): bool
    {
        if (! $this->user()) {
            return false;
        }

        if ($this->isMasterAdmin()) {
            return false;
        }

        // Always isolate shared master data by owner, even with no companies yet.
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->user()->company_id !== null;
    }

    public function shouldApplyCompanyScope(): bool
    {
        if (! $this->user()) {
            return false;
        }

        if ($this->isMasterAdmin()) {
            return false;
        }

        // Always isolate company data, even with no companies yet.
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->user()->company_id !== null;
    }

    public function shouldApplyBranchScope(): bool
    {
        if (! $this->user()) {
            return false;
        }

        if ($this->isMasterAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return $this->isActingAsAdmin();
        }

        return $this->user()->branch_id !== null;
    }

    public function effectivePermissions(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        if ($this->isActingAsAdmin()) {
            return collect(RolePermissions::adminPermissions());
        }

        return $user->getAllPermissions()->pluck('name');
    }

    public function can(string $permission): bool
    {
        return $this->effectivePermissions()->contains($permission);
    }

    public function requireCompanyId(): int
    {
        $companyId = $this->activeCompanyId();

        if (! $companyId) {
            abort(422, 'Company context is required.');
        }

        return $companyId;
    }

    public function requireBranchId(): int
    {
        $branchId = $this->activeBranchId();

        if (! $branchId) {
            abort(422, 'Branch context is required.');
        }

        return $branchId;
    }

    public function mergeTenantIds(array $data, bool $withBranch = true): array
    {
        $data['company_id'] = $this->requireCompanyId();

        if ($withBranch) {
            $data['branch_id'] = $this->requireBranchId();
        } else {
            unset($data['branch_id']);
        }

        return $data;
    }

    public function activeBranch(): ?Branch
    {
        $branchId = $this->activeBranchId();

        return $branchId ? Branch::find($branchId) : null;
    }

    public function activeCompany(): ?Company
    {
        $companyId = $this->activeCompanyId();

        return $companyId ? Company::find($companyId) : null;
    }

    public function assignableRolesFor(User $creator): array
    {
        if ($creator->hasRole(UserRole::MasterAdmin->value)) {
            return UserRole::assignableByMasterAdmin();
        }

        if ($creator->hasRole(UserRole::SuperAdmin->value)) {
            return UserRole::assignableBySuperAdmin();
        }

        return [];
    }

    public function isSetupRoute(string $routeName): bool
    {
        $setupPrefixes = [
            'companies.',
            'branches.',
            'users.',
            'subscription.',
            'subscriptions.',
            'branch-context.',
            // Geo helpers used while creating companies/branches (before a branch is selected).
            'cities.',
            'countries.',
            'states.',
        ];

        foreach ($setupPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        foreach (RolePermissions::sharedMasterDataModules() as $module) {
            if (str_starts_with($routeName, Str::slug($module, '-').'.')) {
                return true;
            }
        }

        return in_array($routeName, [
            'logout',
            'dashboard',
            'password.edit',
            'password.update',
        ], true);
    }

    public function isPortalRoute(string $routeName): bool
    {
        $portalPrefixes = [
            'users.',
            'roles.',
            'subscriptions.',
        ];

        foreach ($portalPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return in_array($routeName, [
            'logout',
            'dashboard',
            'password.edit',
            'password.update',
        ], true);
    }

    public function requiresBranchSelection(string $routeName): bool
    {
        if ($this->isMasterAdmin()) {
            return false;
        }

        if (! $this->isSuperAdmin() || $this->isActingAsAdmin()) {
            return false;
        }

        return ! $this->isSetupRoute($routeName);
    }

    public function isPortalUser(): bool
    {
        return $this->isMasterAdmin()
            || ($this->isSuperAdmin() && ! $this->isActingAsAdmin());
    }

    public function blocksOperationalRoute(string $routeName): bool
    {
        if (! $this->isMasterAdmin()) {
            return false;
        }

        return ! $this->isPortalRoute($routeName);
    }
}

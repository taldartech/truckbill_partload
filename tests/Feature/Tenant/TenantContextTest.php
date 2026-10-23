<?php

namespace Tests\Feature\Tenant;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    private function ctx(): TenantContext
    {
        return app(TenantContext::class);
    }

    private function userWithRole(UserRole $role, array $attributes = []): User
    {
        Role::findOrCreate($role->value, 'web');
        $user = User::factory()->create($attributes);
        $user->assignRole($role->value);

        return $user;
    }

    public function test_guest_has_no_context(): void
    {
        $ctx = $this->ctx();

        $this->assertNull($ctx->user());
        $this->assertFalse($ctx->isMasterAdmin());
        $this->assertFalse($ctx->shouldApplyOwnerScope());
        $this->assertFalse($ctx->shouldApplyCompanyScope());
        $this->assertFalse($ctx->shouldApplyBranchScope());
        $this->assertSame([], $ctx->ownedCompanyIds());
        $this->assertTrue($ctx->effectivePermissions()->isEmpty());
        $this->assertFalse($ctx->can('view bilties'));
        $this->assertNull($ctx->activeBranch());
        $this->assertNull($ctx->activeCompany());
    }

    public function test_master_admin_context(): void
    {
        $tenant = $this->createTenantContext();
        $master = $this->userWithRole(UserRole::MasterAdmin, ['company_id' => $tenant['company']->id]);
        Auth::login($master);

        $ctx = $this->ctx();

        $this->assertTrue($ctx->isMasterAdmin());
        $this->assertTrue($ctx->isPlatformAdmin());
        $this->assertNull($ctx->activeBranchId());
        $this->assertSame($tenant['company']->id, $ctx->activeCompanyId());
        $this->assertNull($ctx->activeOwnerId());
        $this->assertFalse($ctx->shouldApplyOwnerScope());
        $this->assertFalse($ctx->shouldApplyCompanyScope());
        $this->assertFalse($ctx->shouldApplyBranchScope());
        $this->assertFalse($ctx->requiresBranchSelection('bilties.index'));
        $this->assertTrue($ctx->isPortalUser());
        $this->assertTrue($ctx->blocksOperationalRoute('bilties.index'));
        $this->assertFalse($ctx->blocksOperationalRoute('roles.index'));
        $this->assertSame([], $ctx->ownedCompanyIds());
        $this->assertSame([], $ctx->assignableRolesFor($master));

        session(['active_company_id' => 99, 'active_branch_id' => 42]);
        $this->assertSame(99, $ctx->activeCompanyId());
        $this->assertSame(42, $ctx->activeBranchId());
    }

    public function test_super_admin_context_without_and_with_active_branch(): void
    {
        $tenant = $this->createTenantContext();
        $super = $this->userWithRole(UserRole::SuperAdmin);
        Auth::login($super);

        $ctx = $this->ctx();

        $this->assertTrue($ctx->isSuperAdmin());
        $this->assertTrue($ctx->canManagePartyCharges());
        $this->assertNull($ctx->activeCompanyId());
        $this->assertNull($ctx->activeBranchId());
        $this->assertSame($super->id, $ctx->activeOwnerId());
        $this->assertTrue($ctx->shouldApplyOwnerScope());
        $this->assertTrue($ctx->shouldApplyCompanyScope());
        $this->assertFalse($ctx->shouldApplyBranchScope());
        $this->assertFalse($ctx->isActingAsAdmin());
        $this->assertTrue($ctx->isPortalUser());
        $this->assertTrue($ctx->requiresBranchSelection('bilties.index'));
        $this->assertFalse($ctx->requiresBranchSelection('companies.index'));
        $this->assertFalse($ctx->requiresBranchSelection('stations.index'));
        $this->assertFalse($ctx->requiresBranchSelection('dashboard'));
        $this->assertFalse($ctx->blocksOperationalRoute('bilties.index'));
        $this->assertSame(UserRole::assignableBySuperAdmin(), $ctx->assignableRolesFor($super));

        $tenant['company']->update(['user_id' => $super->id]);
        $this->assertSame([$tenant['company']->id], $ctx->ownedCompanyIds());

        session([
            'active_company_id' => $tenant['company']->id,
            'active_branch_id' => $tenant['branch']->id,
        ]);

        $this->assertTrue($ctx->isActingAsAdmin());
        $this->assertTrue($ctx->shouldApplyBranchScope());
        $this->assertFalse($ctx->isPortalUser());
        $this->assertFalse($ctx->requiresBranchSelection('bilties.index'));
        $this->assertSame($tenant['company']->id, $ctx->activeCompanyId());
        $this->assertSame($tenant['branch']->id, $ctx->activeBranch()?->id);
        $this->assertSame($tenant['company']->id, $ctx->activeCompany()?->id);
        $this->assertTrue($ctx->can('delete bilties'));
        $this->assertSame(
            ['name' => 'x', 'company_id' => $tenant['company']->id, 'branch_id' => $tenant['branch']->id],
            $ctx->mergeTenantIds(['name' => 'x'])
        );
        $this->assertSame(
            ['name' => 'x', 'company_id' => $tenant['company']->id],
            $ctx->mergeTenantIds(['name' => 'x', 'branch_id' => 5], false)
        );
    }

    public function test_company_user_context_and_permissions(): void
    {
        $tenant = $this->createTenantContext(['view bilties']);
        Permission::findOrCreate('view bilties');
        Auth::login($tenant['user']);

        $ctx = $this->ctx();

        $this->assertSame($tenant['company']->id, $ctx->activeCompanyId());
        $this->assertSame($tenant['branch']->id, $ctx->activeBranchId());
        $this->assertSame($tenant['owner']->id, $ctx->activeOwnerId());
        $this->assertTrue($ctx->shouldApplyOwnerScope());
        $this->assertTrue($ctx->shouldApplyCompanyScope());
        $this->assertTrue($ctx->shouldApplyBranchScope());
        $this->assertFalse($ctx->requiresBranchSelection('bilties.index'));
        $this->assertFalse($ctx->isPortalUser());
        $this->assertSame([], $ctx->assignableRolesFor($tenant['user']));
        $this->assertTrue($ctx->can('view bilties'));
        $this->assertFalse($ctx->can('delete bilties'));
        $this->assertFalse($ctx->canManagePartyCharges());
    }

    public function test_user_without_company_or_branch_has_no_scopes(): void
    {
        $orphan = $this->userWithRole(UserRole::Admin, ['company_id' => null, 'branch_id' => null]);
        Auth::login($orphan);

        $ctx = $this->ctx();

        $this->assertNull($ctx->activeBranchId());
        $this->assertNull($ctx->activeCompanyId());
        $this->assertNull($ctx->activeOwnerId());
        $this->assertFalse($ctx->shouldApplyOwnerScope());
        $this->assertFalse($ctx->shouldApplyCompanyScope());
        $this->assertFalse($ctx->shouldApplyBranchScope());
        $this->assertTrue($ctx->canManagePartyCharges());
    }

    public function test_required_context_aborts_with_422_when_missing(): void
    {
        $orphan = $this->userWithRole(UserRole::Admin, ['company_id' => null, 'branch_id' => null]);
        Auth::login($orphan);

        try {
            $this->ctx()->requireCompanyId();
            $this->fail('Expected company context abort.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('Company context is required.', $e->getMessage());
        }

        try {
            $this->ctx()->requireBranchId();
            $this->fail('Expected branch context abort.');
        } catch (HttpException|HttpResponseException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('Branch context is required.', $e->getMessage());
        }
    }

    public function test_route_classification_helpers(): void
    {
        $ctx = $this->ctx();

        foreach (['companies.index', 'cities.by-state', 'states.index', 'goods.index', 'logout', 'password.update'] as $route) {
            $this->assertTrue($ctx->isSetupRoute($route), $route);
        }
        $this->assertFalse($ctx->isSetupRoute('bilties.index'));

        foreach (['users.index', 'roles.create', 'subscriptions.index', 'dashboard', 'password.edit'] as $route) {
            $this->assertTrue($ctx->isPortalRoute($route), $route);
        }
        $this->assertFalse($ctx->isPortalRoute('bilties.index'));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Station;
use App\Models\TruckType;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminOwnerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_super_admin_without_companies_cannot_see_other_owners_shared_master_data(): void
    {
        $otherOwner = $this->createSuperAdmin();
        TruckType::create(['owner_id' => $otherOwner->id, 'name' => 'Other Truck Type']);
        Good::create(['owner_id' => $otherOwner->id, 'name' => 'Other Good']);
        Station::create(['owner_id' => $otherOwner->id, 'name' => 'Other Station']);
        PackageType::create(['owner_id' => $otherOwner->id, 'name' => 'Other Package']);

        $newSuperAdmin = $this->createSuperAdmin();
        TruckType::create(['owner_id' => $newSuperAdmin->id, 'name' => 'My Truck Type']);
        Good::create(['owner_id' => $newSuperAdmin->id, 'name' => 'My Good']);
        Station::create(['owner_id' => $newSuperAdmin->id, 'name' => 'My Station']);
        PackageType::create(['owner_id' => $newSuperAdmin->id, 'name' => 'My Package']);

        $this->actingAs($newSuperAdmin)
            ->get(route('truck-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('TruckTypes/Index')
                ->has('truckTypes.data', 1)
                ->where('truckTypes.data.0.name', 'My Truck Type')
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('goods.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Goods/Index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'My Good')
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Stations/Index')
                ->has('stations.data', 1)
                ->where('stations.data.0.name', 'My Station')
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('package-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PackageTypes/Index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'My Package')
                ->etc()
            );
    }

    public function test_new_super_admin_without_companies_sees_empty_shared_master_data_when_none_owned(): void
    {
        $otherOwner = $this->createSuperAdmin();
        TruckType::create(['owner_id' => $otherOwner->id, 'name' => 'Other Truck Type']);
        Good::create(['owner_id' => $otherOwner->id, 'name' => 'Other Good']);
        Station::create(['owner_id' => $otherOwner->id, 'name' => 'Other Station']);
        PackageType::create(['owner_id' => $otherOwner->id, 'name' => 'Other Package']);

        $newSuperAdmin = $this->createSuperAdmin();

        $this->actingAs($newSuperAdmin)
            ->get(route('truck-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('TruckTypes/Index')
                ->has('truckTypes.data', 0)
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('goods.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Goods/Index')
                ->has('items.data', 0)
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Stations/Index')
                ->has('stations.data', 0)
                ->etc()
            );

        $this->actingAs($newSuperAdmin)
            ->get(route('package-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PackageTypes/Index')
                ->has('items.data', 0)
                ->etc()
            );
    }

    private function createSuperAdmin(): User
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');

        foreach (RolePermissions::superAdminPermissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
        ]);
        $user->assignRole(UserRole::SuperAdmin->value);
        $user->givePermissionTo(RolePermissions::superAdminPermissions());

        return $user;
    }
}

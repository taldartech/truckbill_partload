<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_admin_can_open_role_show(): void
    {
        Permission::findOrCreate('view roles');
        Permission::findOrCreate('manage reports', 'web');

        $masterAdmin = User::factory()->create();
        $masterAdmin->assignRole(Role::findOrCreate(UserRole::MasterAdmin->value, 'web'));
        $masterAdmin->givePermissionTo('view roles');

        $role = Role::findOrCreate('custom_role', 'web');
        $role->syncPermissions(['manage reports']);

        $response = $this->actingAs($masterAdmin)->get(route('roles.show', $role));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Roles/Show')
            ->where('role.id', $role->id)
            ->where('role.name', 'custom_role')
            ->where('role.is_system_role', false)
            ->has('role.permissions', 1)
            ->where('role.permissions.0.name', 'manage reports')
            ->etc()
        );
    }

    public function test_master_admin_show_flags_system_roles(): void
    {
        Permission::findOrCreate('view roles');

        $masterAdmin = User::factory()->create();
        $masterAdmin->assignRole(Role::findOrCreate(UserRole::MasterAdmin->value, 'web'));
        $masterAdmin->givePermissionTo('view roles');

        $adminRole = Role::findOrCreate(UserRole::Admin->value, 'web');

        $response = $this->actingAs($masterAdmin)->get(route('roles.show', $adminRole));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Roles/Show')
            ->where('role.is_system_role', true)
            ->etc()
        );
    }

    public function test_non_master_admin_cannot_open_role_show(): void
    {
        Permission::findOrCreate('view roles');

        $user = User::factory()->create();
        $user->givePermissionTo('view roles');

        $role = Role::findOrCreate('custom_role', 'web');

        $response = $this->actingAs($user)->get(route('roles.show', $role));

        $response->assertForbidden();
    }
}

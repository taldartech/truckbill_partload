<?php

namespace Tests\Feature\Ops;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    private const ALL = ['view roles', 'create roles', 'edit roles', 'delete roles'];

    public function test_master_admin_index_lists_roles_and_permissions(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Role::findOrCreate('custom_role');

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Roles/Index')
                ->has('roles', Role::count())
                ->where('roles', fn ($roles) => collect($roles)->pluck('name')->contains('custom_role'))
                ->has('permissions', Permission::count()));
    }

    public function test_index_is_forbidden_for_non_master_admin_even_with_permission(): void
    {
        $context = $this->createTenantContext(['view roles']);

        $this->actingAs($context['user'])
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_index_requires_view_permission_for_master_admin(): void
    {
        $admin = $this->masterAdmin(['create roles']);

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_store_creates_role_with_permissions(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Permission::findOrCreate('view reports');

        $this->actingAs($admin)
            ->post(route('roles.store'), ['name' => 'auditor', 'permissions' => ['view reports']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role created successfully.');

        $role = Role::findByName('auditor');
        $this->assertTrue($role->hasPermissionTo('view reports'));
    }

    public function test_store_creates_role_without_permissions(): void
    {
        $admin = $this->masterAdmin(self::ALL);

        $this->actingAs($admin)
            ->post(route('roles.store'), ['name' => 'empty_role'])
            ->assertSessionHasNoErrors();

        $this->assertCount(0, Role::findByName('empty_role')->permissions);
    }

    public function test_store_validates_name(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Role::findOrCreate('taken');

        $this->actingAs($admin)
            ->post(route('roles.store'), [])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('roles.store'), ['name' => 'taken'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('roles.store'), ['name' => 'fine', 'permissions' => 'not-an-array'])
            ->assertSessionHasErrors('permissions');
    }

    public function test_store_is_restricted_to_master_admin_with_permission(): void
    {
        $context = $this->createTenantContext(['create roles']);

        $this->actingAs($context['user'])
            ->post(route('roles.store'), ['name' => 'sneaky'])
            ->assertForbidden();

        $noPermission = $this->masterAdmin(['view roles'], 'B');
        $this->actingAs($noPermission)
            ->post(route('roles.store'), ['name' => 'sneaky'])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'sneaky']);
    }

    public function test_update_renames_custom_role_and_syncs_permissions(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Permission::findOrCreate('view reports');
        Permission::findOrCreate('view stations');
        $role = Role::findOrCreate('custom_role');
        $role->givePermissionTo('view reports');

        $this->actingAs($admin)
            ->put(route('roles.update', $role), ['name' => 'renamed_role', 'permissions' => ['view stations']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role updated successfully.');

        $role->refresh();
        $this->assertSame('renamed_role', $role->name);
        $this->assertSame(['view stations'], $role->permissions->pluck('name')->all());
    }

    public function test_update_without_permissions_clears_them(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Permission::findOrCreate('view reports');
        $role = Role::findOrCreate('custom_role');
        $role->givePermissionTo('view reports');

        $this->actingAs($admin)
            ->put(route('roles.update', $role), ['name' => 'custom_role'])
            ->assertSessionHasNoErrors();

        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_update_cannot_rename_system_role(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        $role = Role::findOrCreate('moderator');

        $this->actingAs($admin)
            ->from(route('roles.index'))
            ->put(route('roles.update', $role), ['name' => 'not_moderator'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'System role names cannot be changed.');

        $this->assertSame('moderator', $role->fresh()->name);
    }

    public function test_update_allows_permission_changes_on_system_role_with_same_name(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Permission::findOrCreate('view reports');
        $role = Role::findOrCreate('moderator');

        $this->actingAs($admin)
            ->put(route('roles.update', $role), ['name' => 'moderator', 'permissions' => ['view reports']])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Role updated successfully.');

        $this->assertTrue($role->fresh()->hasPermissionTo('view reports'));
    }

    public function test_update_validates_name_uniqueness(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        Role::findOrCreate('first_role');
        $second = Role::findOrCreate('second_role');

        $this->actingAs($admin)
            ->put(route('roles.update', $second), ['name' => 'first_role'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->put(route('roles.update', $second), [])
            ->assertSessionHasErrors('name');
    }

    public function test_update_is_restricted_to_master_admin_with_permission(): void
    {
        $role = Role::findOrCreate('custom_role');
        $context = $this->createTenantContext(['edit roles']);

        $this->actingAs($context['user'])
            ->put(route('roles.update', $role), ['name' => 'hijacked'])
            ->assertForbidden();

        $noPermission = $this->masterAdmin(['view roles'], 'B');
        $this->actingAs($noPermission)
            ->put(route('roles.update', $role), ['name' => 'hijacked'])
            ->assertForbidden();

        $this->assertSame('custom_role', $role->fresh()->name);
    }

    public function test_destroy_deletes_custom_role(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        $role = Role::findOrCreate('custom_role');

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role deleted successfully.');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_destroy_blocks_system_roles(): void
    {
        $admin = $this->masterAdmin(self::ALL);
        $role = Role::findOrCreate('accountant');

        $this->actingAs($admin)
            ->from(route('roles.index'))
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'System roles cannot be deleted.');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_destroy_is_restricted_to_master_admin_with_permission(): void
    {
        $role = Role::findOrCreate('custom_role');
        $context = $this->createTenantContext(['delete roles']);

        $this->actingAs($context['user'])
            ->delete(route('roles.destroy', $role))
            ->assertForbidden();

        $noPermission = $this->masterAdmin(['view roles'], 'B');
        $this->actingAs($noPermission)
            ->delete(route('roles.destroy', $role))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function masterAdmin(array $permissions, string $suffix = 'A'): User
    {
        $context = $this->createTenantContext($permissions, $suffix);
        $this->giveRole($context['user'], 'master_admin');

        return $context['user'];
    }
}

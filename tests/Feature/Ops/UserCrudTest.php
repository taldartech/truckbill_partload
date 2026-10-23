<?php

namespace Tests\Feature\Ops;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_company_users_for_regular_user(): void
    {
        $context = $this->createTenantContext(['view users']);
        $colleague = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Users/Index')
                ->has('users.data', 2)
                ->where('assignable_roles', [])
                ->has('companies', 1)
                ->where('companies.0.id', $context['company']->id)
            );

        $this->assertNotSame($other['user']->company_id, $colleague->company_id);
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext([]);

        $this->actingAs($context['user'])
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_index_for_super_admin_lists_owned_company_users_without_super_admins(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);
        $foreign = $this->createTenantContext([], 'Foreign');

        $response = $this->actingAs($context['admin'])
            ->get(route('users.index'));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 2)
                ->where('users.data.0.can_manage', true)
                ->where('users.data.1.can_manage', true)
                ->where('assignable_roles', UserRole::assignableBySuperAdmin())
                ->has('branches', 1)
            );

        $this->assertNotSame($foreign['company']->id, $context['company']->id);
        $this->assertContains($staff->id, collect($response->viewData('page')['props']['users']['data'])->pluck('id')->all());
    }

    public function test_index_for_master_admin_lists_super_admins_only(): void
    {
        $context = $this->createSuperAdminContext();
        $this->makeStaff($context, UserRole::Admin);
        Role::findOrCreate(UserRole::MasterAdmin->value, 'web');
        Permission::findOrCreate('view users');
        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);
        $master->givePermissionTo('view users');

        $this->actingAs($master)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $context['admin']->id)
            );
    }

    public function test_show_renders_user_in_scope(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Accountant);

        $this->actingAs($context['admin'])
            ->get(route('users.show', $staff))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Users/Show')
                ->where('user.id', $staff->id)
                ->where('user.can_manage', true)
            );
    }

    public function test_show_forbids_user_outside_scope(): void
    {
        $context = $this->createTenantContext(['view users']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->get(route('users.show', $other['user']))
            ->assertForbidden();
    }

    public function test_store_creates_user_with_role_and_hashed_password(): void
    {
        $context = $this->createSuperAdminContext();

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User created successfully.');

        $user = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame($context['company']->id, $user->company_id);
        $this->assertSame($context['branch']->id, $user->branch_id);
        $this->assertTrue($user->hasRole(UserRole::Moderator->value));
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view users']);

        $this->actingAs($context['user'])
            ->post(route('users.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_store_rejects_regular_user_because_no_roles_are_assignable(): void
    {
        Role::findOrCreate(UserRole::Moderator->value, 'web');
        $context = $this->createTenantContext(['create users']);

        $this->actingAs($context['user'])
            ->post(route('users.store'), $this->validPayload($context))
            ->assertSessionHasErrors('role');
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->createSuperAdminContext();

        $this->actingAs($context['admin'])
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_store_validates_email_password_and_role(): void
    {
        $context = $this->createSuperAdminContext();
        $existing = $this->makeStaff($context, UserRole::Admin);

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context, [
                'email' => $existing->email,
                'password' => 'short',
                'role' => 'nonexistent',
            ]))
            ->assertSessionHasErrors(['email', 'password', 'role']);
    }

    public function test_store_rejects_super_admin_role(): void
    {
        $context = $this->createSuperAdminContext();

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context, ['role' => UserRole::SuperAdmin->value]))
            ->assertSessionHasErrors('role');
    }

    public function test_store_requires_company_and_branch_for_branch_roles(): void
    {
        $context = $this->createSuperAdminContext();

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context, [
                'company_id' => null,
                'branch_id' => null,
            ]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_store_rejects_branch_from_another_company(): void
    {
        $context = $this->createSuperAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context, [
                'branch_id' => $foreign['branch']->id,
            ]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_store_rejects_company_and_branch_not_owned_by_super_admin(): void
    {
        $context = $this->createSuperAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context, [
                'company_id' => $foreign['company']->id,
                'branch_id' => $foreign['branch']->id,
            ]))
            ->assertSessionHasErrors('company_id');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_store_enforces_subscription_user_limit(): void
    {
        $context = $this->createSuperAdminContext();
        $context['subscription']->update(['users_per_branch' => 1]);
        $this->makeStaff($context, UserRole::Admin);

        $this->actingAs($context['admin'])
            ->post(route('users.store'), $this->validPayload($context))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_update_validates_required_fields(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Admin);

        $this->actingAs($context['admin'])
            ->put(route('users.update', $staff), ['name' => ''])
            ->assertSessionHasErrors(['name', 'email', 'role']);
    }

    public function test_update_changes_user_details_and_role(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);
        $oldPasswordHash = $staff->password;

        $this->actingAs($context['admin'])
            ->put(route('users.update', $staff), [
                'name' => 'Renamed Staff',
                'email' => 'renamed@example.com',
                'role' => UserRole::Accountant->value,
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User updated successfully.');

        $staff->refresh();
        $this->assertSame('Renamed Staff', $staff->name);
        $this->assertSame('renamed@example.com', $staff->email);
        $this->assertTrue($staff->hasRole(UserRole::Accountant->value));
        $this->assertFalse($staff->hasRole(UserRole::Moderator->value));
        $this->assertSame($oldPasswordHash, $staff->password);
    }

    public function test_update_with_password_rehashes_it(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);

        $this->actingAs($context['admin'])
            ->put(route('users.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => UserRole::Moderator->value,
                'password' => 'brand-new-pass',
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass', $staff->fresh()->password));
    }

    public function test_update_rejects_short_password_and_duplicate_email(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);
        $other = $this->makeStaff($context, UserRole::Admin);

        $this->actingAs($context['admin'])
            ->put(route('users.update', $staff), [
                'name' => $staff->name,
                'email' => $other->email,
                'role' => UserRole::Moderator->value,
                'password' => 'short',
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_update_allows_keeping_own_email(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);

        $this->actingAs($context['admin'])
            ->put(route('users.update', $staff), [
                'name' => 'Same Email',
                'email' => $staff->email,
                'role' => UserRole::Moderator->value,
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_update_requires_permission(): void
    {
        $context = $this->createTenantContext(['view users']);
        $colleague = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);

        $this->actingAs($context['user'])
            ->put(route('users.update', $colleague), ['name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_update_forbids_managing_user_of_another_company(): void
    {
        $context = $this->createSuperAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');

        $this->actingAs($context['admin'])
            ->put(route('users.update', $foreign['user']), [
                'name' => 'Hijack',
                'email' => 'hijack@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->assertForbidden();

        $this->assertNotSame('Hijack', $foreign['user']->fresh()->name);
    }

    public function test_super_admin_cannot_update_or_delete_another_super_admin(): void
    {
        $context = $this->createSuperAdminContext();
        $otherAdmin = User::factory()->create(['company_id' => $context['company']->id]);
        $otherAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($context['admin'])
            ->put(route('users.update', $otherAdmin), [
                'name' => 'Takeover',
                'email' => 'takeover@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->assertForbidden();

        $this->actingAs($context['admin'])
            ->delete(route('users.destroy', $otherAdmin))
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $otherAdmin->id]);
    }

    public function test_destroy_soft_deletes_user_with_remark(): void
    {
        $context = $this->createSuperAdminContext();
        $staff = $this->makeStaff($context, UserRole::Moderator);

        $this->actingAs($context['admin'])
            ->delete(route('users.destroy', $staff), ['delete_remark' => 'Left company'])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'delete_remark' => 'Left company',
            'deleted_by' => $context['admin']->id,
        ]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view users', 'edit users']);
        $colleague = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);

        $this->actingAs($context['user'])
            ->delete(route('users.destroy', $colleague))
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $colleague->id]);
    }

    public function test_regular_user_with_delete_permission_can_delete_same_company_user_only(): void
    {
        $context = $this->createTenantContext(['delete users']);
        $colleague = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
        $foreign = $this->createTenantContext([], 'Foreign');

        $this->actingAs($context['user'])
            ->delete(route('users.destroy', $foreign['user']))
            ->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $foreign['user']->id]);

        $this->actingAs($context['user'])
            ->delete(route('users.destroy', $colleague))
            ->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $colleague->id]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
    }

    /**
     * @return array<string, mixed>
     */
    private function createSuperAdminContext(): array
    {
        foreach ([UserRole::SuperAdmin, UserRole::Admin, UserRole::Moderator, UserRole::Accountant] as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        $context = $this->createTenantContext(RolePermissions::superAdminPermissions());

        // The tenant owner doubles as the Super Admin who owns the company.
        $admin = $context['owner'];
        $admin->assignRole(UserRole::SuperAdmin->value);
        $admin->givePermissionTo(RolePermissions::superAdminPermissions());
        $context['admin'] = $admin;

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeStaff(array $context, UserRole $role): User
    {
        $user = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        $branch = $context['branch'] instanceof Branch ? $context['branch'] : null;

        return array_merge([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'secret-pass-1',
            'role' => UserRole::Moderator->value,
            'company_id' => $context['company']->id,
            'branch_id' => $branch?->id,
        ], $overrides);
    }
}

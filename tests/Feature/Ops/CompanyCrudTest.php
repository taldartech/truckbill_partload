<?php

namespace Tests\Feature\Ops;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class CompanyCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_companies_for_permitted_user(): void
    {
        $context = $this->createTenantContext(['view companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Index')
                ->has('companies.data')
            );
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext([]);

        $this->actingAs($context['user'])
            ->get(route('companies.index'))
            ->assertForbidden();
    }

    public function test_index_for_super_admin_only_lists_owned_companies(): void
    {
        $admin = $this->createSuperAdmin();
        $owned = Company::create(['name' => 'Owned Co', 'user_id' => $admin->id]);
        $foreign = $this->createTenantContext([], 'Z');

        $this->actingAs($admin)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('companies.data', 1)
                ->where('companies.data.0.id', $owned->id)
            );

        $this->assertNotSame($owned->id, $foreign['company']->id);
    }

    public function test_create_page_provides_geo_data_and_default_country(): void
    {
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Create')
                ->has('countries', 1)
                ->has('states', 1)
                ->has('cities', 1)
                ->where('defaultCountryId', $context['country']->id)
            );
    }

    public function test_create_page_requires_permission(): void
    {
        $context = $this->createTenantContext(['view companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.create'))
            ->assertForbidden();
    }

    public function test_store_creates_company_with_trial_subscription_for_super_admin(): void
    {
        Http::fake();
        $admin = $this->createSuperAdmin(['create companies']);
        $geo = $this->createTenantContext([], 'Geo');

        $this->actingAs($admin)
            ->post(route('companies.store'), $this->validPayload($geo))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Company created successfully.');

        $company = Company::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertSame($admin->id, $company->user_id);
        $this->assertFalse($company->has_gst);
        $this->assertDatabaseHas('company_subscriptions', [
            'company_id' => $company->id,
            'status' => 'trial',
        ]);
    }

    public function test_store_with_gst_persists_gst_details(): void
    {
        Http::fake();
        $admin = $this->createSuperAdmin(['create companies']);
        $geo = $this->createTenantContext([], 'Geo');

        $this->actingAs($admin)
            ->post(route('companies.store'), $this->validPayload($geo, [
                'has_gst' => true,
                'gst_number' => '08ABCDE1234F1Z5',
                'gst_type' => 'Regular',
                'gst_scheme' => 'Normal',
                'gst_slab' => 12,
                'gst_exemption_limit' => 40000,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', [
            'email' => 'new@example.com',
            'has_gst' => true,
            'gst_number' => '08ABCDE1234F1Z5',
        ]);
    }

    public function test_store_clears_gst_fields_when_has_gst_is_false(): void
    {
        Http::fake();
        $admin = $this->createSuperAdmin(['create companies']);
        $geo = $this->createTenantContext([], 'Geo');

        $this->actingAs($admin)
            ->post(route('companies.store'), $this->validPayload($geo, [
                'has_gst' => false,
                'gst_number' => '08ABCDE1234F1Z5',
            ]))
            ->assertSessionHasNoErrors();

        $company = Company::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertNull($company->gst_number);
    }

    public function test_store_uploads_company_logo(): void
    {
        Http::fake();
        Storage::fake('public');
        $admin = $this->createSuperAdmin(['create companies']);
        $geo = $this->createTenantContext([], 'Geo');

        $this->actingAs($admin)
            ->post(route('companies.store'), $this->validPayload($geo, [
                'company_logo' => UploadedFile::fake()->image('logo.png'),
            ]))
            ->assertSessionHasNoErrors();

        $company = Company::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertStringStartsWith('/storage/company_logos/', (string) $company->company_logo);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $company->company_logo));
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseMissing('companies', ['email' => 'new@example.com']);
    }

    public function test_store_requires_mandatory_fields(): void
    {
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), [])
            ->assertSessionHasErrors([
                'name',
                'email',
                'address',
                'country_id',
                'state_id',
                'city_id',
                'pin_code',
                'contact_person',
                'contact_number',
            ]);
    }

    public function test_store_validates_formats(): void
    {
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context, [
                'email' => 'not-an-email',
                'pin_code' => '12',
                'contact_number' => '1234567890',
                'pan_no' => 'BADPAN',
                'account_number' => '12',
                'ifsc_code' => 'XXXX',
            ]))
            ->assertSessionHasErrors([
                'email',
                'pin_code',
                'contact_number',
                'pan_no',
                'account_number',
                'ifsc_code',
            ]);
    }

    public function test_store_requires_gst_details_when_has_gst(): void
    {
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context, ['has_gst' => true]))
            ->assertSessionHasErrors(['gst_number', 'gst_type', 'gst_scheme', 'gst_slab', 'gst_exemption_limit']);
    }

    public function test_store_rejects_invalid_gst_number_and_slab(): void
    {
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context, [
                'has_gst' => true,
                'gst_number' => 'BADGST',
                'gst_type' => 'Regular',
                'gst_scheme' => 'Normal',
                'gst_slab' => 150,
                'gst_exemption_limit' => -1,
            ]))
            ->assertSessionHasErrors(['gst_number', 'gst_slab', 'gst_exemption_limit']);
    }

    public function test_store_rejects_duplicate_email_and_invalid_logo(): void
    {
        Storage::fake('public');
        $context = $this->createTenantContext(['create companies']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context, [
                'company_logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('company_logo');

        $context['company']->update(['email' => 'dup@example.com']);

        $this->actingAs($context['user'])
            ->post(route('companies.store'), $this->validPayload($context, ['email' => 'dup@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_show_requires_permission_and_renders_for_permitted_user(): void
    {
        $context = $this->createTenantContext(['view companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.show', $context['company']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Show')
                ->where('company.id', $context['company']->id)
            );

        $noPermission = $this->createTenantContext([], 'C');
        $this->actingAs($noPermission['user'])
            ->get(route('companies.show', $noPermission['company']))
            ->assertForbidden();
    }

    public function test_edit_page_renders_for_permitted_user(): void
    {
        $context = $this->createTenantContext(['edit companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.edit', $context['company']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Edit')
                ->where('company.id', $context['company']->id)
                ->has('countries')
            );
    }

    public function test_edit_requires_permission(): void
    {
        $context = $this->createTenantContext(['view companies']);

        $this->actingAs($context['user'])
            ->get(route('companies.edit', $context['company']))
            ->assertForbidden();
    }

    public function test_update_changes_company(): void
    {
        $context = $this->createTenantContext(['edit companies']);

        $this->actingAs($context['user'])
            ->put(route('companies.update', $context['company']), $this->validPayload($context, [
                'name' => 'Renamed Co',
                'email' => 'renamed@example.com',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Company updated successfully.');

        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'name' => 'Renamed Co',
            'email' => 'renamed@example.com',
        ]);
    }

    public function test_update_allows_keeping_own_email(): void
    {
        $context = $this->createTenantContext(['edit companies']);
        $context['company']->update(['email' => 'keep@example.com']);

        $this->actingAs($context['user'])
            ->put(route('companies.update', $context['company']), $this->validPayload($context, [
                'email' => 'keep@example.com',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejects_email_used_by_another_company(): void
    {
        $context = $this->createTenantContext(['edit companies']);
        $other = $this->createTenantContext([], 'B');
        $other['company']->update(['email' => 'taken@example.com']);

        $this->actingAs($context['user'])
            ->put(route('companies.update', $context['company']), $this->validPayload($context, [
                'email' => 'taken@example.com',
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit companies']);

        $this->actingAs($context['user'])
            ->put(route('companies.update', $context['company']), ['name' => ''])
            ->assertSessionHasErrors(['name', 'email', 'address', 'pin_code', 'contact_number']);

        $viewer = $this->createTenantContext(['view companies'], 'C');
        $this->actingAs($viewer['user'])
            ->put(route('companies.update', $viewer['company']), $this->validPayload($viewer))
            ->assertForbidden();
    }

    public function test_update_without_gst_resets_gst_fields(): void
    {
        $context = $this->createTenantContext(['edit companies']);
        $context['company']->update([
            'has_gst' => true,
            'gst_number' => '08ABCDE1234F1Z5',
            'gst_slab' => 12,
        ]);

        $this->actingAs($context['user'])
            ->put(route('companies.update', $context['company']), $this->validPayload($context, ['has_gst' => false]))
            ->assertSessionHasNoErrors();

        $company = $context['company']->fresh();
        $this->assertFalse($company->has_gst);
        $this->assertNull($company->gst_number);
        $this->assertNull($company->gst_slab);
    }

    public function test_super_admin_cannot_view_edit_update_or_delete_company_owned_by_someone_else(): void
    {
        $admin = $this->createSuperAdmin(['view companies', 'edit companies', 'delete companies']);
        $foreign = $this->createTenantContext([], 'Foreign');

        $this->actingAs($admin)->get(route('companies.show', $foreign['company']))->assertForbidden();
        $this->actingAs($admin)->get(route('companies.edit', $foreign['company']))->assertForbidden();
        $this->actingAs($admin)
            ->put(route('companies.update', $foreign['company']), $this->validPayload($foreign))
            ->assertForbidden();
        $this->actingAs($admin)->delete(route('companies.destroy', $foreign['company']))->assertForbidden();

        $this->assertNotSoftDeleted('companies', ['id' => $foreign['company']->id]);
    }

    public function test_super_admin_can_manage_own_company(): void
    {
        $admin = $this->createSuperAdmin(['view companies', 'edit companies', 'delete companies']);
        $own = Company::create(['name' => 'Mine', 'user_id' => $admin->id]);

        $this->actingAs($admin)->get(route('companies.show', $own))->assertOk();
        $this->actingAs($admin)->get(route('companies.edit', $own))->assertOk();
        $this->actingAs($admin)
            ->delete(route('companies.destroy', $own))
            ->assertRedirect(route('companies.index'));

        $this->assertSoftDeleted('companies', ['id' => $own->id]);
    }

    public function test_destroy_soft_deletes_with_remark(): void
    {
        $context = $this->createTenantContext(['delete companies']);

        $this->actingAs($context['user'])
            ->delete(route('companies.destroy', $context['company']), ['delete_remark' => 'Closed down'])
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Company deleted successfully.');

        $this->assertSoftDeleted('companies', ['id' => $context['company']->id]);
        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'delete_remark' => 'Closed down',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view companies', 'edit companies']);

        $this->actingAs($context['user'])
            ->delete(route('companies.destroy', $context['company']))
            ->assertForbidden();

        $this->assertNotSoftDeleted('companies', ['id' => $context['company']->id]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('companies.index'))->assertRedirect(route('login'));
        $this->post(route('companies.store'), [])->assertRedirect(route('login'));
    }

    /**
     * @param  list<string>  $extraPermissions
     */
    private function createSuperAdmin(array $extraPermissions = []): User
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');

        foreach ([...RolePermissions::superAdminPermissions(), ...$extraPermissions] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);
        $user->givePermissionTo([...RolePermissions::superAdminPermissions(), ...$extraPermissions]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Transport Co',
            'email' => 'new@example.com',
            'address' => '5 Industrial Area',
            'country_id' => $context['country']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'pin_code' => '313001',
            'contact_person' => 'Owner',
            'contact_number' => '9876543210',
        ], $overrides);
    }
}

<?php

namespace Tests\Feature\Ops;

use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BranchSettingsControllerTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_edit_renders_settings_with_defaults(): void
    {
        $context = $this->createTenantContext(['view branches']);
        $context['branch']->settings()->update(['bilty_initial' => 'BLT/']);

        $this->actingAs($context['user'])
            ->get(route('branches.settings.edit', $context['branch']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Branches/Settings')
                ->where('branch.id', $context['branch']->id)
                ->where('branch.name', $context['branch']->name)
                ->where('settings.bilty_initial', 'BLT/')
                ->where('settings.challan_initial', '')
                ->where('settings.delivery_initial', '')
                ->where('settings.bilty_print_config.copies.consignor.enabled', true)
                ->where('settings.bilty_print_config.copies.driver.show_amount_by_bob.to_pay', false));
    }

    public function test_edit_creates_missing_settings_row(): void
    {
        $context = $this->createTenantContext(['view branches']);
        BranchSetting::query()->where('branch_id', $context['branch']->id)->delete();

        $this->actingAs($context['user'])
            ->get(route('branches.settings.edit', $context['branch']))
            ->assertOk();

        $this->assertDatabaseHas('branch_settings', ['branch_id' => $context['branch']->id]);
    }

    public function test_edit_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit branches']);

        $this->actingAs($context['user'])
            ->get(route('branches.settings.edit', $context['branch']))
            ->assertForbidden();
    }

    public function test_edit_hides_other_companys_branch(): void
    {
        $context = $this->createTenantContext(['view branches']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->get(route('branches.settings.edit', $other['branch']))
            ->assertNotFound();
    }

    public function test_super_admin_can_edit_own_company_branch_but_not_others(): void
    {
        $context = $this->createTenantContext(['view branches', 'edit branches']);
        $other = $this->createTenantContext([], 'B');
        $this->giveRole($context['owner'], 'super_admin');
        $context['owner']->givePermissionTo(['view branches', 'edit branches']);

        $this->actingAs($context['owner'])
            ->get(route('branches.settings.edit', $context['branch']))
            ->assertOk();

        $this->actingAs($context['owner'])
            ->get(route('branches.settings.edit', $other['branch']))
            ->assertNotFound();
    }

    public function test_update_persists_trimmed_initials_and_nulls_blank_ones(): void
    {
        $context = $this->createTenantContext(['edit branches']);
        $context['branch']->settings()->update(['invoice_initial' => 'OLD/']);

        $this->actingAs($context['user'])
            ->put(route('branches.settings.update', $context['branch']), [
                'bilty_initial' => '  BLT/  ',
                'voucher_initial' => 'VCH/',
                'delivery_initial' => 'DR/',
                'invoice_initial' => '   ',
                'challan_initial' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success', 'Branch settings updated successfully.');

        $settings = $context['branch']->fresh('settings')->settings;
        $this->assertSame('BLT/', $settings->bilty_initial);
        $this->assertSame('VCH/', $settings->voucher_initial);
        $this->assertSame('DR/', $settings->delivery_initial);
        $this->assertNull($settings->invoice_initial);
        $this->assertNull($settings->challan_initial);
        $this->assertTrue($settings->resolvedBiltyPrintConfig()['copies']['office']['enabled']);
    }

    public function test_update_creates_settings_when_missing(): void
    {
        $context = $this->createTenantContext(['edit branches']);
        BranchSetting::query()->where('branch_id', $context['branch']->id)->delete();

        $this->actingAs($context['user'])
            ->put(route('branches.settings.update', $context['branch']), ['main_challan_initial' => 'MCH/'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $context['branch']->id,
            'main_challan_initial' => 'MCH/',
        ]);
    }

    public function test_update_validates_initial_length_and_print_config(): void
    {
        $context = $this->createTenantContext(['edit branches']);

        $this->actingAs($context['user'])
            ->put(route('branches.settings.update', $context['branch']), [
                'bilty_initial' => str_repeat('A', 21),
                'inward_register_initial' => str_repeat('B', 21),
                'bilty_print_config' => [
                    'note' => str_repeat('n', 1001),
                    'copies' => ['consignor' => ['enabled' => 'sometimes']],
                ],
            ])
            ->assertSessionHasErrors([
                'bilty_initial',
                'inward_register_initial',
                'bilty_print_config.note',
                'bilty_print_config.copies.consignor.enabled',
            ]);
    }

    public function test_update_rejects_config_with_every_copy_disabled(): void
    {
        $context = $this->createTenantContext(['edit branches']);
        $disabled = ['enabled' => false];

        $this->actingAs($context['user'])
            ->putJson(route('branches.settings.update', $context['branch']), [
                'bilty_print_config' => [
                    'copies' => [
                        'consignor' => $disabled,
                        'consignee' => $disabled,
                        'driver' => $disabled,
                        'office' => $disabled,
                    ],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_update_requires_permission(): void
    {
        $context = $this->createTenantContext(['view branches']);

        $this->actingAs($context['user'])
            ->put(route('branches.settings.update', $context['branch']), ['bilty_initial' => 'X/'])
            ->assertForbidden();

        $this->assertNull($context['branch']->fresh('settings')->settings->bilty_initial);
    }

    public function test_update_cannot_touch_other_companys_branch(): void
    {
        $context = $this->createTenantContext(['edit branches']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->put(route('branches.settings.update', $other['branch']), ['bilty_initial' => 'HACK/'])
            ->assertNotFound();

        $this->assertNull(Branch::query()->withoutGlobalScopes()->findOrFail($other['branch']->id)->settings->bilty_initial);
    }
}

<?php

namespace Tests\Feature\Ops;

use App\Models\Group;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PartyControllerExtrasTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Accounting Party',
            'address' => '12 Market Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9876543210',
        ], $overrides);
    }

    private function party(array $context, string $name, array $overrides = []): Party
    {
        return Party::create(array_merge([
            'company_id' => $context['company']->id,
            'name' => $name,
            'address' => '1 Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9876543210',
        ], $overrides));
    }

    public function test_index_filters_by_name_gst_or_mobile(): void
    {
        $context = $this->createTenantContext(['view parties']);
        $this->party($context, 'Alpha Traders', ['gst_number' => '08AAAAA0000A1Z5']);
        $this->party($context, 'Beta Movers', ['mobile' => '9123456789']);

        foreach ([['Alpha', 'Alpha Traders'], ['08AAAAA', 'Alpha Traders'], ['9123456', 'Beta Movers']] as [$search, $expected]) {
            $this->actingAs($context['user'])
                ->get(route('parties.index', ['search' => $search]))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Parties/Index')
                    ->has('parties.data', 1)
                    ->where('parties.data.0.name', $expected)
                    ->where('filters.search', $search)
                    ->etc()
                );
        }
    }

    public function test_create_and_edit_redirect_to_index(): void
    {
        $context = $this->createTenantContext(['create parties', 'edit parties']);
        $party = $this->party($context, 'Redirect Party');

        $this->actingAs($context['user'])->get(route('parties.create'))->assertRedirect(route('parties.index'));
        $this->actingAs($context['user'])->get(route('parties.edit', $party))->assertRedirect(route('parties.index'));
    }

    public function test_quick_form_data_and_quick_show_return_json(): void
    {
        $context = $this->createTenantContext(['create parties', 'view parties']);
        Group::create(['company_id' => $context['company']->id, 'name' => 'Preferred']);
        $group = Group::query()->firstOrFail();
        $party = $this->party($context, 'Quick Party', ['group_id' => $group->id]);

        $this->actingAs($context['user'])
            ->get(route('parties.quick-form-data'))
            ->assertOk()
            ->assertJsonPath('default_company_id', $context['company']->id)
            ->assertJsonPath('groups.0.name', 'Preferred')
            ->assertJsonStructure(['groups', 'states', 'default_company_id']);

        $this->actingAs($context['user'])
            ->get(route('parties.quick', $party))
            ->assertOk()
            ->assertJsonPath('rows.0.value', 'Quick Party')
            ->assertJsonPath('rows.2.value', $context['state']->name)
            ->assertJsonPath('rows.7.value', 'Preferred');
    }

    public function test_store_and_update_link_and_unlink_accounting_ledger(): void
    {
        $context = $this->createTenantContext(['create parties', 'edit parties']);

        $this->actingAs($context['user'])
            ->post(route('parties.store'), $this->payload($context, ['consider_for_accounting' => true]))
            ->assertSessionHasNoErrors();

        $party = Party::query()->where('name', 'Accounting Party')->firstOrFail();
        $this->assertDatabaseHas('accounts', [
            'company_id' => $context['company']->id,
            'reference_type' => Party::class,
            'reference_id' => $party->id,
            'is_selectable' => true,
        ]);

        $this->actingAs($context['user'])
            ->put(route('parties.update', $party), $this->payload($context, [
                'name' => 'Accounting Party Renamed',
                'consider_for_accounting' => false,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'reference_id' => $party->id,
            'reference_type' => Party::class,
            'name' => 'Accounting Party Renamed',
            'is_selectable' => false,
        ]);

        $this->actingAs($context['user'])
            ->put(route('parties.update', $party), $this->payload($context, [
                'name' => 'Accounting Party Renamed',
                'consider_for_accounting' => true,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'reference_id' => $party->id,
            'reference_type' => Party::class,
            'is_selectable' => true,
            'is_active' => true,
        ]);
    }
}

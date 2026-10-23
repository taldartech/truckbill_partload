<?php

namespace Tests\Feature\Tenant;

use App\Enums\UserRole;
use App\Models\Bilty;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_company_scoped_index_excludes_other_company_parties(): void
    {
        $a = $this->createTenantContext(['view parties'], 'A');
        $b = $this->createTenantContext(['view parties'], 'B');

        Party::create(['company_id' => $a['company']->id, 'name' => 'Party A Only']);
        Party::create(['company_id' => $b['company']->id, 'name' => 'Party B Only']);

        $response = $this->actingAs($a['user'])->get(route('parties.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Parties/Index')
            ->has('parties.data', 1)
            ->where('parties.data.0.name', 'Party A Only'));
    }

    public function test_branch_scoped_bilty_is_not_visible_across_companies(): void
    {
        $a = $this->createTenantContext(['view bilties'], 'A');
        $b = $this->createTenantContext(['view bilties'], 'B');

        $party = Party::create(['company_id' => $b['company']->id, 'name' => 'Consignee']);

        $bilty = Bilty::create([
            'company_id' => $b['company']->id,
            'branch_id' => $b['branch']->id,
            'bilty_number' => 'BLT-ISO-1',
            'date' => now()->toDateString(),
            'from_station_id' => $b['station']->id,
            'to_station_id' => $b['station']->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'payment_type' => 'To Pay',
            'freight' => 100,
            'amount' => 100,
            'status' => 'Pending',
        ]);

        $this->actingAs($a['user'])
            ->get(route('bilties.show', $bilty))
            ->assertNotFound();
    }

    public function test_master_admin_cannot_access_operational_bilties_index(): void
    {
        Role::findOrCreate(UserRole::MasterAdmin->value);
        $user = User::factory()->create();
        $user->assignRole(UserRole::MasterAdmin->value);

        $this->actingAs($user)
            ->get(route('bilties.index'))
            ->assertRedirect();
    }
}

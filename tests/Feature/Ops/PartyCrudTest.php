<?php

namespace Tests\Feature\Ops;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PartyCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_user_can_create_and_view_party(): void
    {
        $context = $this->createTenantContext(['create parties', 'view parties']);

        $this->actingAs($context['user'])
            ->post(route('parties.store'), [
                'name' => 'New Consignor',
                'address' => '12 Market Road',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'mobile' => '9876543210',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('parties', [
            'company_id' => $context['company']->id,
            'name' => 'New Consignor',
        ]);

        $party = Party::query()->where('name', 'New Consignor')->firstOrFail();

        $this->actingAs($context['user'])
            ->get(route('parties.show', $party))
            ->assertOk();
    }

    public function test_party_store_requires_name(): void
    {
        $context = $this->createTenantContext(['create parties']);

        $this->actingAs($context['user'])
            ->from(route('parties.create'))
            ->post(route('parties.store'), [])
            ->assertSessionHasErrors('name');
    }
}

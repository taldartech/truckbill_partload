<?php

namespace Tests\Feature\Negative;

use App\Models\Good;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ValidationRejectionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_party_store_rejects_missing_required_fields_without_writing(): void
    {
        $context = $this->createTenantContext(['create parties']);
        $before = Party::query()->count();

        $this->actingAs($context['user'])
            ->from(route('parties.create'))
            ->post(route('parties.store'), [
                'name' => '',
            ])
            ->assertRedirect(route('parties.create'))
            ->assertSessionHasErrors(['name', 'address', 'city_id', 'state_id', 'mobile']);

        $this->assertSame($before, Party::query()->count());
    }

    public function test_party_store_rejects_invalid_mobile_without_writing(): void
    {
        $context = $this->createTenantContext(['create parties']);
        $before = Party::query()->count();

        $this->actingAs($context['user'])
            ->from(route('parties.create'))
            ->post(route('parties.store'), [
                'name' => 'Invalid Mobile Party',
                'address' => '1 Road',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'mobile' => '12345',
            ])
            ->assertRedirect(route('parties.create'))
            ->assertSessionHasErrors('mobile');

        $this->assertSame($before, Party::query()->count());
        $this->assertDatabaseMissing('parties', ['name' => 'Invalid Mobile Party']);
    }

    public function test_good_store_rejects_missing_name_without_writing(): void
    {
        $context = $this->createTenantContext(['create goods']);

        $this->actingAs($context['user'])
            ->from(route('goods.create'))
            ->post(route('goods.store'), [])
            ->assertRedirect(route('goods.create'))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Good::query()->count());
    }
}

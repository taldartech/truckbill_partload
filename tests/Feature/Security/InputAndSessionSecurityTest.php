<?php

namespace Tests\Feature\Security;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class InputAndSessionSecurityTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_party_search_with_sql_meta_characters_does_not_error_or_leak_rows(): void
    {
        $context = $this->createTenantContext(['view parties'], 'Sec');
        Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Safe Party',
        ]);

        $payload = "' OR '1'='1";

        $this->actingAs($context['user'])
            ->get(route('parties.index', ['search' => $payload]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Parties/Index')
                ->has('parties.data', 0)
                ->where('filters.search', $payload));
    }

    public function test_reflected_xss_payload_in_search_is_returned_as_plain_filter_value(): void
    {
        $context = $this->createTenantContext(['view parties'], 'Xss');
        $payload = '<script>alert(1)</script>';

        $response = $this->actingAs($context['user'])
            ->get(route('parties.index', ['search' => $payload]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.search', $payload));

        // Inertia JSON must not treat the payload as executable HTML markup structure.
        $this->assertStringNotContainsString('<script>alert(1)</script></script>', $response->getContent());
    }

    public function test_guest_post_to_protected_route_is_rejected(): void
    {
        $this->post(route('parties.store'), ['name' => 'Guest Party'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('parties', ['name' => 'Guest Party']);
    }

    public function test_user_cannot_delete_another_company_party(): void
    {
        $a = $this->createTenantContext(['delete parties', 'view parties'], 'DelA');
        $b = $this->createTenantContext(['delete parties'], 'DelB');

        $partyB = Party::create([
            'company_id' => $b['company']->id,
            'name' => 'Must Remain',
        ]);

        $this->actingAs($a['user'])
            ->delete(route('parties.destroy', $partyB))
            ->assertNotFound();

        $this->assertDatabaseHas('parties', [
            'id' => $partyB->id,
            'name' => 'Must Remain',
            'deleted_at' => null,
        ]);
    }
}

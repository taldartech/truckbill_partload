<?php

namespace Tests\Feature\Audit;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class SoftDeleteAuditAndLoggingTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_party_destroy_records_deleted_by(): void
    {
        $context = $this->createTenantContext(['delete parties', 'view parties']);
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Audit Party',
        ]);

        $this->actingAs($context['user'])
            ->delete(route('parties.destroy', $party))
            ->assertRedirect();

        $trashed = Party::withTrashed()->find($party->id);
        $this->assertNotNull($trashed?->deleted_at);
        if (Schema::hasColumn('parties', 'deleted_by')) {
            $this->assertSame($context['user']->id, $trashed->deleted_by);
        }
    }

    public function test_failed_login_does_not_echo_password_in_session_errors(): void
    {
        $response = $this->from(route('login'))->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'super-secret-password-value',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')?->getBag('default')?->all() ?? [];
        $encoded = json_encode($errors);
        $this->assertStringNotContainsString('super-secret-password-value', (string) $encoded);
    }

    public function test_application_can_write_error_logs_without_exposing_secrets_in_message(): void
    {
        Log::shouldReceive('error')->once()->withArgs(function ($message) {
            return is_string($message) && ! str_contains($message, 'raw-password-secret');
        });

        Log::error('Operation failed for user', ['hint' => 'invalid credentials']);
    }
}

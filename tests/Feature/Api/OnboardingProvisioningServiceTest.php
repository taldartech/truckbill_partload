<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\OnboardingProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnboardingProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => 'key-1',
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret-pass-1',
        ], $overrides);
    }

    public function test_create_user_is_idempotent_and_rejects_duplicate_emails(): void
    {
        $service = app(OnboardingProvisioningService::class);

        $created = $service->createUser($this->payload());
        $this->assertSame('created', $created['status']);
        $this->assertSame(UserRole::SuperAdmin->value, $created['role']);

        $replayed = $service->createUser($this->payload());
        $this->assertSame('already_provisioned', $replayed['status']);
        $this->assertSame(1, User::query()->where('email', 'owner@example.com')->count());

        try {
            $service->createUser($this->payload(['idempotency_key' => 'key-2']));
            $this->fail('Expected duplicate email validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_update_user_applies_changes_and_reports_status(): void
    {
        $service = app(OnboardingProvisioningService::class);
        $service->createUser($this->payload());

        $status = $service->updateUser([
            'email' => 'owner@example.com',
            'name' => 'Renamed',
            'password' => 'new-secret-9',
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
        ]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->assertSame('Renamed', $user->name);
        $this->assertTrue(Hash::check('new-secret-9', $user->password));
        $this->assertTrue($status['exists']);
        $this->assertTrue($status['email_verified']);
        $this->assertTrue($status['policy_accepted']);
        $this->assertSame(0, $status['company_count']);
        $this->assertSame(UserRole::SuperAdmin->value, $status['role']);

        $unchanged = $service->updateUser(['email' => 'owner@example.com']);
        $this->assertSame('Renamed', User::query()->where('email', 'owner@example.com')->value('name'));
        $this->assertTrue($unchanged['exists']);
    }

    public function test_update_user_rejects_unknown_and_non_super_admin_users(): void
    {
        $service = app(OnboardingProvisioningService::class);
        User::factory()->create(['email' => 'plain@example.com']);

        foreach (['missing@example.com', 'plain@example.com'] as $email) {
            try {
                $service->updateUser(['email' => $email]);
                $this->fail('Expected super admin lookup failure.');
            } catch (ValidationException $e) {
                $this->assertSame(['Super admin user not found.'], $e->errors()['email']);
            }
        }
    }

    public function test_status_helpers_handle_missing_users(): void
    {
        $service = app(OnboardingProvisioningService::class);

        $this->assertSame(['exists' => false, 'email' => null], $service->statusFor(null));
        $this->assertSame(
            ['exists' => false, 'email' => 'nobody@example.com'],
            $service->statusByEmail('nobody@example.com')
        );

        $service->createUser($this->payload());
        $this->assertTrue($service->statusByEmail('owner@example.com')['exists']);

        $plain = User::factory()->create();
        $this->assertNull($service->statusFor($plain)['role']);
    }
}

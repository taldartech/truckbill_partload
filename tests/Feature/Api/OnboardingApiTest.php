<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-onboarding-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['onboarding.signing_secret' => $this->secret]);
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(string $body = ''): array
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $this->secret);

        return [
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_missing_signature_returns_401(): void
    {
        $this->postJson('/api/onboarding/create-user', [
            'idempotency_key' => 'k1',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ])->assertUnauthorized();
    }

    public function test_invalid_signature_returns_401(): void
    {
        $body = json_encode([
            'idempotency_key' => 'k1',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/onboarding/create-user',
            [],
            [],
            [],
            [
                'HTTP_X-Timestamp' => (string) time(),
                'HTTP_X-Signature' => 'deadbeef',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        )->assertUnauthorized();
    }

    public function test_create_user_requires_validation_fields(): void
    {
        $body = '{}';
        $this->call(
            'POST',
            '/api/onboarding/create-user',
            [],
            [],
            [],
            $this->serverHeaders($body),
            $body
        )->assertUnprocessable();
    }

    public function test_create_user_is_idempotent(): void
    {
        $payload = [
            'idempotency_key' => 'idem-1',
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $first = $this->call(
            'POST',
            '/api/onboarding/create-user',
            [],
            [],
            [],
            $this->serverHeaders($body),
            $body
        );
        $first->assertCreated();

        $second = $this->call(
            'POST',
            '/api/onboarding/create-user',
            [],
            [],
            [],
            $this->serverHeaders($body),
            $body
        );
        $second->assertOk();
        $this->assertSame('created', $first->json('status'));
        $this->assertContains($second->json('status'), ['exists', 'created', 'idempotent', 'already_provisioned']);

        $this->assertSame(1, User::query()->where('email', 'super@example.com')->count());
    }

    public function test_status_endpoint_returns_user_state(): void
    {
        User::factory()->create([
            'email' => 'known@example.com',
            'password' => Hash::make('password'),
        ]);

        $query = http_build_query(['email' => 'known@example.com']);
        $body = '';
        $this->call(
            'GET',
            '/api/onboarding/status?'.$query,
            [],
            [],
            [],
            $this->serverHeaders($body)
        )->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function serverHeaders(string $body): array
    {
        $headers = $this->signedHeaders($body);

        return [
            'HTTP_X-Timestamp' => $headers['X-Timestamp'],
            'HTTP_X-Signature' => $headers['X-Signature'],
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}

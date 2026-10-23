<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-onboarding-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['onboarding.signing_secret' => $this->secret]);
    }

    public function test_admin_subscription_index_requires_signature(): void
    {
        $this->getJson('/api/admin/subscriptions')->assertUnauthorized();
    }

    public function test_admin_subscription_index_accepts_valid_signature(): void
    {
        $body = '';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $this->secret);

        $this->call(
            'GET',
            '/api/admin/subscriptions',
            [],
            [],
            [],
            [
                'HTTP_X-Timestamp' => $timestamp,
                'HTTP_X-Signature' => $signature,
                'HTTP_ACCEPT' => 'application/json',
            ]
        )->assertSuccessful();
    }
}

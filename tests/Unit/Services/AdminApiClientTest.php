<?php

namespace Tests\Unit\Services;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Services\AdminApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.admin_api_url' => 'https://admin.example.test/',
            'subscription.signing_secret' => 'secret',
        ]);
    }

    public function test_get_catalog_returns_signed_payload(): void
    {
        Http::fake(['admin.example.test/*' => Http::response(['catalog' => ['base_price' => 100]])]);

        $payload = (new AdminApiClient)->getCatalog();

        $this->assertSame(['base_price' => 100], $payload['catalog']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->hasHeader('X-Signature')
            && $request->hasHeader('X-Timestamp')
            && str_ends_with($request->url(), '/api/v1/products/parchun/subscription-catalog'));
    }

    public function test_get_catalog_without_catalog_key_throws(): void
    {
        Http::fake(['admin.example.test/*' => Http::response(['catalog' => []])]);

        $this->expectException(SubscriptionCatalogUnavailableException::class);

        (new AdminApiClient)->getCatalog();
    }

    public function test_validate_promo_and_increment_use_post_signed_json(): void
    {
        Http::fake(['admin.example.test/*' => Http::response(['valid' => true])]);

        $client = new AdminApiClient;

        $this->assertSame(['valid' => true], $client->validatePromo('SAVE10'));
        $client->incrementPromoUse('SAVE10');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/promo-codes/validate')
            && $request['code'] === 'SAVE10');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/promo-codes/increment-use'));
    }

    public function test_missing_configuration_throws(): void
    {
        config(['subscription.admin_api_url' => '']);

        $this->expectException(SubscriptionCatalogUnavailableException::class);

        (new AdminApiClient)->validatePromo('X');
    }

    public function test_transport_failure_throws(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->expectException(SubscriptionCatalogUnavailableException::class);

        (new AdminApiClient)->validatePromo('X');
    }

    public function test_unsuccessful_response_throws(): void
    {
        Http::fake(['admin.example.test/*' => Http::response([], 500)]);

        $this->expectException(SubscriptionCatalogUnavailableException::class);

        (new AdminApiClient)->incrementPromoUse('X');
    }
}

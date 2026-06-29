<?php

namespace App\Services;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminApiClient
{
    public function getCatalog(): array
    {
        $payload = $this->request('GET', '/api/v1/products/'.config('subscription.product').'/subscription-catalog');

        if (empty($payload['catalog']) || ! is_array($payload['catalog'])) {
            throw new SubscriptionCatalogUnavailableException;
        }

        return $payload;
    }

    public function validatePromo(string $code): array
    {
        return $this->request('POST', '/api/v1/products/'.config('subscription.product').'/promo-codes/validate', [
            'code' => $code,
        ]);
    }

    public function incrementPromoUse(string $code): void
    {
        $this->request('POST', '/api/v1/products/'.config('subscription.product').'/promo-codes/increment-use', [
            'code' => $code,
        ]);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('subscription.admin_api_url'), '/');
        $secret = (string) config('subscription.signing_secret');

        if ($baseUrl === '' || $secret === '') {
            throw new SubscriptionCatalogUnavailableException;
        }

        $url = $baseUrl.$path;
        $timestamp = (string) time();
        $body = $method === 'GET' ? '' : json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        $request = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ]);

        try {
            $response = $method === 'GET'
                ? $request->get($url)
                : $request->withBody($body, 'application/json')->post($url);
        } catch (\Throwable) {
            throw new SubscriptionCatalogUnavailableException;
        }

        if (! $response->successful()) {
            throw new SubscriptionCatalogUnavailableException;
        }

        return $response->json() ?? [];
    }
}

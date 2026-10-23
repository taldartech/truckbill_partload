<?php

namespace Tests\Concerns;

use ReflectionClass;
use WpOrg\Requests\Requests;
use WpOrg\Requests\Transport;

/**
 * In-memory HTTP transport that replaces the real Razorpay network call.
 */
class FakeRazorpayTransport implements Transport
{
    /** @var list<array{url: string, headers: array, data: mixed}> */
    public static array $requests = [];

    public static int $status = 200;

    /** @var array<string, mixed> */
    public static array $body = ['id' => 'order_TEST123', 'entity' => 'order', 'status' => 'created'];

    /** @var array<string, mixed> */
    private static array $savedTransports = [];

    /** @var array<string, mixed> */
    private static array $savedCache = [];

    public static function install(): void
    {
        self::$requests = [];
        self::$status = 200;
        self::$body = ['id' => 'order_TEST123', 'entity' => 'order', 'status' => 'created'];

        $reflection = new ReflectionClass(Requests::class);
        $transports = $reflection->getProperty('transports');
        $cache = $reflection->getProperty('transport');

        self::$savedTransports = $transports->getValue();
        self::$savedCache = $cache->getValue();

        $transports->setValue(null, [self::class => self::class]);
        $cache->setValue(null, []);
    }

    public static function uninstall(): void
    {
        $reflection = new ReflectionClass(Requests::class);
        $reflection->getProperty('transports')->setValue(null, self::$savedTransports);
        $reflection->getProperty('transport')->setValue(null, self::$savedCache);
    }

    public function request($url, $headers = [], $data = [], $options = [])
    {
        self::$requests[] = ['url' => $url, 'headers' => $headers, 'data' => $data];

        $reason = self::$status >= 400 ? 'Bad Request' : 'OK';

        return 'HTTP/1.1 '.self::$status.' '.$reason."\r\nContent-Type: application/json\r\n\r\n".json_encode(self::$body);
    }

    public function request_multiple($requests, $options)
    {
        return [];
    }

    public static function test($capabilities = [])
    {
        return true;
    }
}

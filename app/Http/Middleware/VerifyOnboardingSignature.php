<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOnboardingSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('onboarding.signing_secret');

        if (empty($secret)) {
            return response()->json(['message' => 'Onboarding is not configured.'], 503);
        }

        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if ($timestamp === null || $signature === null) {
            return response()->json(['message' => 'Missing signature headers.'], 401);
        }

        if (! ctype_digit((string) $timestamp)) {
            return response()->json(['message' => 'Invalid timestamp.'], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Request timestamp expired.'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, (string) $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}

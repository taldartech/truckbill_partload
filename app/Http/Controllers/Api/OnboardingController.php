<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnboardingProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingProvisioningService $provisioning,
    ) {}

    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'email_verified_at' => ['nullable', 'date'],
            'policy_accepted_at' => ['nullable', 'date'],
            'role' => ['required', 'in:super_admin'],
        ]);

        $response = $this->provisioning->createUser($validated);

        $statusCode = ($response['status'] ?? null) === 'created' ? 201 : 200;

        return response()->json($response, $statusCode);
    }

    public function updateUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8'],
            'email_verified_at' => ['nullable', 'date'],
            'policy_accepted_at' => ['nullable', 'date'],
        ]);

        return response()->json($this->provisioning->updateUser($validated));
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return response()->json($this->provisioning->statusByEmail($validated['email']));
    }
}

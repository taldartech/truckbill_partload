<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\OnboardingProvisioningLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingProvisioningService
{
    public function createUser(array $payload): array
    {
        $existingLog = OnboardingProvisioningLog::query()
            ->where('idempotency_key', $payload['idempotency_key'])
            ->first();

        if ($existingLog !== null && $existingLog->response_payload !== null) {
            $response = $existingLog->response_payload;

            if (($response['status'] ?? null) === 'created') {
                $response['status'] = 'already_provisioned';
            }

            return $response;
        }

        if (User::query()->where('email', $payload['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'The email has already been taken.',
            ]);
        }

        return DB::transaction(function () use ($payload): array {
            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'company_id' => null,
                'branch_id' => null,
                'email_verified_at' => $payload['email_verified_at'] ?? null,
                'policy_accepted_at' => $payload['policy_accepted_at'] ?? null,
                'policy_version' => $payload['policy_version'] ?? null,
            ]);

            $user->assignRole(UserRole::SuperAdmin->value);

            $user->onboarding()->create([
                'completed_at' => now(),
            ]);

            $companyId = null;

            if (! empty($payload['company_name'])) {
                $company = Company::create([
                    'name' => $payload['company_name'],
                    'user_id' => $user->id,
                    'mobile' => $payload['mobile'] ?? null,
                ]);
                $companyId = $company->id;
            }

            $response = [
                'status' => 'created',
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => UserRole::SuperAdmin->value,
                'company_id' => $companyId,
            ];

            OnboardingProvisioningLog::query()->updateOrCreate(
                ['idempotency_key' => $payload['idempotency_key']],
                [
                    'action' => 'create_user',
                    'user_id' => $user->id,
                    'request_payload' => $this->redactPayload($payload),
                    'response_payload' => $response,
                ]
            );

            return $response;
        });
    }

    public function updateUser(array $payload): array
    {
        $user = User::query()
            ->with(['roles', 'onboarding', 'ownedCompanies'])
            ->where('email', $payload['email'])
            ->first();

        if ($user === null || ! $user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Super admin user not found.',
            ]);
        }

        $updates = collect($payload)
            ->only(['name', 'email_verified_at', 'policy_accepted_at', 'policy_version'])
            ->filter(fn ($value) => $value !== null)
            ->all();

        if (! empty($payload['password'])) {
            $updates['password'] = $payload['password'];
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        if (! empty($payload['company_name'])) {
            $company = $user->ownedCompanies()->orderBy('id')->first();

            if ($company !== null) {
                $company->update(array_filter([
                    'name' => $payload['company_name'],
                    'mobile' => $payload['mobile'] ?? null,
                ], fn ($value) => $value !== null));
            } else {
                Company::create([
                    'name' => $payload['company_name'],
                    'user_id' => $user->id,
                    'mobile' => $payload['mobile'] ?? null,
                ]);
            }
        }

        return $this->statusFor($user->fresh(['roles', 'onboarding', 'ownedCompanies']));
    }

    public function statusFor(?User $user): array
    {
        if ($user === null) {
            return [
                'exists' => false,
                'email' => null,
            ];
        }

        return [
            'exists' => true,
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->isSuperAdmin() ? UserRole::SuperAdmin->value : $user->roles->first()?->name,
            'email_verified' => $user->email_verified_at !== null,
            'policy_accepted' => $user->policy_accepted_at !== null,
            'policy_version' => $user->policy_version,
            'company_count' => $user->ownedCompanies()->count(),
            'status' => $user->onboardingStatusLabel(),
        ];
    }

    public function statusByEmail(string $email): array
    {
        $user = User::query()
            ->with(['roles', 'onboarding', 'ownedCompanies'])
            ->where('email', $email)
            ->first();

        if ($user === null) {
            return [
                'exists' => false,
                'email' => $email,
            ];
        }

        return $this->statusFor($user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        unset($payload['password']);

        return $payload;
    }
}

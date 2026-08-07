<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionPricingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionUsageService $usageService,
        private readonly SubscriptionPricingService $pricingService,
        private readonly SubscriptionCheckoutService $checkoutService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Company::query()->with('subscription')->orderBy('name');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate((int) $request->input('per_page', 25));

        $data = $companies->through(function (Company $company) {
            $subscription = $company->subscription;

            return [
                'id' => $company->id,
                'name' => $company->name,
                'status' => $subscription?->status,
                'ends_on' => $subscription?->ends_on?->toDateString(),
                'usage' => $this->usageService->usageForCompany($company),
            ];
        });

        return response()->json($data);
    }

    public function search(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function show(int $id): JsonResponse
    {
        $company = Company::with('subscription')->findOrFail($id);
        $subscription = $company->subscription;

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'subscription' => $subscription,
            'usage' => $this->usageService->usageForCompany($company),
            'payments' => SubscriptionPayment::query()
                ->where('company_id', $company->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'upcoming' => UpcomingSubscription::query()
                ->where('company_id', $company->id)
                ->where('status', 'pending')
                ->get(),
        ]);
    }

    public function quote(Request $request, int $id): JsonResponse
    {
        $company = Company::with('subscription')->findOrFail($id);

        return response()->json(
            $this->pricingService->quote($company, $request->validate($this->purchaseRules())),
        );
    }

    public function recordOfflinePayment(Request $request, int $id): JsonResponse
    {
        $company = Company::with('subscription')->findOrFail($id);

        $validated = $request->validate([
            ...$this->purchaseRules(),
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'force' => ['sometimes', 'boolean'],
            'admin_user_id' => ['nullable', 'integer'],
        ]);

        try {
            $payment = $this->checkoutService->recordOfflinePurchase($company, $validated, null);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'payment' => $payment,
            'subscription' => $company->fresh(['subscription'])->subscription,
        ]);
    }

    public function extendTrial(Request $request, int $id): JsonResponse
    {
        $company = Company::with('subscription')->findOrFail($id);

        $validated = $request->validate([
            'trial_ends_on' => ['required', 'date', 'after_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'admin_user_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->checkoutService->recordTrialExtension($company, $validated, null);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $company = Company::with('subscription')->findOrFail($id);
        $subscription = $company->subscription ?? CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'trial',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
            'starts_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'date'],
            'trial_ends_on' => ['sometimes', 'nullable', 'date'],
            'included_branches' => ['sometimes', 'integer', 'min:1'],
            'users_per_branch' => ['sometimes', 'integer', 'min:1'],
            'included_lr_credits' => ['sometimes', 'integer', 'min:0'],
            'included_trucks' => ['sometimes', 'integer', 'min:0'],
            'addon_branches' => ['sometimes', 'integer', 'min:0'],
            'addon_users' => ['sometimes', 'integer', 'min:0'],
            'addon_trucks' => ['sometimes', 'integer', 'min:0'],
            'addon_lr_credits' => ['sometimes', 'integer', 'min:0'],
            'lr_credits_remaining' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $notes = $validated['notes'] ?? null;
        unset($validated['notes']);

        $changes = $this->changedSubscriptionAttributes($subscription, $validated);

        if ($changes === []) {
            return response()->json(['message' => 'No changes to save.'], 422);
        }

        $subscription->update($changes);

        SubscriptionEvent::create([
            'company_id' => $company->id,
            'event_type' => 'admin_override',
            'metadata' => array_filter([
                'changes' => $changes,
                'notes' => $notes,
            ]),
        ]);

        return response()->json(['subscription' => $subscription->fresh()]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function changedSubscriptionAttributes(CompanySubscription $subscription, array $validated): array
    {
        $changes = [];

        foreach ($validated as $key => $value) {
            $current = $subscription->{$key};

            if ($current instanceof \Carbon\CarbonInterface) {
                $current = $current->toDateString();
            }

            if ($value === null && $current === null) {
                continue;
            }

            if ((string) $current !== (string) $value) {
                $changes[$key] = $value;
            }
        }

        return $changes;
    }

    /** @return array<string, mixed> */
    private function purchaseRules(): array
    {
        return [
            'purchase_type' => ['required', Rule::in(['renewal', 'addon'])],
            'addon_branches' => ['nullable', 'integer', 'min:0'],
            'addon_users' => ['nullable', 'integer', 'min:0'],
            'addon_trucks' => ['nullable', 'integer', 'min:0'],
            'addon_lr_credits' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Services\AdminApiClient;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionPricingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPricingService $pricingService,
        private readonly SubscriptionCheckoutService $checkoutService,
        private readonly AdminApiClient $adminApi,
        private readonly SubscriptionUsageService $usageService
    ) {}

    public function index(): Response
    {
        $this->assertSuperAdmin();

        $companies = Company::query()
            ->where('user_id', Auth::id())
            ->with('subscription')
            ->orderBy('name')
            ->get();

        $activeCount = $companies->filter(fn ($c) => $c->subscription?->isActive())->count();

        return Inertia::render('Subscription/Index', [
            'companies' => $companies,
            'summary' => [
                'active' => $activeCount,
                'expired' => $companies->count() - $activeCount,
                'total' => $companies->count(),
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->query('company_id'));

        return Inertia::render('Subscription/Show', $this->managePageProps($company));
    }

    public function checkoutForm(Request $request): Response|RedirectResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->query('company_id'));

        $subscription = $company->subscription;
        $isExpired = ! $subscription?->isActive();

        $purchaseType = $request->query('purchase_type', 'renewal');
        if (! in_array($purchaseType, ['renewal', 'addon'], true)) {
            $purchaseType = 'renewal';
        }

        if ($purchaseType === 'addon' && $isExpired) {
            return redirect()
                ->route('subscription.show', ['company_id' => $company->id])
                ->with('error', 'Add-ons are only available for active subscriptions.');
        }

        try {
            $catalog = $this->adminApi->getCatalog();
        } catch (SubscriptionCatalogUnavailableException) {
            return redirect()
                ->route('subscription.show', ['company_id' => $company->id])
                ->with('error', 'Unable to load pricing. Please try again later.');
        }

        $usage = null;
        $minimumAddons = [];
        if ($purchaseType === 'renewal') {
            $usage = $this->usageService->usageForCompany($company);
            $minimumAddons = $this->pricingService->minimumRenewalAddons($company, $catalog['catalog']);
        }

        $remainingDays = $purchaseType === 'addon' ? ($subscription?->remainingDaysInclusive() ?? 0) : null;

        return Inertia::render('Subscription/Checkout', [
            'company' => $company->only(['id', 'name']),
            'subscription' => $subscription,
            'isExpired' => $isExpired,
            'catalog' => $catalog,
            'purchaseType' => $purchaseType,
            'usage' => $usage,
            'minimumAddons' => $minimumAddons,
            'remainingDays' => $remainingDays,
            'productName' => 'TruckBill Parchun',
            'includeTrucks' => false,
        ]);
    }

    public function history(Request $request): Response
    {
        $this->assertSuperAdmin();
        $companyId = (int) $request->query('company_id');
        $payments = SubscriptionPayment::query()
            ->with('company:id,name')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('company', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Subscription/History', [
            'payments' => $payments,
            'companyId' => $companyId,
            'totalPaid' => (float) $payments->sum('total_amount'),
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->input('company_id'));

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'purchase_type' => ['required', 'in:renewal,addon'],
            'addon_branches' => ['nullable', 'integer', 'min:0'],
            'addon_users' => ['nullable', 'integer', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'promo_validated' => ['nullable', 'boolean'],
        ]);

        $validated['addon_trucks'] = 0;
        $validated['addon_lr_credits'] = 0;

        try {
            return response()->json($this->pricingService->quote($company, $validated));
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'blocking_errors' => [$exception->getMessage()],
                'can_checkout' => false,
            ], 503);
        }
    }

    public function validatePromo(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $result = $this->adminApi->validatePromo($validated['code']);
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json([
                'valid' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json($result, ($result['valid'] ?? false) ? 200 : 422);
    }

    public function checkout(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->input('company_id'));

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'purchase_type' => ['required', 'in:renewal,addon'],
            'addon_branches' => ['nullable', 'integer', 'min:0'],
            'addon_users' => ['nullable', 'integer', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'promo_validated' => ['nullable', 'boolean'],
        ]);

        $validated['addon_trucks'] = 0;
        $validated['addon_lr_credits'] = 0;

        try {
            $order = $this->checkoutService->createCheckoutOrder($company, $validated, Auth::id());
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($order);
    }

    public function callback(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = $this->checkoutService->handleCallback($validated, Auth::id());

        return response()->json(['success' => true, 'payment' => $payment]);
    }

    private function managePageProps(Company $company): array
    {
        $subscription = $company->subscription;
        $isExpired = ! $subscription?->isActive();
        $catalog = $this->loadCatalogOrNull();
        $catalogData = $catalog['catalog'] ?? [];
        $usage = $this->usageService->usageForCompany($company);

        $maxBranches = $subscription?->maxBranches() ?? 0;
        $maxUsersPerBranch = $subscription?->maxUsersInBranch() ?? ($catalogData['users_per_branch'] ?? 0);
        $maxUsersInBusiestBranch = $maxUsersPerBranch + (int) ($subscription?->addon_users ?? 0);
        $maxCompanyUsers = $subscription?->maxCompanyUsers() ?? 0;

        $payments = SubscriptionPayment::query()
            ->where('company_id', $company->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $upcoming = UpcomingSubscription::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        return [
            'company' => $company->only(['id', 'name']),
            'subscription' => $subscription,
            'isExpired' => $isExpired,
            'catalogAvailable' => $catalog !== null,
            'productName' => 'TruckBill Parchun',
            'plan' => [
                'included_branches' => $subscription?->included_branches ?? 0,
                'addon_branches' => $subscription?->addon_branches ?? 0,
                'users_per_branch' => $subscription?->users_per_branch ?? ($catalogData['users_per_branch'] ?? 0),
                'addon_users' => $subscription?->addon_users ?? 0,
                'starts_on' => $subscription?->starts_on?->toDateString(),
                'ends_on' => $subscription?->ends_on?->toDateString(),
            ],
            'usageMetrics' => [
                [
                    'label' => 'Branches',
                    'used' => $usage['branches'],
                    'max' => $maxBranches,
                    'percent' => $this->usagePercent($usage['branches'], $maxBranches),
                    'invert' => false,
                ],
                [
                    'label' => 'Users in busiest branch',
                    'used' => $usage['max_users_in_any_branch'],
                    'max' => $maxUsersInBusiestBranch,
                    'percent' => $this->usagePercent($usage['max_users_in_any_branch'], $maxUsersInBusiestBranch),
                    'invert' => false,
                ],
                [
                    'label' => 'Total company users',
                    'used' => $usage['total_users'],
                    'max' => $maxCompanyUsers,
                    'percent' => $this->usagePercent($usage['total_users'], $maxCompanyUsers),
                    'invert' => false,
                ],
            ],
            'payments' => $payments,
            'upcoming' => $upcoming ? [
                'branches' => $upcoming->included_branches + $upcoming->addon_branches,
                'addon_users' => $upcoming->addon_users,
            ] : null,
        ];
    }

    private function usagePercent(int|float $used, int|float $max): int
    {
        if ($max <= 0) {
            return 0;
        }

        return (int) min(100, round(($used / $max) * 100));
    }

    private function assertSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403);
        }
    }

    private function resolveCompany(int $companyId): Company
    {
        $company = Company::with('subscription')->findOrFail($companyId);
        if ($company->user_id !== Auth::id()) {
            abort(403);
        }

        return $company;
    }

    private function loadCatalogOrNull(): ?array
    {
        try {
            return $this->adminApi->getCatalog();
        } catch (SubscriptionCatalogUnavailableException) {
            return null;
        }
    }
}

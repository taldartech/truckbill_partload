<?php

namespace Tests\Feature\Models;

use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Company;
use App\Models\CompanyStationAgent;
use App\Models\CompanySubscription;
use App\Models\DeliveryReceipt;
use App\Models\Station;
use App\Models\SubscriptionEvent;
use App\Models\UserOnboarding;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ModelBehaviourTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function relationProvider(): array
    {
        $map = [
            Account::class => ['company', 'bankAccount'],
            AccountingMapping::class => ['company'],
            AccountingSourceLink::class => ['company', 'branch', 'source', 'voucher', 'postedBy'],
            AccountingVoucher::class => ['company', 'branch', 'source'],
            Bilty::class => ['challanDetails', 'invoiceDetails', 'bankAccount', 'advanceBankAccount'],
            BranchSetting::class => ['branch'],
            Company::class => ['users', 'owner', 'branches', 'pendingUpcomingRenewals'],
            CompanyStationAgent::class => ['company', 'station'],
            CompanySubscription::class => ['company', 'upcoming'],
            DeliveryReceipt::class => ['company', 'bankAccount'],
            Station::class => ['owner', 'companyDefaultAgents'],
            SubscriptionEvent::class => ['company', 'payment'],
            UserOnboarding::class => ['user'],
            Voucher::class => ['company', 'bankAccount', 'accountable'],
        ];

        $cases = [];
        foreach ($map as $class => $methods) {
            foreach ($methods as $method) {
                $cases[class_basename($class).'::'.$method] = [$class, $method];
            }
        }

        return $cases;
    }

    /**
     * @param  class-string  $class
     */
    #[DataProvider('relationProvider')]
    public function test_relationship_methods_return_relations(string $class, string $method): void
    {
        $this->assertInstanceOf(Relation::class, (new $class)->{$method}());
    }

    public function test_account_nature_and_active_scope(): void
    {
        $this->assertNull((new Account)->nature());
        $this->assertStringContainsString('is_active', Account::query()->active()->toSql());
    }

    public function test_branch_document_initials_and_logo_resolution(): void
    {
        $context = $this->createTenantContext();
        $branch = $context['branch'];

        $branch->settings()->updateOrCreate([], [
            'challan_initial' => 'CH',
            'main_challan_initial' => 'MC',
            'invoice_initial' => 'IN',
            'inward_register_initial' => 'IR',
        ]);
        $branch = Branch::query()->find($branch->id);

        $this->assertSame('CH', $branch->challan_initial);
        $this->assertSame('MC', $branch->main_challan_initial);
        $this->assertSame('IN', $branch->invoice_initial);
        $this->assertSame('IR', $branch->inward_register_initial);

        $context['company']->update(['company_logo' => 'logos/company.png']);
        $branch->forceFill(['use_company_logo' => true, 'branch_logo' => 'logos/branch.png']);

        $this->assertSame('logos/company.png', $branch->resolvedLogo());

        $branch->setRelation('company', $context['company']->fresh());
        $this->assertSame('logos/company.png', $branch->resolvedLogo());

        $branch->use_company_logo = false;
        $this->assertSame('logos/branch.png', $branch->resolvedLogo());
    }

    public function test_branch_setting_resolves_legacy_copy_note(): void
    {
        $method = (new ReflectionClass(BranchSetting::class))->getMethod('resolveBiltyPrintNote');

        $note = $method->invoke(null, ['copies' => [
            'bad' => 'not-an-array',
            'blank' => ['note' => '   '],
            'legacy' => ['note' => ' Legacy note '],
        ]]);

        $this->assertSame('Legacy note', $note);
        $this->assertSame('', $method->invoke(null, ['copies' => ['x' => ['note' => '']]]));
    }

    public function test_company_subscription_helpers(): void
    {
        $context = $this->createTenantContext();
        $company = $context['company'];

        $this->assertFalse($company->hasPendingUpcomingRenewal());
        $this->assertTrue($company->isPlanActive());
        $this->assertTrue($company->subscriptionCanWrite());
        $this->assertTrue($company->subscriptionCanExport());
        $this->assertTrue($company->subscriptionCanPrint());

        $bare = Company::create(['name' => 'No Plan Co', 'user_id' => $context['owner']->id]);
        $this->assertFalse($bare->isPlanActive());
    }

    public function test_company_subscription_display_status_in_grace_period(): void
    {
        Cache::put('subscription.settings', ['grace_period_days' => 10, 'gst_rate_percent' => 18.0], now()->addHour());

        $context = $this->createTenantContext([], 'G', 'active', now()->subDay()->toDateString());

        $this->assertSame('grace', $context['subscription']->fresh()->displayStatus());

        Cache::put('subscription.settings', ['grace_period_days' => 0, 'gst_rate_percent' => 18.0], now()->addHour());
        $this->assertSame('expired', $context['subscription']->fresh()->displayStatus());
    }

    public function test_user_onboarding_state_helpers(): void
    {
        $onboarding = new UserOnboarding([
            'otp_hash' => 'hash',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue($onboarding->hasActiveOtp());
        $this->assertFalse($onboarding->isCompleted());

        $onboarding->otp_expires_at = now()->subMinute();
        $onboarding->completed_at = now();

        $this->assertFalse($onboarding->hasActiveOtp());
        $this->assertTrue($onboarding->isCompleted());
    }
}

<?php

namespace Tests\Feature\Models;

use App\Enums\UserRole;
use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherEntry;
use App\Models\Agent;
use App\Models\Bilty;
use App\Models\BiltyEwayBill;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\MainChallanDetail;
use App\Models\OnboardingProvisioningLog;
use App\Models\Station;
use App\Models\SubscriptionPayment;
use App\Models\Truck;
use App\Models\UpcomingSubscription;
use App\Models\User;
use App\Models\VoucherSequence;
use App\Support\GstCalculator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class RelationshipCoverageTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_model_relationships_resolve_to_expected_related_models(): void
    {
        $expectations = [
            [new AccountGroup, 'company', Company::class],
            [new AccountingVoucherEntry, 'voucher', AccountingVoucher::class],
            [new Agent, 'trucks', Truck::class],
            [new BiltyEwayBill, 'bilty', Bilty::class],
            [new Challan, 'mainChallanDetails', MainChallanDetail::class],
            [new FinancialYear, 'company', Company::class],
            [new Invoice, 'company', Company::class],
            [new InvoiceDetail, 'invoice', Invoice::class],
            [new InwardRegister, 'company', Company::class],
            [new InwardRegisterDetail, 'challanDetail', ChallanDetail::class],
            [new OnboardingProvisioningLog, 'user', User::class],
            [new SubscriptionPayment, 'upcomingSubscription', UpcomingSubscription::class],
            [new UpcomingSubscription, 'company', Company::class],
            [new UpcomingSubscription, 'payment', SubscriptionPayment::class],
            [new VoucherSequence, 'company', Company::class],
            [new VoucherSequence, 'financialYear', FinancialYear::class],
        ];

        foreach ($expectations as [$model, $relation, $related]) {
            $this->assertInstanceOf(Relation::class, $model->{$relation}());
            $this->assertInstanceOf($related, $model->{$relation}()->getRelated(), $model::class.'::'.$relation);
        }
    }

    public function test_user_branch_assignment_requirement_follows_role(): void
    {
        $context = $this->createTenantContext();
        $this->giveRole($context['user'], UserRole::Admin->value);

        $this->assertTrue($context['user']->requiresBranchAssignment());
        $this->assertFalse($context['owner']->requiresBranchAssignment());
    }

    public function test_user_role_system_roles_lists_all_roles(): void
    {
        $this->assertSame(UserRole::all(), UserRole::systemRoles());
    }

    public function test_gst_split_uses_igst_for_inter_state_supply(): void
    {
        $this->assertEquals(
            ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 18.0],
            GstCalculator::split(18.0, 1, 2)
        );
    }

    public function test_owner_scope_is_skipped_for_guests_and_blocks_users_without_resolvable_owner(): void
    {
        $context = $this->createTenantContext();

        Auth::logout();
        $this->assertGreaterThanOrEqual(1, Station::query()->count());

        $context['company']->delete();
        $this->actingAs($context['user']->fresh());

        $this->assertSame(0, Station::query()->count());
    }
}

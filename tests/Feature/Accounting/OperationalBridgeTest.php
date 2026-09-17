<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingVoucher;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_posts_when_party_accounting_enabled(): void
    {
        [$company, $branch, $party] = $this->context();
        app(EnsureCompanyAccounting::class)->handle($company);

        $party->update(['consider_for_accounting' => true]);
        app(EntityAccountLinker::class)->enable($party);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'invoice_no' => 'INV-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'net_amount' => 1500,
        ]);

        $voucher = app(OperationalAccountingBridge::class)->sync($invoice);
        $this->assertNotNull($voucher);
        $this->assertSame(2, $voucher->entries()->count());
        $this->assertEquals(1500.0, (float) $voucher->entries->sum('debit'));
        $this->assertEquals(1500.0, (float) $voucher->entries->sum('credit'));
    }

    public function test_invoice_skips_when_party_accounting_disabled(): void
    {
        [$company, $branch, $party] = $this->context();
        app(EnsureCompanyAccounting::class)->handle($company);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'invoice_no' => 'INV-2',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'net_amount' => 1500,
        ]);

        $voucher = app(OperationalAccountingBridge::class)->sync($invoice);
        $this->assertNull($voucher);
        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    /**
     * @return array{0: Company, 1: Branch, 2: Party}
     */
    private function context(): array
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Bridge Co', 'user_id' => $owner->id]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'RJ', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);
        $station = Station::create(['name' => 'UDR', 'owner_id' => $owner->id]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main',
            'address' => '1 St',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'a@example.com',
        ]);
        $party = Party::create(['company_id' => $company->id, 'name' => 'Party']);

        return [$company, $branch, $party];
    }
}

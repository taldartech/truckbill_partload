<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Services\Accounting\AccountingPoster;
use App\Services\Accounting\EnsureCompanyLedgerAccounts;
use App\Services\Accounting\EntityBalance;
use App\Services\Accounting\LedgerAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_account_crud_creates_ledger_account(): void
    {
        $context = $this->createAccountingContext(['create bank_accounts', 'view bank_accounts', 'edit bank_accounts', 'delete bank_accounts']);
        $user = $context['user'];

        $response = $this->actingAs($user)->post(route('bank-accounts.store'), [
            'name' => 'HDFC Current',
            'bank_name' => 'HDFC',
            'account_number' => '123456',
            'ifsc_code' => 'HDFC0001',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_accounts', [
            'company_id' => $context['company']->id,
            'name' => 'HDFC Current',
        ]);

        $bank = BankAccount::query()->first();
        $this->assertNotNull($bank);

        $ledger = app(LedgerAccountResolver::class)->forBankAccount($bank);
        $this->assertSame('bank', $ledger->kind);
        $this->assertSame($bank->id, $ledger->bank_account_id);
    }

    public function test_receipt_voucher_posts_balanced_cash_pair(): void
    {
        $context = $this->createAccountingContext(['create vouchers', 'view vouchers']);
        $party = $context['party'];

        $response = $this->actingAs($context['user'])->post(route('vouchers.store'), [
            'date' => now()->toDateString(),
            'voucher_number' => 'R-1',
            'voucher_type' => 'Receipt',
            'accountable_type' => 'party',
            'accountable_id' => $party->id,
            'amount' => 1000,
            'mode' => 'cash',
            'remark' => 'Receipt test',
        ]);

        $response->assertRedirect(route('vouchers.index'));

        $voucher = Voucher::query()->first();
        $this->assertNotNull($voucher);
        $this->assertSame(2, LedgerEntry::query()->where('reference_type', Voucher::class)->where('reference_id', $voucher->id)->count());

        $entries = LedgerEntry::query()->where('reference_id', $voucher->id)->get();
        $this->assertEquals(1000.0, (float) $entries->sum('debit'));
        $this->assertEquals(1000.0, (float) $entries->sum('credit'));
    }

    public function test_payment_voucher_with_bank_and_exceed_balance_still_saves(): void
    {
        $context = $this->createAccountingContext(['create vouchers', 'view vouchers']);
        $vendor = $context['vendor'];
        $bank = BankAccount::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'name' => 'SBI',
            'is_active' => true,
        ]);

        $response = $this->actingAs($context['user'])->post(route('vouchers.store'), [
            'date' => now()->toDateString(),
            'voucher_type' => 'Payment',
            'accountable_type' => 'vendor',
            'accountable_id' => $vendor->id,
            'amount' => 5000,
            'mode' => 'bank',
            'bank_account_id' => $bank->id,
            'remark' => 'Advance payment',
        ]);

        $response->assertRedirect(route('vouchers.index'));
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('vouchers', [
            'voucher_type' => 'Payment',
            'amount' => 5000,
            'mode' => 'bank',
            'bank_account_id' => $bank->id,
        ]);
    }

    public function test_bilty_due_cartage_posts_and_cash_does_not(): void
    {
        $context = $this->createAccountingContext();
        $poster = app(AccountingPoster::class);
        $stationId = $context['station']->id;
        $partyId = $context['party']->id;

        $due = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'consignor_id' => $partyId,
            'consignee_id' => $partyId,
            'from_station_id' => $stationId,
            'to_station_id' => $stationId,
            'bilty_number' => 'BLT-DUE',
            'date' => now()->toDateString(),
            'vendor_id' => $context['vendor']->id,
            'cartage_type' => 'Due',
            'cartage' => 250,
            'status' => 'Pending',
        ]);
        $poster->syncBilty($due);
        $this->assertSame(2, LedgerEntry::query()->where('reference_type', Bilty::class)->where('reference_id', $due->id)->count());

        $cash = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'consignor_id' => $partyId,
            'consignee_id' => $partyId,
            'from_station_id' => $stationId,
            'to_station_id' => $stationId,
            'bilty_number' => 'BLT-CASH',
            'date' => now()->toDateString(),
            'vendor_id' => $context['vendor']->id,
            'cartage_type' => 'Cash',
            'cartage' => 250,
            'status' => 'Pending',
        ]);
        $poster->syncBilty($cash);
        $this->assertSame(0, LedgerEntry::query()->where('reference_type', Bilty::class)->where('reference_id', $cash->id)->count());
    }

    public function test_invoice_posts_party_receivable(): void
    {
        $context = $this->createAccountingContext();
        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-1',
            'date' => now()->toDateString(),
            'party_id' => $context['party']->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'gst_amount' => 0,
            'net_amount' => 1000,
        ]);

        app(AccountingPoster::class)->syncInvoice($invoice);

        $partyAccount = app(LedgerAccountResolver::class)->forEntity($context['party']);
        $debit = LedgerEntry::query()
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('ledger_account_id', $partyAccount->id)
            ->where('debit', 1000)
            ->exists();

        $this->assertTrue($debit);
        $this->assertEquals(1000.0, app(EntityBalance::class)->outstanding($context['party']));
    }

    public function test_inward_and_challan_post_independently(): void
    {
        $context = $this->createAccountingContext();
        $poster = app(AccountingPoster::class);
        $stationId = $context['station']->id;

        $inward = InwardRegister::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_no' => 'IR-1',
            'date' => now()->toDateString(),
            'from_station_id' => $stationId,
            'to_station_id' => $stationId,
        ]);
        InwardRegisterDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_id' => $inward->id,
            'agent_id' => $context['agent']->id,
            'freight' => 400,
        ]);
        $poster->syncInwardRegister($inward->fresh());
        $this->assertSame(2, LedgerEntry::query()->where('reference_type', InwardRegister::class)->where('reference_id', $inward->id)->count());

        $truck = \App\Models\Truck::create([
            'company_id' => $context['company']->id,
            'truck_number' => 'RJ14AB1234',
        ]);

        $challan = Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'from_station_id' => $stationId,
            'to_station_id' => $stationId,
            'truck_id' => $truck->id,
            'driver_name' => 'Driver',
            'challan_number' => 'CH-1',
            'date' => now()->toDateString(),
            'pay_by_agent_id' => $context['agent']->id,
            'total_amount' => 300,
        ]);
        $poster->syncChallan($challan);
        $this->assertSame(2, LedgerEntry::query()->where('reference_type', Challan::class)->where('reference_id', $challan->id)->count());
    }

    public function test_update_and_delete_reverse_and_repost(): void
    {
        $context = $this->createAccountingContext();
        $poster = app(AccountingPoster::class);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-2',
            'date' => now()->toDateString(),
            'party_id' => $context['party']->id,
            'status' => 'Pending',
            'net_amount' => 500,
        ]);
        $poster->syncInvoice($invoice);
        $this->assertSame(2, LedgerEntry::query()->where('reference_id', $invoice->id)->where('reference_type', Invoice::class)->count());

        $invoice->update(['net_amount' => 750]);
        $poster->syncInvoice($invoice->fresh());
        $this->assertSame(2, LedgerEntry::query()->where('reference_id', $invoice->id)->where('reference_type', Invoice::class)->count());
        $this->assertTrue(
            LedgerEntry::query()
                ->where('reference_id', $invoice->id)
                ->where('reference_type', Invoice::class)
                ->where('debit', 750)
                ->exists()
        );

        $poster->clearReference($invoice);
        $invoice->delete();
        $this->assertSame(0, LedgerEntry::query()->where('reference_id', $invoice->id)->where('reference_type', Invoice::class)->count());
    }

    public function test_invoice_payment_routes_are_removed(): void
    {
        $context = $this->createAccountingContext(['view invoices', 'edit invoices']);
        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-3',
            'date' => now()->toDateString(),
            'party_id' => $context['party']->id,
            'status' => 'Pending',
            'net_amount' => 100,
        ]);

        $this->actingAs($context['user'])
            ->get("/invoices/{$invoice->id}/payments")
            ->assertNotFound();

        $this->actingAs($context['user'])
            ->post("/invoices/{$invoice->id}/payments", [
                'date' => now()->toDateString(),
                'amount' => 50,
                'payment_mode' => 'Cash',
            ])
            ->assertNotFound();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, company: Company, branch: Branch, party: Party, vendor: Vendor, agent: Agent, station: Station}
     */
    private function createAccountingContext(array $permissions = []): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Accounting Co', 'user_id' => $owner->id]);
        app(EnsureCompanyLedgerAccounts::class)->handle($company);

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
            'name' => 'Accounting Branch',
            'address' => '1 Ledger Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'Accountant',
            'contact_number' => '9000000000',
            'email' => 'accounts@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $party = Party::create([
            'company_id' => $company->id,
            'name' => 'Test Party',
        ]);
        $vendor = Vendor::create([
            'company_id' => $company->id,
            'name' => 'Test Vendor',
        ]);
        $agent = Agent::create([
            'company_id' => $company->id,
            'name' => 'Test Agent',
        ]);

        return compact('user', 'company', 'branch', 'party', 'vendor', 'agent', 'station');
    }
}

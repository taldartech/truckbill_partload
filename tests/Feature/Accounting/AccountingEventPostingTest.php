<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\Agent;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Good;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Models\Truck;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\VoucherService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingEventPostingTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_paid_bilty_posts_receipt_advance_and_cash_cartage(): void
    {
        $context = $this->accountingContext();
        $bilty = $this->paidBilty($context);
        $bridge = app(OperationalAccountingBridge::class);

        $this->assertSame(3, $bridge->sync($bilty));

        $vouchers = $bridge->activeVouchers($bilty)->keyBy('event_key');
        $this->assertSame(['bilty.paid_receipt', 'bilty.advance_receipt', 'bilty.cartage_cash'], $vouchers->keys()->all());

        $receipt = $vouchers['bilty.paid_receipt'];
        $this->assertSame(AccountingVoucherType::Receipt, $receipt->voucher_type);
        $this->assertNet($receipt, $this->systemAccountId($context['company'], 'cash'), 1007.50);
        $this->assertNet($receipt, $this->partyAccountId($bilty->consignor_id), 200);
        $this->assertNet($receipt, $this->systemAccountId($context['company'], 'freight_income'), -1000);
        $this->assertNet($receipt, $this->systemAccountId($context['company'], 'bilty_charges_income'), -150);
        $this->assertNet($receipt, $this->systemAccountId($context['company'], 'gst_output_cgst'), -28.75);
        $this->assertNet($receipt, $this->systemAccountId($context['company'], 'gst_output_sgst'), -28.75);

        $this->assertNet($vouchers['bilty.advance_receipt'], $this->partyAccountId($bilty->consignor_id), -200);
        $this->assertNet($vouchers['bilty.cartage_cash'], $this->systemAccountId($context['company'], 'cartage_expense'), 100);
    }

    public function test_bilty_edit_reposts_only_the_changed_event(): void
    {
        $context = $this->accountingContext();
        $bilty = $this->paidBilty($context);
        $bridge = app(OperationalAccountingBridge::class);
        $bridge->sync($bilty);
        $before = $bridge->activeVouchers($bilty)->keyBy('event_key');

        $this->assertSame(0, $bridge->sync($bilty));

        $bilty->update(['freight' => 1200]);
        $this->assertSame(1, $bridge->sync($bilty));

        $after = $bridge->activeVouchers($bilty)->keyBy('event_key');
        $this->assertNotSame($before['bilty.paid_receipt']->id, $after['bilty.paid_receipt']->id);
        $this->assertSame($before['bilty.advance_receipt']->id, $after['bilty.advance_receipt']->id);
        $this->assertSame($before['bilty.cartage_cash']->id, $after['bilty.cartage_cash']->id);

        $reversal = $before['bilty.paid_receipt']->fresh()->reversals()->sole();
        $this->assertSame($before['bilty.paid_receipt']->date->toDateString(), $reversal->date->toDateString());
    }

    public function test_cancelled_or_deleted_bilty_reverses_all_postings(): void
    {
        $context = $this->accountingContext();
        $bridge = app(OperationalAccountingBridge::class);

        $cancelled = $this->paidBilty($context, 'BLT-C');
        $bridge->sync($cancelled);
        $cancelled->update(['status' => 'Cancelled']);
        $this->assertSame(3, $bridge->sync($cancelled));
        $this->assertCount(0, $bridge->activeVouchers($cancelled));

        $deleted = $this->paidBilty($context, 'BLT-D');
        $bridge->sync($deleted);
        $this->assertSame(3, $bridge->clear($deleted));
        $deleted->delete();
        $this->assertSame(0, $bridge->sync($deleted));
    }

    public function test_to_pay_bilty_and_paid_bilty_without_mode_do_not_post_receipts(): void
    {
        $context = $this->accountingContext();
        $bridge = app(OperationalAccountingBridge::class);

        $toPay = $this->paidBilty($context, 'BLT-TP', ['payment_type' => 'To Pay', 'payment_mode' => null, 'less_advance_received' => 0, 'cartage' => 0]);
        $this->assertSame(0, $bridge->sync($toPay));

        $noMode = $this->paidBilty($context, 'BLT-NM', ['payment_mode' => null, 'less_advance_received' => 0, 'cartage' => 0]);
        $this->assertSame(0, $bridge->sync($noMode));
    }

    public function test_bilty_for_party_without_accounting_uses_unlinked_debtors(): void
    {
        $context = $this->accountingContext();
        $bilty = $this->paidBilty($context, 'BLT-UL', ['cartage' => 0], linkParties: false);

        app(OperationalAccountingBridge::class)->sync($bilty);
        $advance = app(OperationalAccountingBridge::class)->activeVouchers($bilty)->firstWhere('event_key', 'bilty.advance_receipt');

        $this->assertNet($advance, $this->systemAccountId($context['company'], 'sundry_debtors_unlinked'), -200);
    }

    public function test_inward_posts_crossing_only_for_forward_rows(): void
    {
        $context = $this->accountingContext();
        $agent = $this->agent($context);
        $register = InwardRegister::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_no' => 'IR-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
        ]);
        $this->inwardDetail($context, $register, $agent, 'forward', 400, 'GR-F');
        $this->inwardDetail($context, $register, $agent, 'delivery', 900, 'GR-D');

        $bridge = app(OperationalAccountingBridge::class);
        $this->assertSame(1, $bridge->sync($register));

        $voucher = $bridge->activeVouchers($register)->sole();
        $this->assertNet($voucher, $this->systemAccountId($context['company'], 'agent_crossing_expense'), 400);
        $this->assertNet($voucher, app(EntityAccountLinker::class)->linkedAccount($agent)->id, -400);
    }

    public function test_challan_settlement_is_itemised_and_nets_to_total(): void
    {
        $context = $this->accountingContext();
        $agent = $this->agent($context);
        $challan = Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => 'CH-EV-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'truck_id' => Truck::create(['company_id' => $context['company']->id, 'truck_number' => 'RJ27EV1'])->id,
            'driver_name' => 'Driver',
            'status' => 'Created',
            'pay_by_agent_id' => $agent->id,
            'to_pay_amount' => 3000,
            'crossing_freight' => 500,
            'labour_charge' => 100,
            'truck_freight' => 2000,
            'cash_received' => 300,
            'cash_payment' => 50,
            'total_amount' => 1150,
        ]);

        $bridge = app(OperationalAccountingBridge::class);
        $bridge->sync($challan);
        $voucher = $bridge->activeVouchers($challan)->sole();
        $agentAccountId = app(EntityAccountLinker::class)->linkedAccount($agent)->id;

        $this->assertSame('challan.agent_settlement', $voucher->event_key);
        $this->assertNet($voucher, $agentAccountId, 1150);
        $this->assertNet($voucher, $this->systemAccountId($context['company'], 'freight_income'), -3500);
        $this->assertNet($voucher, $this->systemAccountId($context['company'], 'labour_expense'), 100);
        $this->assertNet($voucher, $this->systemAccountId($context['company'], 'truck_hire_expense'), 2000);
        $this->assertNet($voucher, $this->systemAccountId($context['company'], 'cash'), 250);
    }

    public function test_posting_into_closed_year_raises_validation_error(): void
    {
        $context = $this->accountingContext();
        $invoice = $this->invoice($context, 'INV-FY');
        $bridge = app(OperationalAccountingBridge::class);
        $bridge->sync($invoice);

        $years = app(FinancialYearService::class);
        $years->close($years->forDate($context['company']->id, now()));

        $invoice->update(['net_amount' => 1500, 'total_amount' => 1500]);

        try {
            $bridge->sync($invoice);
            $this->fail('Expected a validation error for the closed financial year.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('financial_year_id', $e->errors());
        }

        $this->assertCount(1, $bridge->activeVouchers($invoice));
        $this->assertTrue($bridge->activeVouchers($invoice)->sole()->isOpenForReversal());
    }

    public function test_documents_post_only_to_their_own_company_ledgers(): void
    {
        $first = $this->accountingContext([], 'A');
        $second = $this->accountingContext([], 'B');
        $bridge = app(OperationalAccountingBridge::class);

        $bridge->sync($this->invoice($first, 'INV-ISO-A'));
        $bridge->sync($this->invoice($second, 'INV-ISO-B'));

        AccountingVoucher::query()->withoutGlobalScopes()->with(['entries.account' => fn ($q) => $q->withoutGlobalScopes()])->get()
            ->each(function (AccountingVoucher $voucher) {
                $voucher->entries->each(fn ($entry) => $this->assertSame($voucher->company_id, $entry->account->company_id));
            });

        $this->assertSame(1, AccountingVoucher::query()->withoutGlobalScopes()->where('company_id', $first['company']->id)->count());
        $this->assertSame(1, AccountingVoucher::query()->withoutGlobalScopes()->where('company_id', $second['company']->id)->count());
    }

    public function test_source_link_is_unique_per_document_and_event(): void
    {
        $context = $this->accountingContext();
        $invoice = $this->invoice($context, 'INV-LINK');
        app(OperationalAccountingBridge::class)->sync($invoice);

        $this->expectException(QueryException::class);

        AccountingSourceLink::query()->withoutGlobalScopes()->create([
            'company_id' => $context['company']->id,
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
            'event_key' => 'invoice.freight',
        ]);
    }

    public function test_balance_check_uses_exact_paise(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $vouchers = app(VoucherService::class);
        $cash = $this->systemAccountId($companyId, 'cash');
        $expense = $this->systemAccountId($companyId, 'general_expense');

        $voucher = $vouchers->post(['company_id' => $companyId, 'voucher_type' => AccountingVoucherType::Payment, 'date' => now()->toDateString()], [
            ['account_id' => $expense, 'debit' => 0.1],
            ['account_id' => $expense, 'debit' => 0.2],
            ['account_id' => $cash, 'credit' => 0.3],
        ]);
        $this->assertEquals(0.3, $voucher->totalCredit());

        $this->expectException(ValidationException::class);
        $vouchers->post(['company_id' => $companyId, 'voucher_type' => AccountingVoucherType::Payment, 'date' => now()->toDateString()], [
            ['account_id' => $expense, 'debit' => 33.33],
            ['account_id' => $expense, 'debit' => 33.33],
            ['account_id' => $expense, 'debit' => 33.33],
            ['account_id' => $cash, 'credit' => 100],
        ]);
    }

    /**
     * Freight 1,000 + bilty charge 50 + cash cartage 100, 5% GST by transporter, 200 advance in cash.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     */
    private function paidBilty(array $context, string $number = 'BLT-1', array $overrides = [], bool $linkParties = true): Bilty
    {
        return Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $this->party($context, 'Consignor '.$number, $linkParties)->id,
            'consignee_id' => $this->party($context, 'Consignee '.$number, $linkParties)->id,
            'payment_type' => 'Paid',
            'payment_mode' => 'Cash',
            'freight' => 1000,
            'bilty_charge' => 50,
            'cartage' => 100,
            'cartage_type' => 'Cash',
            'gst' => 5,
            'gst_liability_by' => 'Transporter',
            'less_advance_received' => 200,
            'advance_payment_mode' => 'Cash',
            'amount' => 1007.50,
            'status' => 'Pending',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function party(array $context, string $name, bool $linked = true): Party
    {
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000006',
            'consider_for_accounting' => $linked,
        ]);

        if ($linked) {
            app(EntityAccountLinker::class)->enable($party);
        }

        return $party;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function agent(array $context): Agent
    {
        $agent = Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Event Agent',
            'address' => 'Market',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000007',
            'consider_for_accounting' => true,
        ]);
        app(EntityAccountLinker::class)->enable($agent);

        return $agent;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function invoice(array $context, string $number): Invoice
    {
        return Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => $number,
            'date' => now()->toDateString(),
            'party_id' => $this->party($context, 'Invoice Party '.$number)->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function inwardDetail(array $context, InwardRegister $register, Agent $agent, string $disposition, float $freight, string $grNo): void
    {
        InwardRegisterDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_id' => $register->id,
            'gr_no' => $grNo,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignee_id' => $this->party($context, 'Inward Consignee '.$grNo)->id,
            'good_id' => Good::create(['owner_id' => $context['owner']->id, 'name' => 'Goods '.$grNo])->id,
            'agent_id' => $agent->id,
            'no_of_packages' => 1,
            'weight' => 10,
            'freight' => $freight,
            'disposition' => $disposition,
            'status' => 'pending',
            'basis_of_booking' => 'To Pay',
        ]);
    }

    private function partyAccountId(int $partyId): int
    {
        return app(EntityAccountLinker::class)->linkedAccount(Party::query()->withoutGlobalScopes()->findOrFail($partyId))->id;
    }

    private function assertNet(AccountingVoucher $voucher, int $accountId, float $expected): void
    {
        $lines = $voucher->entries->where('account_id', $accountId);
        $this->assertNotEmpty($lines, "Missing line for account {$accountId}.");
        $this->assertEquals($expected, round((float) $lines->sum('debit') - (float) $lines->sum('credit'), 2));
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\Account;
use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EnsureCompanyAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BackfillAccountsCommandTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_backfill_ensures_chart_of_accounts_and_financial_year_for_company(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;
        $this->assertSame(0, Account::query()->withoutGlobalScopes()->where('company_id', $companyId)->count());

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId])
            ->expectsOutputToContain("Company #{$companyId}")
            ->expectsOutputToContain('Ensured chart of accounts + financial year')
            ->assertSuccessful();

        $this->assertGreaterThan(0, Account::query()->withoutGlobalScopes()->where('company_id', $companyId)->count());
        $this->assertSame(1, FinancialYear::query()->withoutGlobalScopes()->where('company_id', $companyId)->where('is_current', true)->count());
    }

    public function test_backfill_is_idempotent(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId])->assertSuccessful();
        $accounts = Account::query()->withoutGlobalScopes()->where('company_id', $companyId)->count();

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId])->assertSuccessful();

        $this->assertSame($accounts, Account::query()->withoutGlobalScopes()->where('company_id', $companyId)->count());
    }

    public function test_backfill_works_for_company_that_already_has_accounting(): void
    {
        $context = $this->createTenantContext();
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $this->artisan('accounting:backfill-accounts', ['--company' => $context['company']->id])
            ->assertSuccessful();
    }

    public function test_backfill_without_company_option_processes_all_companies(): void
    {
        $first = $this->createTenantContext([], 'A');
        $second = $this->createTenantContext([], 'B');

        $this->artisan('accounting:backfill-accounts')
            ->expectsOutputToContain("Company #{$first['company']->id}")
            ->expectsOutputToContain("Company #{$second['company']->id}")
            ->assertSuccessful();

        foreach ([$first, $second] as $context) {
            $this->assertGreaterThan(0, Account::query()->withoutGlobalScopes()->where('company_id', $context['company']->id)->count());
        }
    }

    public function test_backfill_only_touches_the_requested_company(): void
    {
        $target = $this->createTenantContext([], 'A');
        $untouched = $this->createTenantContext([], 'B');

        $this->artisan('accounting:backfill-accounts', ['--company' => $target['company']->id])->assertSuccessful();

        $this->assertSame(0, Account::query()->withoutGlobalScopes()->where('company_id', $untouched['company']->id)->count());
    }

    public function test_backfill_fails_for_unknown_company(): void
    {
        $this->createTenantContext();

        $this->artisan('accounting:backfill-accounts', ['--company' => 999999])
            ->expectsOutputToContain('No companies found.')
            ->assertFailed();
    }

    public function test_backfill_fails_when_there_are_no_companies(): void
    {
        $this->artisan('accounting:backfill-accounts')
            ->expectsOutputToContain('No companies found.')
            ->assertFailed();
    }

    public function test_backfill_syncs_bank_accounts_and_flagged_parties_and_agents(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;

        $bank = BankAccount::create(['company_id' => $companyId, 'name' => 'HDFC Current', 'is_active' => true]);
        $flaggedParty = $this->makeParty($context, 'Flagged Party', true);
        $plainParty = $this->makeParty($context, 'Plain Party', false);
        $flaggedAgent = $this->makeAgent($context, 'Flagged Agent', true);
        $plainAgent = $this->makeAgent($context, 'Plain Agent', false);

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId])
            ->expectsOutputToContain('Synced bank accounts: 1')
            ->expectsOutputToContain('Parties enabled: 0, linked: 1')
            ->expectsOutputToContain('Agents enabled: 0, linked: 1')
            ->assertSuccessful();

        $this->assertTrue($this->hasLedger($companyId, BankAccount::class, $bank->id));
        $this->assertTrue($this->hasLedger($companyId, Party::class, $flaggedParty->id));
        $this->assertFalse($this->hasLedger($companyId, Party::class, $plainParty->id));
        $this->assertTrue($this->hasLedger($companyId, Agent::class, $flaggedAgent->id));
        $this->assertFalse($this->hasLedger($companyId, Agent::class, $plainAgent->id));
        $this->assertFalse($plainParty->fresh()->consider_for_accounting);
    }

    public function test_enable_all_flags_and_links_every_party_and_agent(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;
        $party = $this->makeParty($context, 'Plain Party', false);
        $agent = $this->makeAgent($context, 'Plain Agent', false);

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId, '--enable-all' => true])
            ->expectsOutputToContain('Parties enabled: 1, linked: 1')
            ->expectsOutputToContain('Agents enabled: 1, linked: 1')
            ->assertSuccessful();

        $this->assertTrue($party->fresh()->consider_for_accounting);
        $this->assertTrue($agent->fresh()->consider_for_accounting);
        $this->assertTrue($this->hasLedger($companyId, Party::class, $party->id));
        $this->assertTrue($this->hasLedger($companyId, Agent::class, $agent->id));
    }

    public function test_dry_run_reports_counts_without_writing(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;
        BankAccount::create(['company_id' => $companyId, 'name' => 'Preview Bank', 'is_active' => true]);
        $party = $this->makeParty($context, 'Plain Party', false);
        $agent = $this->makeAgent($context, 'Plain Agent', false);

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId, '--dry-run' => true, '--enable-all' => true])
            ->expectsOutputToContain('Dry run')
            ->expectsOutputToContain('Bank accounts to sync: 1')
            ->expectsOutputToContain('Parties flag enable: 1')
            ->expectsOutputToContain('Parties to link: 1')
            ->expectsOutputToContain('Agents flag enable: 1')
            ->expectsOutputToContain('Agents to link: 1')
            ->assertSuccessful();

        $this->assertSame(0, Account::query()->withoutGlobalScopes()->where('company_id', $companyId)->count());
        $this->assertFalse($party->fresh()->consider_for_accounting);
        $this->assertFalse($agent->fresh()->consider_for_accounting);
    }

    public function test_dry_run_without_enable_all_counts_only_flagged_entities(): void
    {
        $context = $this->createTenantContext();
        $this->makeParty($context, 'Flagged', true);
        $this->makeParty($context, 'Plain', false);

        $this->artisan('accounting:backfill-accounts', ['--company' => $context['company']->id, '--dry-run' => true])
            ->expectsOutputToContain('Parties flag enable: 0')
            ->expectsOutputToContain('Parties to link: 1')
            ->assertSuccessful();
    }

    public function test_with_vouchers_reconciles_operational_documents(): void
    {
        $context = $this->createTenantContext();
        $companyId = $context['company']->id;
        $party = $this->makeParty($context, 'Invoiced Party', true);

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId])->assertSuccessful();

        Invoice::create([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-BF-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'gst_amount' => 0,
            'net_amount' => 1000,
        ]);

        $this->artisan('accounting:backfill-accounts', ['--company' => $companyId, '--with-vouchers' => true])
            ->expectsOutputToContain('Operational vouchers synced:')
            ->assertSuccessful();
    }

    public function test_dry_run_with_vouchers_previews_reconciliation(): void
    {
        $context = $this->createTenantContext();

        $this->artisan('accounting:backfill-accounts', [
            '--company' => $context['company']->id,
            '--dry-run' => true,
            '--with-vouchers' => true,
        ])
            ->expectsOutputToContain('Operational events to post/repost/reverse/link:')
            ->assertSuccessful();
    }

    public function test_failure_for_one_company_is_reported_and_returns_failure_exit_code(): void
    {
        $context = $this->createTenantContext();

        $this->mock(EnsureCompanyAccounting::class, function ($mock): void {
            $mock->shouldReceive('handle')->andThrow(new RuntimeException('chart exploded'));
        });

        $this->artisan('accounting:backfill-accounts', ['--company' => $context['company']->id])
            ->expectsOutputToContain('Failed: chart exploded')
            ->assertFailed();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeParty(array $context, string $name, bool $considerForAccounting): Party
    {
        return Party::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'address' => '1 Party Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000001',
            'consider_for_accounting' => $considerForAccounting,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeAgent(array $context, string $name, bool $considerForAccounting): Agent
    {
        return Agent::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'address' => '1 Agent Street',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
            'consider_for_accounting' => $considerForAccounting,
        ]);
    }

    private function hasLedger(int $companyId, string $referenceType, int $referenceId): bool
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();
    }
}

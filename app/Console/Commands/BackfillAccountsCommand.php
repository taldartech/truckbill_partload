<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Party;
use App\Services\Accounting\AccountingReconciler;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillAccountsCommand extends Command
{
    protected $signature = 'accounting:backfill-accounts
                            {--company= : Limit to a single company ID}
                            {--enable-all : Turn on consider_for_accounting for all parties and agents before linking}
                            {--with-vouchers : Also reconcile operational documents into accounting vouchers (see accounting:reconcile)}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill chart of accounts, bank/party/agent ledgers (and optionally operational vouchers) for companies';

    public function handle(
        EnsureCompanyAccounting $ensureAccounting,
        EntityAccountLinker $linker,
        AccountingReconciler $reconciler,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $enableAll = (bool) $this->option('enable-all');
        $withVouchers = (bool) $this->option('with-vouchers');
        $companyId = $this->option('company') !== null ? (int) $this->option('company') : null;

        $companies = Company::query()
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->warn('No companies found.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes will be saved.');
        }

        $totals = [
            'companies' => 0,
            'coa' => 0,
            'banks' => 0,
            'parties_enabled' => 0,
            'parties_linked' => 0,
            'agents_enabled' => 0,
            'agents_linked' => 0,
            'vouchers' => 0,
            'errors' => 0,
        ];

        foreach ($companies as $company) {
            $this->newLine();
            $this->info("Company #{$company->id} — {$company->name}");

            try {
                $result = $dryRun
                    ? $this->previewCompany($company, $enableAll, $withVouchers)
                    : $this->backfillCompany($company, $enableAll, $withVouchers, $ensureAccounting, $linker, $reconciler);

                foreach ($result as $key => $value) {
                    $totals[$key] = ($totals[$key] ?? 0) + $value;
                }

                $totals['companies']++;
            } catch (Throwable $e) {
                $totals['errors']++;
                $this->error("  Failed: {$e->getMessage()}");
                report($e);
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($totals)->map(fn ($count, $key) => [str_replace('_', ' ', $key), $count])->values()->all(),
        );

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function previewCompany(Company $company, bool $enableAll, bool $withVouchers): array
    {
        $partyQuery = Party::query()->where('company_id', $company->id);
        $agentQuery = Agent::query()->where('company_id', $company->id);

        $partiesToEnable = $enableAll
            ? (clone $partyQuery)->where('consider_for_accounting', false)->count()
            : 0;
        $agentsToEnable = $enableAll
            ? (clone $agentQuery)->where('consider_for_accounting', false)->count()
            : 0;

        $partiesToLink = $enableAll
            ? (clone $partyQuery)->count()
            : (clone $partyQuery)->where('consider_for_accounting', true)->count();
        $agentsToLink = $enableAll
            ? (clone $agentQuery)->count()
            : (clone $agentQuery)->where('consider_for_accounting', true)->count();

        $banks = BankAccount::query()->where('company_id', $company->id)->count();

        $this->line('  CoA / FY ensure: yes');
        $this->line("  Bank accounts to sync: {$banks}");
        $this->line("  Parties flag enable: {$partiesToEnable}");
        $this->line("  Parties to link: {$partiesToLink}");
        $this->line("  Agents flag enable: {$agentsToEnable}");
        $this->line("  Agents to link: {$agentsToLink}");

        $vouchers = 0;
        if ($withVouchers) {
            $summary = app(AccountingReconciler::class)->run($company->id);
            $vouchers = $summary['post'] + $summary['repost'] + $summary['reverse'] + $summary['links'] + $summary['orphans'];
            $this->line("  Operational events to post/repost/reverse/link: {$vouchers}");
        }

        return [
            'coa' => 1,
            'banks' => $banks,
            'parties_enabled' => $partiesToEnable,
            'parties_linked' => $partiesToLink,
            'agents_enabled' => $agentsToEnable,
            'agents_linked' => $agentsToLink,
            'vouchers' => $vouchers,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function backfillCompany(
        Company $company,
        bool $enableAll,
        bool $withVouchers,
        EnsureCompanyAccounting $ensureAccounting,
        EntityAccountLinker $linker,
        AccountingReconciler $reconciler,
    ): array {
        return DB::transaction(function () use ($company, $enableAll, $withVouchers, $ensureAccounting, $linker, $reconciler) {
            $ensureAccounting->handle($company);
            $this->line('  Ensured chart of accounts + financial year');

            $banksSynced = 0;
            BankAccount::query()
                ->where('company_id', $company->id)
                ->orderBy('id')
                ->each(function (BankAccount $bank) use ($linker, &$banksSynced) {
                    $linker->syncBankAccount($bank);
                    $banksSynced++;
                });
            $this->line("  Synced bank accounts: {$banksSynced}");

            $partiesEnabled = 0;
            $partiesLinked = 0;
            if ($enableAll) {
                $partiesEnabled = Party::query()
                    ->where('company_id', $company->id)
                    ->where('consider_for_accounting', false)
                    ->update(['consider_for_accounting' => true]);
            }

            Party::query()
                ->where('company_id', $company->id)
                ->where('consider_for_accounting', true)
                ->orderBy('id')
                ->each(function (Party $party) use ($linker, &$partiesLinked) {
                    $linker->enable($party);
                    $partiesLinked++;
                });
            $this->line("  Parties enabled: {$partiesEnabled}, linked: {$partiesLinked}");

            $agentsEnabled = 0;
            $agentsLinked = 0;
            if ($enableAll) {
                $agentsEnabled = Agent::query()
                    ->where('company_id', $company->id)
                    ->where('consider_for_accounting', false)
                    ->update(['consider_for_accounting' => true]);
            }

            Agent::query()
                ->where('company_id', $company->id)
                ->where('consider_for_accounting', true)
                ->orderBy('id')
                ->each(function (Agent $agent) use ($linker, &$agentsLinked) {
                    $linker->enable($agent);
                    $agentsLinked++;
                });
            $this->line("  Agents enabled: {$agentsEnabled}, linked: {$agentsLinked}");

            $vouchers = 0;
            if ($withVouchers) {
                $vouchers = $this->syncOperationalVouchers($company, $reconciler);
                $this->line("  Operational vouchers synced: {$vouchers}");
            }

            return [
                'coa' => 1,
                'banks' => $banksSynced,
                'parties_enabled' => $partiesEnabled,
                'parties_linked' => $partiesLinked,
                'agents_enabled' => $agentsEnabled,
                'agents_linked' => $agentsLinked,
                'vouchers' => $vouchers,
            ];
        });
    }

    private function syncOperationalVouchers(Company $company, AccountingReconciler $reconciler): int
    {
        return $reconciler->run($company->id, null, false, fn (string $message) => $this->error('  '.$message))['changed'];
    }
}

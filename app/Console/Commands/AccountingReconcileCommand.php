<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Accounting\AccountingMapper;
use App\Services\Accounting\AccountingReconciler;
use Illuminate\Console\Command;

class AccountingReconcileCommand extends Command
{
    protected $signature = 'accounting:reconcile
                            {--company= : Limit to a single company ID}
                            {--event= : Limit to one accounting event, e.g. bilty.paid_receipt}
                            {--dry-run : Only show what would change (the default)}
                            {--commit : Post and reverse vouchers so the ledger matches the documents}';

    protected $description = 'Repost documents through the accounting bridge, link legacy vouchers and migrate legacy payment/receipt vouchers (dry run unless --commit)';

    public function handle(AccountingReconciler $reconciler): int
    {
        $eventKey = $this->option('event') ?: null;
        $knownEvents = array_merge(...array_values(AccountingMapper::EVENTS));
        if ($eventKey !== null && ! in_array($eventKey, $knownEvents, true)) {
            $this->error("Unknown event [{$eventKey}]. Known events: ".implode(', ', $knownEvents));

            return self::INVALID;
        }

        $dryRun = ! $this->option('commit') || $this->option('dry-run');
        $companyId = $this->option('company') !== null ? (int) $this->option('company') : null;
        $companies = Company::query()
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->warn('No companies found.');

            return self::FAILURE;
        }

        $this->warn($dryRun ? 'Dry run — nothing will be posted. Use --commit to apply.' : 'Committing changes through reversals and new postings.');

        $errors = 0;
        foreach ($companies as $company) {
            $summary = $reconciler->run($company->id, $eventKey, $dryRun, fn (string $message) => $this->error('  '.$message));
            $errors += $summary['errors'];

            $this->newLine();
            $this->info("Company #{$company->id} — {$company->name}");
            $this->table(['Metric', 'Count'], [
                ['Documents checked', $summary['documents']],
                ['To post (missing)', $summary['post']],
                ['To repost (changed)', $summary['repost']],
                ['To reverse (no longer applicable)', $summary['reverse']],
                ['Legacy vouchers to link', $summary['links']],
                ['Orphan vouchers to reverse', $summary['orphans']],
                ['Unchanged', $summary['unchanged']],
                ['Nothing to post', $summary['none']],
                ['Events changed', $dryRun ? '—' : $summary['changed']],
                ['Errors', $summary['errors']],
            ]);
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Accounting\AccountingIntegrity;
use Illuminate\Console\Command;

class AccountingAuditCommand extends Command
{
    protected $signature = 'accounting:audit
                            {--company= : Limit to a single company ID}
                            {--limit=20 : Items listed per check}';

    protected $description = 'Report missing, orphan, duplicate, unbalanced and misplaced accounting vouchers (read-only)';

    public function handle(AccountingIntegrity $integrity): int
    {
        $companyId = $this->option('company') !== null ? (int) $this->option('company') : null;
        $companies = Company::query()
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->warn('No companies found.');

            return self::FAILURE;
        }

        $issues = 0;
        foreach ($companies as $company) {
            $report = $integrity->audit($company->id, max(1, (int) $this->option('limit')));
            $issues += $report['total'];

            $this->newLine();
            $this->info("Company #{$company->id} — {$company->name}: {$report['total']} issue(s)");
            $this->table(
                ['Check', 'Count'],
                collect($report['checks'])->map(fn (array $check) => [$check['label'], $check['count']])->values()->all(),
            );

            foreach ($report['checks'] as $check) {
                foreach ($check['items'] as $item) {
                    $this->line('  - '.$check['label'].': '.$this->describe($item));
                }
            }
        }

        return $issues > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function describe(array $item): string
    {
        return implode(' · ', array_filter([
            isset($item['module']) ? $item['module'].' '.($item['number'] ?? '') : null,
            $item['event'] ?? null,
            isset($item['voucher_number']) ? 'Voucher '.$item['voucher_number'] : null,
            isset($item['voucher_numbers']) ? 'Vouchers '.implode(', ', $item['voucher_numbers']) : null,
            $item['detail'] ?? null,
        ]));
    }
}

<?php

namespace App\Services\Accounting\Reports;

use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Support\Carbon;

class OutstandingReport
{
    public function __construct(private AccountLedgerReport $ledger) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(int $companyId, string $groupSystemKey, Carbon|string $from, Carbon|string $to): array
    {
        $group = AccountGroup::query()
            ->where('company_id', $companyId)
            ->where('system_key', $groupSystemKey)
            ->first();

        if (! $group) {
            return [];
        }

        $groupIds = $this->descendantGroupIds($group);

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->whereIn('account_group_id', $groupIds)
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($accounts as $account) {
            $ledger = $this->ledger->build($account, $from, $to);
            if ($ledger['opening'] == 0.0 && $ledger['totals']['debit'] == 0.0 && $ledger['totals']['credit'] == 0.0) {
                continue;
            }

            $rows[] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'opening' => $ledger['opening'],
                'debit' => $ledger['totals']['debit'],
                'credit' => $ledger['totals']['credit'],
                'outstanding' => $ledger['closing'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<int>
     */
    private function descendantGroupIds(AccountGroup $group): array
    {
        $ids = [$group->id];
        $children = AccountGroup::query()->where('parent_id', $group->id)->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->descendantGroupIds($child));
        }

        return $ids;
    }
}

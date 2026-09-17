<?php

namespace App\Services\Accounting;

use App\Enums\AccountNature;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public const SYSTEM_GROUP_KEYS = [
        'assets' => ['name' => 'Assets', 'nature' => AccountNature::Assets, 'parent' => null],
        'current_assets' => ['name' => 'Current Assets', 'nature' => AccountNature::Assets, 'parent' => 'assets'],
        'cash_in_hand' => ['name' => 'Cash-in-Hand', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'bank_accounts' => ['name' => 'Bank Accounts', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'sundry_debtors' => ['name' => 'Sundry Debtors', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'liabilities' => ['name' => 'Liabilities', 'nature' => AccountNature::Liabilities, 'parent' => null],
        'current_liabilities' => ['name' => 'Current Liabilities', 'nature' => AccountNature::Liabilities, 'parent' => 'liabilities'],
        'sundry_creditors' => ['name' => 'Sundry Creditors', 'nature' => AccountNature::Liabilities, 'parent' => 'current_liabilities'],
        'capital' => ['name' => 'Capital', 'nature' => AccountNature::Capital, 'parent' => null],
        'capital_account' => ['name' => 'Capital Account', 'nature' => AccountNature::Capital, 'parent' => 'capital'],
        'income' => ['name' => 'Income', 'nature' => AccountNature::Income, 'parent' => null],
        'direct_incomes' => ['name' => 'Direct Incomes', 'nature' => AccountNature::Income, 'parent' => 'income'],
        'expenses' => ['name' => 'Expenses', 'nature' => AccountNature::Expenses, 'parent' => null],
        'direct_expenses' => ['name' => 'Direct Expenses', 'nature' => AccountNature::Expenses, 'parent' => 'expenses'],
    ];

    public const SYSTEM_ACCOUNT_KEYS = [
        'cash' => ['name' => 'Cash', 'group' => 'cash_in_hand', 'code' => 'CASH'],
        'freight_income' => ['name' => 'Freight Income', 'group' => 'direct_incomes', 'code' => 'FRT-INC'],
        'agent_challan_income' => ['name' => 'Agent Challan Income', 'group' => 'direct_incomes', 'code' => 'AG-CH-INC'],
        'cartage_expense' => ['name' => 'Cartage Expense', 'group' => 'direct_expenses', 'code' => 'CART-EXP'],
        'agent_crossing_expense' => ['name' => 'Agent Crossing Expense', 'group' => 'direct_expenses', 'code' => 'AG-CR-EXP'],
        'agent_challan_expense' => ['name' => 'Agent Challan Expense', 'group' => 'direct_expenses', 'code' => 'AG-CH-EXP'],
        'owners_capital' => ['name' => "Owner's Capital", 'group' => 'capital_account', 'code' => 'CAP'],
    ];

    public function ensureDefaults(Company|int $company): void
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        DB::transaction(function () use ($companyId) {
            $groupIds = [];

            foreach (self::SYSTEM_GROUP_KEYS as $key => $meta) {
                $parentId = $meta['parent'] ? ($groupIds[$meta['parent']] ?? null) : null;

                $group = AccountGroup::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'system_key' => $key,
                    ],
                    [
                        'parent_id' => $parentId,
                        'name' => $meta['name'],
                        'nature' => $meta['nature']->value,
                        'is_system' => true,
                        'is_active' => true,
                    ],
                );

                if ($group->parent_id !== $parentId) {
                    $group->update(['parent_id' => $parentId]);
                }

                $groupIds[$key] = $group->id;
            }

            foreach (self::SYSTEM_ACCOUNT_KEYS as $key => $meta) {
                Account::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'system_key' => $key,
                    ],
                    [
                        'account_group_id' => $groupIds[$meta['group']],
                        'name' => $meta['name'],
                        'code' => $meta['code'],
                        'is_system' => true,
                        'is_active' => true,
                        'is_selectable' => true,
                    ],
                );
            }
        });
    }

    public function systemAccount(Company|int $company, string $systemKey): Account
    {
        $this->ensureDefaults($company);

        $companyId = $company instanceof Company ? $company->id : $company;

        return Account::query()
            ->where('company_id', $companyId)
            ->where('system_key', $systemKey)
            ->firstOrFail();
    }

    public function systemGroup(Company|int $company, string $systemKey): AccountGroup
    {
        $this->ensureDefaults($company);

        $companyId = $company instanceof Company ? $company->id : $company;

        return AccountGroup::query()
            ->where('company_id', $companyId)
            ->where('system_key', $systemKey)
            ->firstOrFail();
    }
}

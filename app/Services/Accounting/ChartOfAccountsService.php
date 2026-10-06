<?php

namespace App\Services\Accounting;

use App\Enums\AccountNature;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public const SYSTEM_GROUP_KEYS = [
        'assets' => ['name' => 'Assets', 'nature' => AccountNature::Assets, 'parent' => null],
        'current_assets' => ['name' => 'Current Assets', 'nature' => AccountNature::Assets, 'parent' => 'assets'],
        'cash_in_hand' => ['name' => 'Cash-in-Hand', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'bank_accounts' => ['name' => 'Bank Accounts', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'sundry_debtors' => ['name' => 'Sundry Debtors', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'loans_advances_asset' => ['name' => 'Loans & Advances (Asset)', 'nature' => AccountNature::Assets, 'parent' => 'current_assets'],
        'liabilities' => ['name' => 'Liabilities', 'nature' => AccountNature::Liabilities, 'parent' => null],
        'current_liabilities' => ['name' => 'Current Liabilities', 'nature' => AccountNature::Liabilities, 'parent' => 'liabilities'],
        'sundry_creditors' => ['name' => 'Sundry Creditors', 'nature' => AccountNature::Liabilities, 'parent' => 'current_liabilities'],
        'duties_taxes' => ['name' => 'Duties & Taxes', 'nature' => AccountNature::Liabilities, 'parent' => 'current_liabilities'],
        'advances_received' => ['name' => 'Advances Received', 'nature' => AccountNature::Liabilities, 'parent' => 'current_liabilities'],
        'capital' => ['name' => 'Capital', 'nature' => AccountNature::Capital, 'parent' => null],
        'capital_account' => ['name' => 'Capital Account', 'nature' => AccountNature::Capital, 'parent' => 'capital'],
        'income' => ['name' => 'Income', 'nature' => AccountNature::Income, 'parent' => null],
        'direct_incomes' => ['name' => 'Direct Incomes', 'nature' => AccountNature::Income, 'parent' => 'income'],
        'indirect_incomes' => ['name' => 'Indirect Incomes', 'nature' => AccountNature::Income, 'parent' => 'income'],
        'expenses' => ['name' => 'Expenses', 'nature' => AccountNature::Expenses, 'parent' => null],
        'direct_expenses' => ['name' => 'Direct Expenses', 'nature' => AccountNature::Expenses, 'parent' => 'expenses'],
        'indirect_expenses' => ['name' => 'Indirect Expenses', 'nature' => AccountNature::Expenses, 'parent' => 'expenses'],
    ];

    public const SYSTEM_ACCOUNT_KEYS = [
        'cash' => ['name' => 'Cash', 'group' => 'cash_in_hand', 'code' => 'CASH'],
        'freight_income' => ['name' => 'Freight Income', 'group' => 'direct_incomes', 'code' => 'FRT-INC'],
        'agent_challan_income' => ['name' => 'Agent Challan Income', 'group' => 'direct_incomes', 'code' => 'AG-CH-INC'],
        'bilty_charges_income' => ['name' => 'Bilty Charges Income', 'group' => 'direct_incomes', 'code' => 'BLT-CHG-INC'],
        'delivery_charges_income' => ['name' => 'Delivery Charges Income', 'group' => 'direct_incomes', 'code' => 'DLV-CHG-INC'],
        'demurrage_income' => ['name' => 'Demurrage Income', 'group' => 'indirect_incomes', 'code' => 'DEM-INC'],
        'cartage_expense' => ['name' => 'Cartage Expense', 'group' => 'direct_expenses', 'code' => 'CART-EXP'],
        'agent_crossing_expense' => ['name' => 'Agent Crossing Expense', 'group' => 'direct_expenses', 'code' => 'AG-CR-EXP'],
        'agent_challan_expense' => ['name' => 'Agent Challan Expense', 'group' => 'direct_expenses', 'code' => 'AG-CH-EXP'],
        'truck_hire_expense' => ['name' => 'Truck Hire Expense', 'group' => 'direct_expenses', 'code' => 'TRK-HIRE-EXP'],
        'labour_expense' => ['name' => 'Labour Expense', 'group' => 'direct_expenses', 'code' => 'LAB-EXP'],
        'kat_expense' => ['name' => 'Kat Expense', 'group' => 'direct_expenses', 'code' => 'KAT-EXP'],
        'home_delivery_expense' => ['name' => 'Home Delivery Expense', 'group' => 'direct_expenses', 'code' => 'HD-EXP'],
        'diesel_expense' => ['name' => 'Diesel Expense', 'group' => 'direct_expenses', 'code' => 'DSL-EXP'],
        'general_expense' => ['name' => 'General Expense', 'group' => 'indirect_expenses', 'code' => 'GEN-EXP'],
        'discount_allowed' => ['name' => 'Discount Allowed', 'group' => 'indirect_expenses', 'code' => 'DISC-ALW'],
        'round_off' => ['name' => 'Round Off', 'group' => 'indirect_expenses', 'code' => 'RND-OFF'],
        'gst_output_cgst' => ['name' => 'Output CGST', 'group' => 'duties_taxes', 'code' => 'GST-OUT-CGST'],
        'gst_output_sgst' => ['name' => 'Output SGST', 'group' => 'duties_taxes', 'code' => 'GST-OUT-SGST'],
        'gst_output_igst' => ['name' => 'Output IGST', 'group' => 'duties_taxes', 'code' => 'GST-OUT-IGST'],
        'gst_input' => ['name' => 'GST Input Credit', 'group' => 'duties_taxes', 'code' => 'GST-IN'],
        'tds_payable' => ['name' => 'TDS Payable', 'group' => 'duties_taxes', 'code' => 'TDS-PAY'],
        'tds_receivable' => ['name' => 'TDS Receivable', 'group' => 'loans_advances_asset', 'code' => 'TDS-REC'],
        'sundry_debtors_unlinked' => ['name' => 'Sundry Debtors (Unlinked Parties)', 'group' => 'sundry_debtors', 'code' => 'SD-UNLINKED'],
        'sundry_creditors_unlinked' => ['name' => 'Sundry Creditors (Unlinked Agents)', 'group' => 'sundry_creditors', 'code' => 'SC-UNLINKED'],
        'owners_capital' => ['name' => "Owner's Capital", 'group' => 'capital_account', 'code' => 'CAP'],
    ];

    /**
     * Company ids whose defaults were ensured during this request.
     *
     * @var array<int, true>
     */
    private array $ensuredCompanyIds = [];

    public function ensureDefaults(Company|int $company): void
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        if (isset($this->ensuredCompanyIds[$companyId])) {
            return;
        }

        DB::transaction(function () use ($companyId) {
            $groupIds = [];

            foreach (self::SYSTEM_GROUP_KEYS as $key => $meta) {
                $parentId = $meta['parent'] ? ($groupIds[$meta['parent']] ?? null) : null;

                $group = AccountGroup::query()->withoutGlobalScopes()->firstOrCreate(
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
                Account::query()->withoutGlobalScopes()->firstOrCreate(
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

        $this->ensuredCompanyIds[$companyId] = true;
    }

    public function forgetEnsured(?int $companyId = null): void
    {
        if ($companyId === null) {
            $this->ensuredCompanyIds = [];

            return;
        }

        unset($this->ensuredCompanyIds[$companyId]);
    }

    public function systemAccount(Company|int $company, string $systemKey): Account
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $this->ensureDefaults($companyId);

        $find = fn () => Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('system_key', $systemKey)
            ->first();

        $account = $find();

        if (! $account) {
            $this->forgetEnsured($companyId);
            $this->ensureDefaults($companyId);
            $account = $find() ?? throw (new ModelNotFoundException)->setModel(Account::class);
        }

        return $account;
    }

    public function systemGroup(Company|int $company, string $systemKey): AccountGroup
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $this->ensureDefaults($companyId);

        $find = fn () => AccountGroup::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('system_key', $systemKey)
            ->first();

        $group = $find();

        if (! $group) {
            $this->forgetEnsured($companyId);
            $this->ensureDefaults($companyId);
            $group = $find() ?? throw (new ModelNotFoundException)->setModel(AccountGroup::class);
        }

        return $group;
    }
}

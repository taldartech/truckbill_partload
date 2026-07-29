<?php

namespace App\Services\Accounting;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\LedgerAccount;
use App\Models\Party;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class LedgerAccountResolver
{
    public const SYSTEM_ACCOUNTS = [
        'cash' => ['name' => 'Cash', 'nature' => 'asset', 'kind' => 'cash'],
        'freight_income' => ['name' => 'Freight Income', 'nature' => 'income', 'kind' => 'system'],
        'cartage_expense' => ['name' => 'Cartage Expense', 'nature' => 'expense', 'kind' => 'system'],
        'agent_crossing_expense' => ['name' => 'Agent Crossing Expense', 'nature' => 'expense', 'kind' => 'system'],
        'agent_challan_expense' => ['name' => 'Agent Challan Expense', 'nature' => 'expense', 'kind' => 'system'],
        'agent_challan_income' => ['name' => 'Agent Challan Income', 'nature' => 'income', 'kind' => 'system'],
    ];

    public function ensureSystemAccounts(Company|int $company): void
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        foreach (self::SYSTEM_ACCOUNTS as $systemKey => $meta) {
            LedgerAccount::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'system_key' => $systemKey,
                ],
                [
                    'name' => $meta['name'],
                    'code' => strtoupper($systemKey),
                    'nature' => $meta['nature'],
                    'kind' => $meta['kind'],
                ],
            );
        }
    }

    public function system(Company|int $company, string $systemKey): LedgerAccount
    {
        $this->ensureSystemAccounts($company);

        $companyId = $company instanceof Company ? $company->id : $company;

        return LedgerAccount::query()
            ->where('company_id', $companyId)
            ->where('system_key', $systemKey)
            ->firstOrFail();
    }

    public function cash(Company|int $company): LedgerAccount
    {
        return $this->system($company, 'cash');
    }

    public function forBankAccount(BankAccount $bankAccount): LedgerAccount
    {
        $this->ensureSystemAccounts($bankAccount->company_id);

        return LedgerAccount::query()->firstOrCreate(
            [
                'company_id' => $bankAccount->company_id,
                'bank_account_id' => $bankAccount->id,
                'kind' => 'bank',
            ],
            [
                'name' => $bankAccount->name,
                'code' => 'BANK-'.$bankAccount->id,
                'nature' => 'asset',
                'system_key' => null,
            ],
        );
    }

    public function forEntity(Model $entity): LedgerAccount
    {
        $kind = match ($entity::class) {
            Vendor::class => 'vendor',
            Agent::class => 'agent',
            Party::class => 'party',
            default => throw new \InvalidArgumentException('Unsupported accountable entity: '.$entity::class),
        };

        $nature = $kind === 'party' ? 'asset' : 'liability';
        $companyId = (int) $entity->company_id;

        $this->ensureSystemAccounts($companyId);

        return LedgerAccount::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'accountable_type' => $entity::class,
                'accountable_id' => $entity->id,
                'kind' => $kind,
            ],
            [
                'name' => $entity->name ?? (ucfirst($kind).' #'.$entity->id),
                'code' => strtoupper($kind).'-'.$entity->id,
                'nature' => $nature,
                'system_key' => null,
            ],
        );
    }
}

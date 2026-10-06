<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EntityAccountLinker
{
    public function __construct(private ChartOfAccountsService $chartOfAccounts) {}

    public function isAccountingEnabled(Model $entity): bool
    {
        return (bool) ($entity->consider_for_accounting ?? false);
    }

    public function linkedAccount(Model $entity): ?Account
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $entity->company_id)
            ->where('reference_type', $entity::class)
            ->where('reference_id', $entity->getKey())
            ->first();
    }

    public function enable(
        Model $entity,
        ?int $accountGroupId = null,
        ?string $suggestedGroupKey = null,
    ): Account {
        if (! in_array($entity::class, [Party::class, Agent::class], true)) {
            throw ValidationException::withMessages([
                'entity' => 'Only Party and Agent can be linked for accounting.',
            ]);
        }

        $this->chartOfAccounts->ensureDefaults((int) $entity->company_id);

        $group = $this->resolveGroup($entity, $accountGroupId, $suggestedGroupKey);

        $account = $this->linkedAccount($entity);

        if ($account) {
            $account->update([
                'account_group_id' => $group->id,
                'name' => $entity->name,
                'is_active' => true,
                'is_selectable' => true,
            ]);

            return $account->fresh('accountGroup');
        }

        return Account::query()->create([
            'company_id' => $entity->company_id,
            'account_group_id' => $group->id,
            'name' => $entity->name,
            'code' => null,
            'is_system' => false,
            'is_active' => true,
            'is_selectable' => true,
            'reference_type' => $entity::class,
            'reference_id' => $entity->getKey(),
        ])->load('accountGroup');
    }

    public function disable(Model $entity): ?Account
    {
        $account = $this->linkedAccount($entity);

        if (! $account) {
            return null;
        }

        $account->update([
            'is_selectable' => false,
        ]);

        if (! $account->hasPostedHistory()) {
            $account->update(['is_active' => false]);
        }

        return $account->fresh();
    }

    public function syncName(Model $entity): void
    {
        $account = $this->linkedAccount($entity);

        if ($account) {
            $account->update(['name' => $entity->name]);
        }
    }

    public function syncBankAccount(BankAccount $bankAccount): Account
    {
        $this->chartOfAccounts->ensureDefaults((int) $bankAccount->company_id);
        $group = $this->chartOfAccounts->systemGroup($bankAccount->company_id, 'bank_accounts');

        $account = Account::query()
            ->where('company_id', $bankAccount->company_id)
            ->where(function ($query) use ($bankAccount) {
                $query->where('bank_account_id', $bankAccount->id)
                    ->orWhere(function ($inner) use ($bankAccount) {
                        $inner->where('reference_type', BankAccount::class)
                            ->where('reference_id', $bankAccount->id);
                    });
            })
            ->first();

        if ($account) {
            $account->update([
                'account_group_id' => $group->id,
                'name' => $bankAccount->name,
                'bank_account_id' => $bankAccount->id,
                'reference_type' => BankAccount::class,
                'reference_id' => $bankAccount->id,
                'is_active' => (bool) $bankAccount->is_active,
                'is_selectable' => (bool) $bankAccount->is_active,
            ]);

            return $account->fresh('accountGroup');
        }

        return Account::query()->create([
            'company_id' => $bankAccount->company_id,
            'account_group_id' => $group->id,
            'name' => $bankAccount->name,
            'code' => 'BANK-'.$bankAccount->id,
            'is_system' => false,
            'is_active' => (bool) $bankAccount->is_active,
            'is_selectable' => (bool) $bankAccount->is_active,
            'reference_type' => BankAccount::class,
            'reference_id' => $bankAccount->id,
            'bank_account_id' => $bankAccount->id,
        ])->load('accountGroup');
    }

    public function bankLedger(BankAccount|int|null $bankAccount, int $companyId): ?Account
    {
        $bankAccountId = $bankAccount instanceof BankAccount ? $bankAccount->id : $bankAccount;

        if (! $bankAccountId) {
            return null;
        }

        return Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->first();
    }

    /**
     * Hides a deleted bank's ledger from selection; keeps it active when it carries history.
     */
    public function deactivateBankAccount(BankAccount $bankAccount): ?Account
    {
        $account = $this->bankLedger($bankAccount, (int) $bankAccount->company_id);

        if (! $account) {
            return null;
        }

        $account->update([
            'is_selectable' => false,
            'is_active' => $account->hasPostedHistory(),
        ]);

        return $account->fresh();
    }

    public function deleteAccountIfSafe(Account $account): bool
    {
        if ($account->is_system || $account->hasPostedHistory()) {
            return false;
        }

        $account->delete();

        return true;
    }

    private function resolveGroup(
        Model $entity,
        ?int $accountGroupId,
        ?string $suggestedGroupKey,
    ): AccountGroup {
        if ($accountGroupId) {
            $group = AccountGroup::query()
                ->where('company_id', $entity->company_id)
                ->whereKey($accountGroupId)
                ->first();

            if ($group) {
                return $group;
            }
        }

        $defaultKey = $suggestedGroupKey ?? match ($entity::class) {
            Party::class => 'sundry_debtors',
            Agent::class => 'sundry_creditors',
            default => null,
        };

        if ($defaultKey) {
            return $this->chartOfAccounts->systemGroup($entity->company_id, $defaultKey);
        }

        throw ValidationException::withMessages([
            'account_group_id' => 'Account group is required.',
        ]);
    }
}

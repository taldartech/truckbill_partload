<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherEntry;
use App\Models\VoucherSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public function __construct(
        private FinancialYearService $financialYears,
        private ChartOfAccountsService $chartOfAccounts,
    ) {}

    /**
     * @param  list<array{account_id:int,debit?:float|int|string,credit?:float|int|string,narration?:?string}>  $lines
     */
    public function post(array $attributes, array $lines): AccountingVoucher
    {
        return DB::transaction(function () use ($attributes, $lines) {
            $companyId = (int) $attributes['company_id'];
            $date = Carbon::parse($attributes['date']);
            $type = $attributes['voucher_type'] instanceof AccountingVoucherType
                ? $attributes['voucher_type']
                : AccountingVoucherType::from((string) $attributes['voucher_type']);

            $this->chartOfAccounts->ensureDefaults($companyId);

            $financialYear = $attributes['financial_year_id'] ?? null
                ? \App\Models\FinancialYear::query()->findOrFail($attributes['financial_year_id'])
                : $this->financialYears->forDate($companyId, $date);

            if ((int) $financialYear->company_id !== $companyId) {
                throw ValidationException::withMessages([
                    'financial_year_id' => 'Financial year does not belong to this company.',
                ]);
            }

            $this->financialYears->assertOpen($financialYear);

            if (! $financialYear->containsDate($date)) {
                throw ValidationException::withMessages([
                    'date' => 'Voucher date is outside the financial year.',
                ]);
            }

            $normalized = $this->normalizeAndValidateLines($companyId, $lines);

            $voucherNumber = $attributes['voucher_number']
                ?? $this->nextNumber($companyId, $financialYear->id, $type);

            $this->assertUniqueNumber($companyId, $financialYear->id, $type, $voucherNumber);

            $voucher = AccountingVoucher::query()->create([
                'company_id' => $companyId,
                'branch_id' => $attributes['branch_id'] ?? null,
                'financial_year_id' => $financialYear->id,
                'voucher_type' => $type,
                'voucher_number' => $voucherNumber,
                'date' => $date->toDateString(),
                'narration' => $attributes['narration'] ?? null,
                'status' => AccountingVoucherStatus::Posted,
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'created_by' => $attributes['created_by'] ?? Auth::id(),
                'updated_by' => $attributes['updated_by'] ?? Auth::id(),
            ]);

            foreach ($normalized as $index => $line) {
                AccountingVoucherEntry::query()->create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'narration' => $line['narration'],
                    'line_no' => $index + 1,
                ]);
            }

            return $voucher->load('entries.account.accountGroup');
        });
    }

    public function cancel(AccountingVoucher $voucher, ?int $userId = null): AccountingVoucher
    {
        return DB::transaction(function () use ($voucher, $userId) {
            if ($voucher->status !== AccountingVoucherStatus::Posted) {
                throw ValidationException::withMessages([
                    'voucher' => 'Only posted vouchers can be cancelled.',
                ]);
            }

            $voucher->update([
                'status' => AccountingVoucherStatus::Cancelled,
                'cancelled_by' => $userId ?? Auth::id(),
                'cancelled_at' => now(),
                'updated_by' => $userId ?? Auth::id(),
            ]);

            return $voucher->fresh('entries');
        });
    }

    public function reverse(AccountingVoucher $voucher, ?string $date = null, ?int $userId = null): AccountingVoucher
    {
        return DB::transaction(function () use ($voucher, $date, $userId) {
            if ($voucher->status !== AccountingVoucherStatus::Posted) {
                throw ValidationException::withMessages([
                    'voucher' => 'Only posted vouchers can be reversed.',
                ]);
            }

            $voucher->loadMissing('entries');

            $lines = $voucher->entries->map(fn (AccountingVoucherEntry $entry) => [
                'account_id' => $entry->account_id,
                'debit' => (float) $entry->credit,
                'credit' => (float) $entry->debit,
                'narration' => 'Reversal of '.$voucher->voucher_number,
            ])->all();

            $reverse = $this->post([
                'company_id' => $voucher->company_id,
                'branch_id' => $voucher->branch_id,
                'financial_year_id' => $voucher->financial_year_id,
                'voucher_type' => $voucher->voucher_type,
                'date' => $date ?? now()->toDateString(),
                'narration' => 'Reversal of '.$voucher->voucher_type->label().' '.$voucher->voucher_number,
                'source_type' => $voucher->source_type,
                'source_id' => $voucher->source_id,
                'created_by' => $userId ?? Auth::id(),
            ], $lines);

            $reverse->update([
                'reversed_voucher_id' => $voucher->id,
            ]);

            return $reverse->fresh('entries.account');
        });
    }

    public function reverseSourceVouchers(Model $source): void
    {
        AccountingVoucher::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->posted()
            ->whereNull('reversed_voucher_id')
            ->whereDoesntHave('reversals')
            ->orderBy('id')
            ->get()
            ->each(fn (AccountingVoucher $voucher) => $this->reverse($voucher));
    }

    public function nextNumber(int $companyId, int $financialYearId, AccountingVoucherType $type): string
    {
        $sequence = VoucherSequence::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_type' => $type,
            ],
            [
                'prefix' => $type->prefix(),
                'next_number' => 1,
            ],
        );

        $number = $sequence->next_number;
        $sequence->update(['next_number' => $number + 1]);

        return sprintf('%s-%06d', $sequence->prefix, $number);
    }

    /**
     * @param  list<array{account_id:int,debit?:float|int|string,credit?:float|int|string,narration?:?string}>  $lines
     * @return list<array{account_id:int,debit:float,credit:float,narration:?string}>
     */
    private function normalizeAndValidateLines(int $companyId, array $lines): array
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'entries' => 'A voucher requires at least two lines.',
            ]);
        }

        $normalized = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($accountId <= 0) {
                throw ValidationException::withMessages([
                    "entries.{$index}.account_id" => 'Account is required.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    "entries.{$index}" => 'Each line must have either debit or credit (not both).',
                ]);
            }

            $account = Account::query()->find($accountId);

            if (! $account || (int) $account->company_id !== $companyId) {
                throw ValidationException::withMessages([
                    "entries.{$index}.account_id" => 'Invalid account for this company.',
                ]);
            }

            if (! $account->is_active) {
                throw ValidationException::withMessages([
                    "entries.{$index}.account_id" => 'Account is inactive.',
                ]);
            }

            $normalized[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'narration' => $line['narration'] ?? null,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if ($totalDebit <= 0 || $totalCredit <= 0 || $totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'entries' => 'Total debit must equal total credit and be greater than zero.',
            ]);
        }

        return $normalized;
    }

    private function assertUniqueNumber(
        int $companyId,
        int $financialYearId,
        AccountingVoucherType $type,
        string $voucherNumber,
    ): void {
        $exists = AccountingVoucher::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type', $type)
            ->where('voucher_number', $voucherNumber)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'voucher_number' => 'Voucher number already exists for this financial year and type.',
            ]);
        }
    }
}

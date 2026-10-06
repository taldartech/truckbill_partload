<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherEntry;
use App\Models\FinancialYear;
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
     * Converts a money value to integer paise so balancing never depends on float rounding.
     */
    public static function toPaise(float|int|string|null $amount): int
    {
        return (int) round(round((float) ($amount ?? 0), 2) * 100);
    }

    public static function fromPaise(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }

    /**
     * @param  array{company_id:int,date:string|\DateTimeInterface,voucher_type:AccountingVoucherType|string,branch_id?:?int,financial_year_id?:?int,voucher_number?:?string,narration?:?string,source_type?:?string,source_id?:?int,event_key?:?string,created_by?:?int,updated_by?:?int}  $attributes
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

            $financialYear = ($attributes['financial_year_id'] ?? null)
                ? FinancialYear::query()->withoutGlobalScopes()->findOrFail($attributes['financial_year_id'])
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
                'event_key' => $attributes['event_key'] ?? null,
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

    public function cancel(AccountingVoucher $voucher, ?int $userId = null, ?string $reason = null): AccountingVoucher
    {
        return DB::transaction(function () use ($voucher, $userId, $reason) {
            $voucher = $this->lockVoucher($voucher);

            if ($voucher->status !== AccountingVoucherStatus::Posted) {
                throw ValidationException::withMessages([
                    'voucher' => 'Only posted vouchers can be cancelled.',
                ]);
            }

            $this->financialYears->assertOpen($voucher->financialYear()->withoutGlobalScopes()->firstOrFail());

            $voucher->update([
                'status' => AccountingVoucherStatus::Cancelled,
                'cancelled_by' => $userId ?? Auth::id(),
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updated_by' => $userId ?? Auth::id(),
            ]);

            return $voucher->fresh('entries');
        });
    }

    /**
     * Posts a mirror voucher. Defaults to the original voucher date so the
     * reversal lands in the same period; a closed year blocks the reversal.
     */
    public function reverse(AccountingVoucher $voucher, ?string $date = null, ?int $userId = null): AccountingVoucher
    {
        return DB::transaction(function () use ($voucher, $date, $userId) {
            $voucher = $this->lockVoucher($voucher);

            if (! $voucher->isOpenForReversal()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Only posted vouchers that have not been reversed can be reversed.',
                ]);
            }

            $voucher->loadMissing('entries');
            $actorId = $userId ?? Auth::id();

            $lines = $voucher->entries->map(fn (AccountingVoucherEntry $entry) => [
                'account_id' => $entry->account_id,
                'debit' => $entry->credit,
                'credit' => $entry->debit,
                'narration' => 'Reversal of '.$voucher->voucher_number,
            ])->all();

            $reverse = $this->post([
                'company_id' => $voucher->company_id,
                'branch_id' => $voucher->branch_id,
                'financial_year_id' => $date === null ? $voucher->financial_year_id : null,
                'voucher_type' => $voucher->voucher_type,
                'date' => $date ?? $voucher->date->toDateString(),
                'narration' => 'Reversal of '.$voucher->voucher_type->label().' '.$voucher->voucher_number,
                'source_type' => $voucher->source_type,
                'source_id' => $voucher->source_id,
                'event_key' => $voucher->event_key,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ], $lines);

            $reverse->update(['reversed_voucher_id' => $voucher->id]);

            $voucher->update([
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'updated_by' => $actorId,
            ]);

            return $reverse->fresh('entries.account');
        });
    }

    /**
     * Reverses every still-open voucher posted for $source (optionally one event only).
     */
    public function reverseSourceVouchers(Model $source, ?string $eventKey = null): void
    {
        AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->when($eventKey !== null, fn ($query) => $query->where('event_key', $eventKey))
            ->where('status', AccountingVoucherStatus::Posted)
            ->whereNull('reversed_voucher_id')
            ->whereNull('reversed_at')
            ->whereDoesntHave('reversals', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get()
            ->each(fn (AccountingVoucher $voucher) => $this->reverse($voucher));
    }

    public function nextNumber(int $companyId, int $financialYearId, AccountingVoucherType $type): string
    {
        return DB::transaction(function () use ($companyId, $financialYearId, $type) {
            $keys = [
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_type' => $type,
            ];

            VoucherSequence::query()->withoutGlobalScopes()->firstOrCreate($keys, [
                'prefix' => $type->prefix(),
                'next_number' => 1,
            ]);

            $sequence = VoucherSequence::query()
                ->withoutGlobalScopes()
                ->where($keys)
                ->lockForUpdate()
                ->firstOrFail();

            $number = (int) $sequence->next_number;
            do {
                $voucherNumber = sprintf('%s-%06d', $sequence->prefix, $number);
                $number++;
            } while ($this->numberExists($companyId, $financialYearId, $type, $voucherNumber));

            $sequence->update(['next_number' => $number]);

            return $voucherNumber;
        });
    }

    private function lockVoucher(AccountingVoucher $voucher): AccountingVoucher
    {
        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->whereKey($voucher->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  list<array{account_id:int,debit?:float|int|string,credit?:float|int|string,narration?:?string}>  $lines
     * @return list<array{account_id:int,debit:string,credit:string,narration:?string}>
     */
    private function normalizeAndValidateLines(int $companyId, array $lines): array
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'entries' => 'A voucher requires at least two lines.',
            ]);
        }

        $normalized = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach (array_values($lines) as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = self::toPaise($line['debit'] ?? 0);
            $credit = self::toPaise($line['credit'] ?? 0);

            if ($accountId <= 0) {
                throw ValidationException::withMessages([
                    "entries.{$index}.account_id" => 'Account is required.',
                ]);
            }

            if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                throw ValidationException::withMessages([
                    "entries.{$index}" => 'Each line must have either debit or credit (not both).',
                ]);
            }

            $account = Account::query()->withoutGlobalScopes()->find($accountId);

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
                'debit' => self::fromPaise($debit),
                'credit' => self::fromPaise($credit),
                'narration' => $line['narration'] ?? null,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'entries' => 'Total debit must equal total credit and be greater than zero.',
            ]);
        }

        return $normalized;
    }

    private function numberExists(int $companyId, int $financialYearId, AccountingVoucherType $type, string $voucherNumber): bool
    {
        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type', $type)
            ->where('voucher_number', $voucherNumber)
            ->exists();
    }

    private function assertUniqueNumber(
        int $companyId,
        int $financialYearId,
        AccountingVoucherType $type,
        string $voucherNumber,
    ): void {
        if ($this->numberExists($companyId, $financialYearId, $type, $voucherNumber)) {
            throw ValidationException::withMessages([
                'voucher_number' => 'Voucher number already exists for this financial year and type.',
            ]);
        }
    }
}

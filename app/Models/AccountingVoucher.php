<?php

namespace App\Models;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountingVoucherType;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingVoucher extends Model
{
    use BelongsToBranch;
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'voucher_type' => AccountingVoucherType::class,
            'status' => AccountingVoucherStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingVoucherEntry::class, 'voucher_id')->orderBy('line_no');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reversedVoucher(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_voucher_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_voucher_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', AccountingVoucherStatus::Posted);
    }

    public function totalDebit(): float
    {
        return round((float) $this->entries->sum('debit'), 2);
    }

    public function totalCredit(): float
    {
        return round((float) $this->entries->sum('credit'), 2);
    }
}

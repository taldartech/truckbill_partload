<?php

namespace App\Models;

use App\Enums\AccountingVoucherType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherSequence extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'voucher_type' => AccountingVoucherType::class,
            'next_number' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}

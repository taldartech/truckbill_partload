<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialYear extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'is_current' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(AccountingVoucher::class);
    }

    public function containsDate(\DateTimeInterface|string $date): bool
    {
        $carbon = \Illuminate\Support\Carbon::parse($date)->startOfDay();

        return $carbon->betweenIncluded($this->starts_on->startOfDay(), $this->ends_on->endOfDay());
    }
}

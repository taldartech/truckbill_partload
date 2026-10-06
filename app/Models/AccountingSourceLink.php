<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row per (document, accounting event): points at the voucher currently
 * representing that event, or null when the event has nothing posted.
 */
class AccountingSourceLink extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(AccountingVoucher::class, 'voucher_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}

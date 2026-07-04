<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpcomingSubscription extends Model
{
    protected $fillable = [
        'company_id',
        'payment_id',
        'included_branches',
        'users_per_branch',
        'included_lr_credits',
        'included_trucks',
        'addon_branches',
        'addon_users',
        'addon_trucks',
        'addon_lr_credits',
        'status',
        'catalog_snapshot',
        'promo_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'catalog_snapshot' => 'array',
            'promo_snapshot' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'payment_id');
    }
}

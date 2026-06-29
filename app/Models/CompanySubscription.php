<?php

namespace App\Models;

use App\Support\SubscriptionProration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscription extends Model
{
    protected $appends = [
        'display_status',
    ];

    protected $fillable = [
        'company_id',
        'status',
        'starts_on',
        'ends_on',
        'trial_ends_on',
        'included_branches',
        'users_per_branch',
        'included_lr_credits',
        'included_trucks',
        'addon_branches',
        'addon_users',
        'addon_trucks',
        'addon_lr_credits',
        'lr_credits_remaining',
        'last_renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'trial_ends_on' => 'date',
            'last_renewed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function upcoming(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UpcomingSubscription::class, 'company_id', 'company_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active'], true)
            && $this->ends_on->toDateString() >= now()->toDateString();
    }

    public function displayStatus(): string
    {
        if ($this->isActive()) {
            return $this->status;
        }

        if (in_array($this->status, ['trial', 'active'], true)) {
            return 'expired';
        }

        return $this->status ?? 'none';
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->displayStatus();
    }

    public function remainingDaysInclusive(): int
    {
        return SubscriptionProration::remainingDaysInclusive($this->ends_on);
    }

    public function maxBranches(): int
    {
        return $this->included_branches + $this->addon_branches;
    }

    public function maxUsersInBranch(): int
    {
        return $this->users_per_branch;
    }

    public function maxCompanyUsers(): int
    {
        return ($this->maxBranches() * $this->users_per_branch) + $this->addon_users;
    }
}

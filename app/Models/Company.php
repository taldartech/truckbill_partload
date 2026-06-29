<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function subscription()
    {
        return $this->hasOne(CompanySubscription::class);
    }

    public function pendingUpcomingRenewals()
    {
        return $this->hasMany(UpcomingSubscription::class)->where('status', 'pending');
    }

    public function hasPendingUpcomingRenewal(): bool
    {
        return UpcomingSubscription::query()
            ->where('company_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function isPlanActive(): bool
    {
        if ($this->subscription?->isActive()) {
            return true;
        }

        return $this->hasPendingUpcomingRenewal();
    }

    public function planEndsOn(): ?\Illuminate\Support\Carbon
    {
        return $this->subscription?->ends_on;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

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
        return $this->subscription?->isActive() ?? false;
    }

    public function subscriptionAccessPayload(): array
    {
        return app(\App\Services\SubscriptionAccessService::class)->accessPayloadForCompany($this);
    }

    public function subscriptionCanWrite(): bool
    {
        return $this->subscriptionAccessPayload()['can_write'] ?? false;
    }

    public function subscriptionCanExport(): bool
    {
        return $this->subscriptionAccessPayload()['can_export'] ?? false;
    }

    public function subscriptionCanPrint(): bool
    {
        return $this->subscriptionAccessPayload()['can_print'] ?? false;
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

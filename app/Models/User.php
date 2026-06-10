<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'policy_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function onboarding()
    {
        return $this->hasOne(UserOnboarding::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        if (! $this->isSuperAdmin()) {
            return true;
        }

        return (bool) $this->onboarding?->isCompleted();
    }

    public function requiresOnboarding(): bool
    {
        return $this->isSuperAdmin() && ! $this->hasCompletedOnboarding();
    }

    public function onboardingStatusLabel(): string
    {
        if (! $this->requiresOnboarding()) {
            return 'Active';
        }

        if (! $this->email_verified_at) {
            return 'Pending Email Verification';
        }

        if (! $this->policy_accepted_at) {
            return 'Pending Policy Acceptance';
        }

        return 'Pending Password Setup';
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function ownedCompanies()
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    public function isMasterAdmin(): bool
    {
        return $this->hasRole(UserRole::MasterAdmin->value);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin->value);
    }

    public function requiresBranchAssignment(): bool
    {
        return $this->hasAnyRole(UserRole::requiresBranchAssignment());
    }
}

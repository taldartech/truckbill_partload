<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnboarding extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasActiveOtp(): bool
    {
        return $this->otp_hash
            && $this->otp_expires_at
            && $this->otp_expires_at->isFuture();
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SingleDeviceLoginService
{
    public function enforce(User $user, string $currentSessionId, bool $remember = false): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        if (! $remember) {
            $user->forceFill(['remember_token' => null])->save();
        }
    }
}

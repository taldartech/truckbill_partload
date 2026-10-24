<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SingleDeviceLoginService
{
    public function enforce(User $user, string $currentSessionId, bool $remember = false): void
    {
        // Playwright (and similar) run many browsers against one user; wiping sibling
        // sessions makes authenticated specs flap to /login under parallel load.
        if (config('session.allow_multi_device_login')) {
            return;
        }

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

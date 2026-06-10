<?php

namespace App\Traits;

use App\Models\Scopes\OwnerScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;

trait BelongsToOwner
{
    protected static function bootBelongsToOwner(): void
    {
        static::addGlobalScope(new OwnerScope);

        static::creating(function ($model) {
            if (! Auth::check()) {
                return;
            }

            $context = app(TenantContext::class);

            if (empty($model->owner_id) && $context->activeOwnerId()) {
                $model->owner_id = $context->activeOwnerId();
            }
        });
    }
}

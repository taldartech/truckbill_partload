<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OwnerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->shouldApplyOwnerScope()) {
            return;
        }

        $ownerId = $context->activeOwnerId();

        if ($ownerId) {
            $builder->where($model->getTable().'.owner_id', $ownerId);

            return;
        }

        $builder->whereRaw('0 = 1');
    }
}

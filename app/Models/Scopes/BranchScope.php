<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->shouldApplyBranchScope()) {
            return;
        }

        $branchId = $context->activeBranchId();

        if ($branchId) {
            $builder->where($model->getTable().'.branch_id', $branchId);
        }
    }
}

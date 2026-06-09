<?php

namespace App\Traits;

use App\Models\Scopes\BranchScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (! Auth::check()) {
                return;
            }

            $context = app(TenantContext::class);

            if (empty($model->branch_id) && $context->activeBranchId()) {
                $model->branch_id = $context->activeBranchId();
            }

            if (empty($model->company_id) && $context->activeCompanyId()) {
                $model->company_id = $context->activeCompanyId();
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}

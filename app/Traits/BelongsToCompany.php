<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (! Auth::check()) {
                return;
            }

            $context = app(TenantContext::class);

            if (empty($model->company_id) && $context->activeCompanyId()) {
                $model->company_id = $context->activeCompanyId();
            }
        });
    }
}

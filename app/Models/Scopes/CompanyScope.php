<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->shouldApplyCompanyScope()) {
            return;
        }

        $companyId = $context->activeCompanyId();

        if ($companyId) {
            $builder->where($model->getTable().'.company_id', $companyId);

            return;
        }

        $ownedCompanyIds = $context->ownedCompanyIds();

        if (! empty($ownedCompanyIds)) {
            $builder->whereIn($model->getTable().'.company_id', $ownedCompanyIds);

            return;
        }

        $builder->whereRaw('0 = 1');
    }
}

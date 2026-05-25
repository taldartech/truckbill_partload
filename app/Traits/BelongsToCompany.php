<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait for a model.
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->company_id) {
                // Let admins override company_id if they want, but if it's empty, set it.
                // For non-admins, always force their company_id
                if (!Auth::user()->hasRole(['master_admin', 'super_admin'])) {
                    $model->company_id = Auth::user()->company_id;
                } else if (empty($model->company_id)) {
                    $model->company_id = Auth::user()->company_id;
                }
            }
        });
    }
}

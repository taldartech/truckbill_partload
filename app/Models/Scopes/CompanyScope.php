<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->company_id) {
            // Bypass company scoping if user is an admin
            if (Auth::user()->hasRole(['master_admin', 'super_admin'])) {
                return;
            }
            
            $builder->where($model->getTable() . '.company_id', Auth::user()->company_id);
        }
    }
}

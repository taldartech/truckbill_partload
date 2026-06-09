<?php

namespace App\Providers;

use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            $context = app(TenantContext::class);

            if ($context->isActingAsAdmin() && $context->can($ability)) {
                return true;
            }

            return null;
        });
    }
}

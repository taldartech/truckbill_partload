<?php

namespace App\Providers;

use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::before(function ($user, $ability) {
            $context = app(TenantContext::class);

            if ($context->isActingAsAdmin() && $context->can($ability)) {
                return true;
            }

            return null;
        });
    }
}

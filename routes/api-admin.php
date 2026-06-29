<?php

use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Middleware\VerifyOnboardingSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin/subscriptions')
    ->middleware(['onboarding.signature'])
    ->group(function (): void {
        Route::get('/', [AdminSubscriptionController::class, 'index']);
        Route::get('/search', [AdminSubscriptionController::class, 'search']);
        Route::get('/{id}', [AdminSubscriptionController::class, 'show']);
        Route::patch('/{id}', [AdminSubscriptionController::class, 'update']);
    });

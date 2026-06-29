<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upcoming_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('subscription_payments')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('included_branches');
            $table->unsignedSmallInteger('users_per_branch');
            $table->unsignedInteger('included_lr_credits')->default(0);
            $table->unsignedInteger('included_trucks')->default(0);
            $table->unsignedSmallInteger('addon_branches')->default(0);
            $table->unsignedSmallInteger('addon_users')->default(0);
            $table->unsignedSmallInteger('addon_trucks')->default(0);
            $table->unsignedInteger('addon_lr_credits')->default(0);
            $table->string('status')->default('pending');
            $table->json('catalog_snapshot')->nullable();
            $table->json('promo_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upcoming_subscriptions');
    }
};

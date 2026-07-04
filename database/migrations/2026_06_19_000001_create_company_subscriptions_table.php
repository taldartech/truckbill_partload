<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('trial_ends_on')->nullable();
            $table->unsignedSmallInteger('included_branches')->default(2);
            $table->unsignedSmallInteger('users_per_branch')->default(3);
            $table->unsignedInteger('included_lr_credits')->default(0);
            $table->unsignedInteger('included_trucks')->default(0);
            $table->unsignedSmallInteger('addon_branches')->default(0);
            $table->unsignedSmallInteger('addon_users')->default(0);
            $table->unsignedSmallInteger('addon_trucks')->default(0);
            $table->unsignedInteger('addon_lr_credits')->default(0);
            $table->unsignedInteger('lr_credits_remaining')->default(0);
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_group_id')->constrained('account_groups')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('system_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_selectable')->default(true);
            $table->nullableMorphs('reference');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'system_key']);
            $table->index(['company_id', 'is_active', 'is_selectable']);
            $table->index(['company_id', 'account_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

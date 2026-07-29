<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->date('date');
            $table->string('voucher_type');
            $table->string('voucher_number')->nullable();
            $table->morphs('reference');
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->uuid('entry_group_uuid')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['ledger_account_id', 'date']);
            $table->index('entry_group_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};

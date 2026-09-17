<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('accounting_vouchers')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->text('narration')->nullable();
            $table->unsignedInteger('line_no')->default(1);
            $table->timestamps();

            $table->index(['account_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_voucher_entries');
    }
};

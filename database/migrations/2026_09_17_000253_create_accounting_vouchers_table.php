<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->string('voucher_type');
            $table->string('voucher_number');
            $table->date('date');
            $table->text('narration')->nullable();
            $table->string('status')->default('posted');
            $table->nullableMorphs('source');
            $table->foreignId('reversed_voucher_id')->nullable()->constrained('accounting_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'financial_year_id', 'voucher_type', 'voucher_number'], 'acct_vouchers_fy_type_number_unique');
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'voucher_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vouchers');
    }
};

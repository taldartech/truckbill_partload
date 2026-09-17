<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->string('voucher_type');
            $table->string('prefix');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['company_id', 'financial_year_id', 'voucher_type'], 'voucher_sequences_company_fy_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_sequences');
    }
};

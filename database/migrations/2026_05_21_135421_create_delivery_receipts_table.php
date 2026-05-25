<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('delivery_receipt_no')->nullable();
            $table->date('date')->nullable();
            $table->string('gr_no_bilty_no')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->string('office_name')->nullable();
            $table->foreignId('consignee_id')->constrained('parties')->cascadeOnDelete();
            $table->string('haste')->nullable();
            $table->string('payment_due_from')->nullable();
            $table->decimal('freight', 10, 2)->nullable();
            $table->decimal('handling_charge', 10, 2)->nullable();
            $table->decimal('risk_dd_charge', 10, 2)->nullable();
            $table->decimal('delivery_charge', 10, 2)->nullable();
            $table->decimal('stationary_charge', 10, 2)->nullable();
            $table->decimal('demurrage', 10, 2)->nullable();
            $table->decimal('other_charge', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('gst_on')->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->string('gst_pay_by')->nullable();
            $table->decimal('less', 10, 2)->nullable();
            $table->decimal('final_amount', 12, 2)->nullable();
            $table->decimal('cartage_charge', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_receipts');
    }
};

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
        Schema::create('bilties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('consignor_id')->constrained('parties')->onDelete('cascade');
            $table->foreignId('consignee_id')->constrained('parties')->onDelete('cascade');
            $table->foreignId('from_station_id')->constrained('stations')->onDelete('cascade');
            $table->foreignId('to_station_id')->constrained('stations')->onDelete('cascade');
            $table->string('bilty_number')->nullable();
            $table->date('date')->nullable();
            $table->decimal('freight', 10, 2)->nullable();
            $table->decimal('bilty_charge', 10, 2)->nullable();
            $table->decimal('surcharge', 10, 2)->nullable();
            $table->decimal('other_charge', 10, 2)->nullable();
            $table->decimal('handling_charge', 10, 2)->nullable();
            $table->decimal('risk_dd_charge', 10, 2)->nullable();
            $table->decimal('cartage', 10, 2)->nullable();
            $table->string('cartage_type')->nullable(); // cash/due
            $table->string('cartage_due_with_whom')->nullable();
            $table->decimal('less_advance_received', 10, 2)->nullable();
            $table->decimal('previous_freight', 10, 2)->nullable();
            $table->string('previous_freight_no')->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->string('gst_liability_by')->nullable();
            $table->decimal('weight_actual', 10, 2)->nullable();
            $table->text('remark')->nullable();
            $table->decimal('declared_value', 15, 2)->nullable();
            $table->string('payment_type')->nullable(); // Paid / To Pay / TBB / FOC
            $table->decimal('advance_payment_received', 10, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->decimal('amount', 10, 2)->nullable(); // total amount
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilties');
    }
};

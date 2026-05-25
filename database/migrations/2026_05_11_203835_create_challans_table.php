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
        Schema::create('challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_station_id')->constrained('stations')->onDelete('cascade');
            $table->foreignId('to_station_id')->constrained('stations')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('truck_id')->constrained()->onDelete('cascade');
            $table->string('challan_number')->nullable();
            $table->date('date')->nullable();
            $table->decimal('labour_charge', 10, 2)->nullable();
            $table->decimal('kat', 10, 2)->nullable();
            $table->decimal('home_delivery_charge', 10, 2)->nullable();
            $table->decimal('other_charge', 10, 2)->nullable();
            $table->boolean('add_to_main_challan')->default(false);
            $table->decimal('truck_freight', 10, 2)->nullable();
            $table->decimal('cash_received', 10, 2)->nullable();
            $table->decimal('cash_payment', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('pay_by')->nullable();
            $table->string('delivery_at')->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->integer('total_packages')->nullable();
            $table->decimal('to_pay_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable(); // paid amount/advance amount
            $table->decimal('crossing_freight', 10, 2)->nullable();
            $table->decimal('cartage_dalali_pf', 10, 2)->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->text('remark')->nullable();
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
        Schema::dropIfExists('challans');
    }
};

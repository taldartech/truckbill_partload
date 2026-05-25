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
        Schema::create('main_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('main_challan_number')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('from_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('to_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->integer('total_packages')->nullable();
            $table->decimal('to_pay_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->decimal('crossing_freight', 10, 2)->nullable();
            $table->decimal('cartage_dalali_pf', 10, 2)->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->decimal('labour_charge', 10, 2)->nullable();
            $table->decimal('kat', 10, 2)->nullable();
            $table->decimal('home_delivery_charge', 10, 2)->nullable();
            $table->decimal('other_charge', 10, 2)->nullable();
            $table->decimal('truck_freight', 10, 2)->nullable();
            $table->decimal('cash_received', 10, 2)->nullable();
            $table->decimal('cash_paid', 10, 2)->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_challans');
    }
};

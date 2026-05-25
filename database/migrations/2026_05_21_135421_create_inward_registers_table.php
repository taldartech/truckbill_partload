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
        Schema::create('inward_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('inward_register_no')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('from_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('to_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();
            $table->date('challan_date')->nullable();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->string('goods_ready_no')->nullable();
            $table->date('goods_ready_date')->nullable();
            $table->foreignId('good_id')->constrained('goods')->cascadeOnDelete();
            // User requested from_station_id and to_station_id again, assuming they meant for the goods? We have them above.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inward_registers');
    }
};

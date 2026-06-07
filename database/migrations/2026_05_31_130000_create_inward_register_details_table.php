<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inward_register_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inward_register_id')->constrained('inward_registers')->cascadeOnDelete();
            $table->string('gr_no')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('from_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->foreignId('to_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->foreignId('consignor_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('consignee_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('good_id')->nullable()->constrained('goods')->nullOnDelete();
            $table->integer('no_of_packages')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('basis_of_booking')->nullable();
            $table->decimal('freight', 12, 2)->nullable();
            $table->decimal('home_delivery_charge', 12, 2)->nullable();
            $table->string('private_marka')->nullable();
            $table->foreignId('transporter_id')->nullable()->constrained('transporters')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inward_register_details');
    }
};

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
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('transporter_id')->nullable()->constrained()->onDelete('set null');
            $table->string('truck_number')->nullable();
            $table->foreignId('truck_type_id')->nullable()->constrained('truck_types')->nullOnDelete();
            $table->string('capacity')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_mobile')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_city')->nullable();
            $table->string('owner_state')->nullable();
            $table->string('rc_no')->nullable();
            $table->date('rc_expiry_date')->nullable();
            $table->string('insurance_no')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('fitness_no')->nullable();
            $table->date('fitness_expiry_date')->nullable();
            $table->string('pollution_no')->nullable();
            $table->date('pollution_expiry_date')->nullable();
            $table->string('permit_no')->nullable();
            $table->date('permit_expiry_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};

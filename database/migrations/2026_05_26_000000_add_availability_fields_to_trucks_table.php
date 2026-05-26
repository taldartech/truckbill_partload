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
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('status')->default('Available');
            $table->foreignId('current_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('current_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('current_station_id')->nullable()->constrained('stations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropForeign(['current_state_id']);
            $table->dropForeign(['current_city_id']);
            $table->dropForeign(['current_station_id']);
            $table->dropColumn(['status', 'current_state_id', 'current_city_id', 'current_station_id']);
        });
    }
};

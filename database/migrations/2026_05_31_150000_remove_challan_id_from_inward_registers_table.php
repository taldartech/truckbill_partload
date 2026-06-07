<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inward_registers', function (Blueprint $table) {
            $table->dropForeign(['challan_id']);
            $table->dropColumn('challan_id');
        });
    }

    public function down(): void
    {
        Schema::table('inward_registers', function (Blueprint $table) {
            $table->foreignId('challan_id')->nullable()->after('to_station_id')->constrained('challans')->nullOnDelete();
        });
    }
};

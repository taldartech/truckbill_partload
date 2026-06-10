<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inward_register_details', function (Blueprint $table) {
            $table->string('disposition', 20)->default('forward')->after('transporter_id');
            $table->string('status', 20)->default('pending')->after('disposition');
        });

        DB::table('inward_register_details')
            ->whereNotNull('to_station_id')
            ->update(['disposition' => 'forward']);
    }

    public function down(): void
    {
        Schema::table('inward_register_details', function (Blueprint $table) {
            $table->dropColumn(['disposition', 'status']);
        });
    }
};

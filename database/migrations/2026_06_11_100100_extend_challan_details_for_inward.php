<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challan_details', function (Blueprint $table) {
            $table->dropForeign(['bilty_id']);
        });

        Schema::table('challan_details', function (Blueprint $table) {
            $table->unsignedBigInteger('bilty_id')->nullable()->change();
            $table->foreignId('inward_register_detail_id')
                ->nullable()
                ->after('bilty_id')
                ->constrained('inward_register_details')
                ->nullOnDelete();
            $table->unsignedBigInteger('to_station_id')->nullable()->change();
        });

        Schema::table('challan_details', function (Blueprint $table) {
            $table->foreign('bilty_id')->references('id')->on('bilties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('challan_details', function (Blueprint $table) {
            $table->dropForeign(['inward_register_detail_id']);
            $table->dropColumn('inward_register_detail_id');
        });

        Schema::table('challan_details', function (Blueprint $table) {
            $table->dropForeign(['bilty_id']);
        });

        Schema::table('challan_details', function (Blueprint $table) {
            $table->unsignedBigInteger('bilty_id')->nullable(false)->change();
            $table->unsignedBigInteger('to_station_id')->nullable(false)->change();
        });

        Schema::table('challan_details', function (Blueprint $table) {
            $table->foreign('bilty_id')->references('id')->on('bilties')->cascadeOnDelete();
        });
    }
};

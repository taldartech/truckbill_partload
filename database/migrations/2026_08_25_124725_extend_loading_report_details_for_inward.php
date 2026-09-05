<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->dropForeign(['bilty_id']);
            $table->dropForeign(['package_type_id']);
        });

        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->unsignedBigInteger('bilty_id')->nullable()->change();
            $table->unsignedBigInteger('package_type_id')->nullable()->change();
            $table->foreignId('inward_register_detail_id')
                ->nullable()
                ->after('bilty_id')
                ->constrained('inward_register_details')
                ->nullOnDelete();
        });

        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->foreign('bilty_id')->references('id')->on('bilties')->nullOnDelete();
            $table->foreign('package_type_id')->references('id')->on('package_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->dropForeign(['inward_register_detail_id']);
            $table->dropColumn('inward_register_detail_id');
        });

        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->dropForeign(['bilty_id']);
            $table->dropForeign(['package_type_id']);
        });

        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->unsignedBigInteger('bilty_id')->nullable(false)->change();
            $table->unsignedBigInteger('package_type_id')->nullable(false)->change();
        });

        Schema::table('loading_report_details', function (Blueprint $table) {
            $table->foreign('bilty_id')->references('id')->on('bilties')->cascadeOnDelete();
            $table->foreign('package_type_id')->references('id')->on('package_types')->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('truck_id');
        });

        Schema::table('main_challans', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('truck_id');
        });

        Schema::table('loading_reports', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('truck_id');
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('truck_id');
        });

        DB::statement('UPDATE challans c LEFT JOIN drivers d ON d.id = c.driver_id SET c.driver_name = d.name WHERE c.driver_id IS NOT NULL');
        DB::statement('UPDATE main_challans c LEFT JOIN drivers d ON d.id = c.driver_id SET c.driver_name = d.name WHERE c.driver_id IS NOT NULL');
        DB::statement('UPDATE loading_reports r LEFT JOIN drivers d ON d.id = r.driver_id SET r.driver_name = d.name WHERE r.driver_id IS NOT NULL');
        DB::statement('UPDATE inward_registers r LEFT JOIN drivers d ON d.id = r.driver_id SET r.driver_name = d.name WHERE r.driver_id IS NOT NULL');

        Schema::table('challans', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::table('main_challans', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::table('loading_reports', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::dropIfExists('drivers');
    }

    public function down(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('license_number')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('aadhar_no')->nullable();
            $table->string('pan_no')->nullable();
            $table->string('rto_name')->nullable();
            $table->string('rto_state')->nullable();
            $table->string('license_verification')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('challans', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->after('truck_id');
        });

        Schema::table('main_challans', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->after('truck_id');
        });

        Schema::table('loading_reports', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->after('truck_id');
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->after('truck_id');
        });

        Schema::table('challans', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });

        Schema::table('main_challans', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });

        Schema::table('loading_reports', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });
    }
};

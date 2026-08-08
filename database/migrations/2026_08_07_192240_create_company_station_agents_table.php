<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_station_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'station_id']);
        });

        if (Schema::hasColumn('stations', 'agency_id')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stations', 'agency_id')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('city_id')->constrained('agents')->nullOnDelete();
            });
        }

        Schema::dropIfExists('company_station_agents');
    }
};

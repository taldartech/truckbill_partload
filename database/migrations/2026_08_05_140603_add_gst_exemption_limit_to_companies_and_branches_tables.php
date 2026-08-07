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
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('gst_exemption_limit', 12, 2)->nullable()->after('gst_slab');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('gst_exemption_limit', 12, 2)->nullable()->after('gst_slab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('gst_exemption_limit');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('gst_exemption_limit');
        });
    }
};

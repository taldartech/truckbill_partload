<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'inward_register_initial')) {
                $table->string('inward_register_initial')->nullable()->after('invoice_initial');
            }
        });

        DB::table('branches')
            ->whereNull('inward_register_initial')
            ->update(['inward_register_initial' => 'IR-']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'inward_register_initial')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('inward_register_initial');
            });
        }
    }
};

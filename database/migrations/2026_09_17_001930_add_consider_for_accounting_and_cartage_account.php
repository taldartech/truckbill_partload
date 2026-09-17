<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->boolean('consider_for_accounting')->default(false)->after('bilty_charge');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('consider_for_accounting')->default(false)->after('transport_id');
        });

        Schema::table('bilties', function (Blueprint $table) {
            $table->foreignId('cartage_account_id')->nullable()->after('vendor_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cartage_account_id');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('consider_for_accounting');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn('consider_for_accounting');
        });
    }
};

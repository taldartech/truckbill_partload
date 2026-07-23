<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('delivery_receipts')
            ->where('mode_of_payment', 'To Pay')
            ->update(['mode_of_payment' => 'Due']);

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->dropColumn('office_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->string('office_name')->nullable()->after('mode_of_payment');
        });

        DB::table('delivery_receipts')
            ->where('mode_of_payment', 'Due')
            ->update(['mode_of_payment' => 'To Pay']);
    }
};

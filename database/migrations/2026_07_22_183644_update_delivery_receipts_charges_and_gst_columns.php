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
        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->dropColumn('risk_dd_charge');
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->renameColumn('stationary_charge', 'stationery_charge');
            $table->renameColumn('cartage_charge', 'carriage_charge');
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->nullable()->after('gst_on');
            $table->foreignId('payment_due_from_id')
                ->nullable()
                ->after('haste')
                ->constrained('parties')
                ->nullOnDelete();
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->dropColumn('payment_due_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->string('payment_due_from')->nullable()->after('haste');
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_due_from_id');
            $table->dropColumn('gst_rate');
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->renameColumn('stationery_charge', 'stationary_charge');
            $table->renameColumn('carriage_charge', 'cartage_charge');
        });

        Schema::table('delivery_receipts', function (Blueprint $table) {
            $table->decimal('risk_dd_charge', 10, 2)->nullable()->after('handling_charge');
        });
    }
};

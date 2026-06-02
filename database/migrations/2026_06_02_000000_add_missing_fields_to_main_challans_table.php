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
        Schema::table('main_challans', function (Blueprint $table) {
            $table->string('pay_by')->nullable()->after('cash_paid');
            $table->decimal('total_amount', 12, 2)->nullable()->after('pay_by');
            $table->string('total_balance_type', 10)->nullable()->after('total_amount');
            $table->string('status')->nullable()->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_challans', function (Blueprint $table) {
            $table->dropColumn(['pay_by', 'total_amount', 'total_balance_type', 'status']);
        });
    }
};

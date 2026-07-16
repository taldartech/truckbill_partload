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
        DB::table('bilties')->whereIn('cartage_type', ['Paid', 'paid'])->update(['cartage_type' => 'Cash']);

        Schema::table('bilties', function (Blueprint $table) {
            $table->dropColumn('other_charge');
            $table->dropColumn('cartage_cash');
            $table->dropColumn('cartage_due_with_whom');
            $table->dropColumn('previous_freight');
            $table->dropColumn('previous_freight_no');
            $table->dropColumn('remark');
            $table->dropColumn('advance_payment_received');
            $table->dropColumn('payment_mode');
            $table->dropColumn('eway_bill_no');
            $table->dropColumn('eway_bill_expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->decimal('other_charge', 10, 2)->nullable();
            $table->decimal('cartage_cash', 10, 2)->nullable()->after('cartage');
            $table->string('cartage_due_with_whom')->nullable();
            $table->decimal('previous_freight', 10, 2)->nullable();
            $table->string('previous_freight_no')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('advance_payment_received', 10, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('eway_bill_no')->nullable();
            $table->date('eway_bill_expiry_date')->nullable();
        });
    }
};

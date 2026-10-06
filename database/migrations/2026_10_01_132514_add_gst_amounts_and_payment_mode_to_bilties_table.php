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
        Schema::table('bilties', function (Blueprint $table) {
            $table->decimal('charges_total', 12, 2)->default(0)->after('amount');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('charges_total');
            $table->decimal('cgst_amount', 12, 2)->default(0)->after('gst_amount');
            $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 12, 2)->default(0)->after('sgst_amount');
            $table->string('payment_mode', 10)->nullable()->after('payment_type');
            $table->foreignId('bank_account_id')->nullable()->after('payment_mode')->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('advance_bank_account_id')->nullable()->after('advance_payment_mode')->constrained('bank_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_bank_account_id');
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn(['charges_total', 'gst_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'payment_mode']);
        });
    }
};

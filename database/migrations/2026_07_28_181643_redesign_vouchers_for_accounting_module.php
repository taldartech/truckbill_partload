<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->nullableMorphs('accountable');
            $table->string('mode')->nullable()->after('amount'); // cash|bank
            $table->foreignId('bank_account_id')->nullable()->after('mode')->constrained('bank_accounts')->nullOnDelete();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'account_head')) {
                $table->dropColumn('account_head');
            }
            if (Schema::hasColumn('vouchers', 'debit_credit')) {
                $table->dropColumn('debit_credit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropMorphs('accountable');
            $table->dropColumn('mode');
            $table->string('account_head')->nullable();
            $table->string('debit_credit')->nullable();
        });
    }
};

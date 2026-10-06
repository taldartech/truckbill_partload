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
        Schema::table('accounting_vouchers', function (Blueprint $table) {
            $table->string('event_key', 100)->nullable()->after('source_id');
            $table->foreignId('reversed_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->text('cancel_reason')->nullable()->after('reversed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['event_key', 'reversed_at', 'cancel_reason']);
        });
    }
};

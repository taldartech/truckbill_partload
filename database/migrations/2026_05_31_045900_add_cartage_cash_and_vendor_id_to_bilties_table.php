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
            $table->decimal('cartage_cash', 10, 2)->nullable()->after('cartage');
            $table->foreignId('vendor_id')->nullable()->constrained('parties')->nullOnDelete()->after('cartage_due_with_whom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['cartage_cash', 'vendor_id']);
        });
    }
};

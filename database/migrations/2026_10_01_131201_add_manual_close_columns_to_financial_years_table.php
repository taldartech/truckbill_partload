<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Years were previously closed automatically once their end date passed;
     * closing is now a manual admin action, so those automatic closures are lifted.
     */
    public function up(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            $table->foreignId('closed_by')->nullable()->after('is_closed')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by');
        });

        DB::table('financial_years')->where('is_closed', true)->update(['is_closed' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('closed_at');
        });
    }
};

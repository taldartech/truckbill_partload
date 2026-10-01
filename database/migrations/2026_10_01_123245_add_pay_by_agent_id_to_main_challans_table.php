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
            $table->foreignId('pay_by_agent_id')->nullable()->after('cash_paid')->constrained('agents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_challans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pay_by_agent_id');
        });
    }
};

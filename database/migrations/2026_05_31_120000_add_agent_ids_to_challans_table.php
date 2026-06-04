<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->foreignId('pay_by_agent_id')->nullable()->after('pay_by')->constrained('agents')->nullOnDelete();
            $table->foreignId('delivery_at_agent_id')->nullable()->after('delivery_at')->constrained('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->dropForeign(['pay_by_agent_id']);
            $table->dropForeign(['delivery_at_agent_id']);
            $table->dropColumn(['pay_by_agent_id', 'delivery_at_agent_id']);
        });
    }
};

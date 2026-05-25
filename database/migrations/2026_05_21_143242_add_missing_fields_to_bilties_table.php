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
            $table->string('private_marka')->nullable();
            $table->string('delivery_at')->nullable();
            $table->string('eway_bill_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->dropColumn(['private_marka', 'delivery_at', 'eway_bill_no']);
        });
    }
};

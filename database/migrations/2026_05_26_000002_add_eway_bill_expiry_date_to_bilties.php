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
            $table->date('eway_bill_expiry_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilties', function (Blueprint $table) {
            $table->dropColumn('eway_bill_expiry_date');
        });
    }
};

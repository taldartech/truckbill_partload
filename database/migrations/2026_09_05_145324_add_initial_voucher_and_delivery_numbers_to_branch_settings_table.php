<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->string('voucher_initial')->nullable()->after('inward_register_initial');
            $table->string('delivery_initial')->nullable()->after('voucher_initial');
        });
    }

    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['voucher_initial', 'delivery_initial']);
        });
    }
};

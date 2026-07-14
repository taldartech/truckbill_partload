<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['type', 'mobile_sms']);
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
            $table->string('mobile_sms')->nullable()->after('mobile');
        });
    }
};

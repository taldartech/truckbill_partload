<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->decimal('booking_freight', 12, 2)->default(0)->after('crossing_freight');
        });
    }

    public function down(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->dropColumn('booking_freight');
        });
    }
};

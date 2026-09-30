<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('bilty_charge_type', 20)->default('fixed')->after('bilty_charge');
            $table->decimal('surcharge', 10, 2)->nullable()->after('bilty_charge_type');
            $table->string('surcharge_type', 20)->default('fixed')->after('surcharge');
            $table->decimal('handling_charge', 10, 2)->nullable()->after('surcharge_type');
            $table->string('handling_charge_type', 20)->default('fixed')->after('handling_charge');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'bilty_charge_type',
                'surcharge',
                'surcharge_type',
                'handling_charge',
                'handling_charge_type',
            ]);
        });
    }
};

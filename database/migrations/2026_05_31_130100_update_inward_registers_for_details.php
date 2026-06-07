<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inward_registers', function (Blueprint $table) {
            $table->integer('total_packages')->nullable()->after('challan_date');
            $table->decimal('total_delivery_freight', 12, 2)->nullable()->after('total_packages');
            $table->decimal('total_crossing_freight', 12, 2)->nullable()->after('total_delivery_freight');
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->dropForeign(['good_id']);
            $table->dropColumn(['goods_ready_no', 'goods_ready_date', 'good_id']);
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->change();
            $table->foreignId('truck_id')->nullable()->change();
            $table->foreignId('challan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inward_registers', function (Blueprint $table) {
            $table->foreignId('challan_id')->nullable(false)->change();
            $table->foreignId('driver_id')->nullable(false)->change();
            $table->foreignId('truck_id')->nullable(false)->change();
        });

        Schema::table('inward_registers', function (Blueprint $table) {
            $table->string('goods_ready_no')->nullable();
            $table->date('goods_ready_date')->nullable();
            $table->foreignId('good_id')->nullable()->constrained('goods')->cascadeOnDelete();
            $table->dropColumn(['total_packages', 'total_delivery_freight', 'total_crossing_freight']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        if (Schema::hasColumn('subscription_payments', 'subtotal')) {
            return;
        }

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('quantities');
            $table->decimal('promo_discount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('promo_discount');
            $table->decimal('total_amount', 12, 2)->nullable()->after('gst_amount');
        });

        if (Schema::hasColumn('subscription_payments', 'subtotal_paise')) {
            DB::table('subscription_payments')->orderBy('id')->each(function ($row) {
                DB::table('subscription_payments')->where('id', $row->id)->update([
                    'subtotal' => round($row->subtotal_paise / 100, 2),
                    'promo_discount' => round($row->promo_discount_paise / 100, 2),
                    'gst_amount' => round($row->gst_amount_paise / 100, 2),
                    'total_amount' => round($row->total_amount_paise / 100, 2),
                ]);
            });

            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->dropColumn(['subtotal_paise', 'promo_discount_paise', 'gst_amount_paise', 'total_amount_paise']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        if (! Schema::hasColumn('subscription_payments', 'subtotal')) {
            return;
        }

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal_paise')->nullable()->after('quantities');
            $table->unsignedBigInteger('promo_discount_paise')->default(0)->after('subtotal_paise');
            $table->unsignedBigInteger('gst_amount_paise')->default(0)->after('promo_discount_paise');
            $table->unsignedBigInteger('total_amount_paise')->nullable()->after('gst_amount_paise');
        });

        DB::table('subscription_payments')->orderBy('id')->each(function ($row) {
            DB::table('subscription_payments')->where('id', $row->id)->update([
                'subtotal_paise' => (int) round(((float) $row->subtotal) * 100),
                'promo_discount_paise' => (int) round(((float) $row->promo_discount) * 100),
                'gst_amount_paise' => (int) round(((float) $row->gst_amount) * 100),
                'total_amount_paise' => (int) round(((float) $row->total_amount) * 100),
            ]);
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'promo_discount', 'gst_amount', 'total_amount']);
        });
    }
};

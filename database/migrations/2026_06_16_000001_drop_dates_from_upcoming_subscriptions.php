<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('upcoming_subscriptions')) {
            return;
        }

        Schema::table('upcoming_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('upcoming_subscriptions', 'starts_on')) {
                $table->dropColumn('starts_on');
            }
            if (Schema::hasColumn('upcoming_subscriptions', 'ends_on')) {
                $table->dropColumn('ends_on');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('upcoming_subscriptions')) {
            return;
        }

        Schema::table('upcoming_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('upcoming_subscriptions', 'starts_on')) {
                $table->date('starts_on')->nullable();
            }
            if (! Schema::hasColumn('upcoming_subscriptions', 'ends_on')) {
                $table->date('ends_on')->nullable();
            }
        });
    }
};

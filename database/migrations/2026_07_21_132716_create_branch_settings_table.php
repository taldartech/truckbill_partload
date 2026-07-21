<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('bilty_initial')->nullable();
            $table->string('challan_initial')->nullable();
            $table->string('main_challan_initial')->nullable();
            $table->string('invoice_initial')->nullable();
            $table->string('inward_register_initial')->nullable();
            $table->json('bilty_print_config')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('branches', 'bilty_initial')) {
            $branches = DB::table('branches')->get([
                'id',
                'bilty_initial',
                'challan_initial',
                'main_challan_initial',
                'invoice_initial',
                'inward_register_initial',
                'bilty_print_config',
            ]);

            foreach ($branches as $branch) {
                DB::table('branch_settings')->insert([
                    'branch_id' => $branch->id,
                    'bilty_initial' => $branch->bilty_initial,
                    'challan_initial' => $branch->challan_initial,
                    'main_challan_initial' => $branch->main_challan_initial,
                    'invoice_initial' => $branch->invoice_initial,
                    'inward_register_initial' => $branch->inward_register_initial,
                    'bilty_print_config' => $branch->bilty_print_config,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn([
                    'bilty_initial',
                    'challan_initial',
                    'main_challan_initial',
                    'invoice_initial',
                    'inward_register_initial',
                    'bilty_print_config',
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'bilty_initial')) {
                $table->string('bilty_initial')->nullable()->after('email');
                $table->string('challan_initial')->nullable()->after('bilty_initial');
                $table->string('main_challan_initial')->nullable()->after('challan_initial');
                $table->string('invoice_initial')->nullable()->after('main_challan_initial');
                $table->string('inward_register_initial')->nullable()->after('invoice_initial');
                $table->json('bilty_print_config')->nullable()->after('inward_register_initial');
            }
        });

        if (Schema::hasTable('branch_settings')) {
            $settings = DB::table('branch_settings')->get();

            foreach ($settings as $setting) {
                DB::table('branches')
                    ->where('id', $setting->branch_id)
                    ->update([
                        'bilty_initial' => $setting->bilty_initial,
                        'challan_initial' => $setting->challan_initial,
                        'main_challan_initial' => $setting->main_challan_initial,
                        'invoice_initial' => $setting->invoice_initial,
                        'inward_register_initial' => $setting->inward_register_initial,
                        'bilty_print_config' => $setting->bilty_print_config,
                    ]);
            }
        }

        Schema::dropIfExists('branch_settings');
    }
};

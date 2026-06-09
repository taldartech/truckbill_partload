<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'branch_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            $branchId = DB::table('branches')
                ->where('company_id', $companyId)
                ->where('is_head_office', true)
                ->value('id');

            if (! $branchId) {
                $branchId = DB::table('branches')
                    ->where('company_id', $companyId)
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $branchId) {
                continue;
            }

            DB::table('settings')
                ->where('company_id', $companyId)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->index('company_id', 'settings_company_id_index');
            $table->dropUnique('settings_company_id_key_unique');
            $table->unique(['company_id', 'branch_id', 'key'], 'settings_company_branch_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_branch_key_unique');
            $table->dropIndex('settings_company_id_index');
            $table->unique(['company_id', 'key'], 'settings_company_id_key_unique');
        });
    }
};

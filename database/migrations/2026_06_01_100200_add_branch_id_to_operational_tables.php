<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'agents', 'bilties', 'bilty_details', 'bilty_eway_bills', 'challans', 'challan_details',
        'delivery_receipts', 'drivers', 'groups', 'inward_register_details', 'inward_registers',
        'invoices', 'loading_report_details', 'loading_reports', 'main_challan_details', 'main_challans',
        'parties', 'rate_lists', 'settings', 'transporters', 'trucks',
        'vendors', 'vouchers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        $companies = DB::table('companies')->get(['id', 'name']);

        foreach ($companies as $company) {
            $branchId = DB::table('branches')
                ->where('company_id', $company->id)
                ->where('is_head_office', true)
                ->value('id');

            if (! $branchId) {
                $branchId = DB::table('branches')
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $branchId) {
                $branchId = DB::table('branches')->insertGetId([
                    'company_id' => $company->id,
                    'name' => 'Head Office',
                    'is_head_office' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($this->tables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                    continue;
                }

                DB::table($table)
                    ->where('company_id', $company->id)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $branchId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};

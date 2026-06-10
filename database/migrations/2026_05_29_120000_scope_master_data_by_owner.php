<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'stations',
        'goods',
        'package_types',
        'truck_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            });
        }

        foreach ($this->tables as $table) {
            DB::statement("
                UPDATE {$table}
                SET owner_id = (
                    SELECT companies.user_id
                    FROM companies
                    WHERE companies.id = {$table}.company_id
                )
                WHERE company_id IS NOT NULL
            ");
        }

        $fallbackOwnerId = DB::table('companies')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');

        if ($fallbackOwnerId) {
            foreach ($this->tables as $table) {
                DB::table($table)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $fallbackOwnerId]);
            }
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
                $table->unsignedBigInteger('owner_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $fallbackCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if ($fallbackCompanyId) {
            foreach ($this->tables as $table) {
                DB::table($table)->update(['company_id' => $fallbackCompanyId]);
            }
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['owner_id']);
                $table->dropColumn('owner_id');
                $table->unsignedBigInteger('company_id')->nullable(false)->change();
            });
        }
    }
};

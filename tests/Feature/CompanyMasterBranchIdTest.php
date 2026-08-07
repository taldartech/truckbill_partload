<?php

namespace Tests\Feature;

use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyMasterBranchIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_master_tables_do_not_have_branch_id(): void
    {
        foreach (RolePermissions::companyMasterDataModules() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
            $this->assertFalse(
                Schema::hasColumn($table, 'branch_id'),
                "Company master [{$table}] should not have branch_id."
            );
        }
    }

    public function test_document_tables_still_have_branch_id(): void
    {
        foreach (['bilties', 'challans', 'delivery_receipts', 'vouchers', 'invoices'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'branch_id'), "Document [{$table}] should keep branch_id.");
        }
    }
}

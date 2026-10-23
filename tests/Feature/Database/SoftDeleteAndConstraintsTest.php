<?php

namespace Tests\Feature\Database;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class SoftDeleteAndConstraintsTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_soft_deleted_party_is_hidden_from_default_queries(): void
    {
        $context = $this->createTenantContext(['delete parties', 'view parties']);
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Soft Delete Me',
        ]);

        $party->delete();

        $this->assertSoftDeleted('parties', ['id' => $party->id]);
        $this->assertNull(Party::query()->find($party->id));
        $this->assertNotNull(Party::withTrashed()->find($party->id));
    }

    public function test_refresh_database_rolls_back_between_tests_isolation(): void
    {
        $this->assertSame(0, DB::table('parties')->count());
    }

    public function test_company_master_tables_do_not_require_branch_id_on_parties(): void
    {
        $context = $this->createTenantContext();
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'No Branch Party',
        ]);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'company_id' => $context['company']->id,
        ]);
        $this->assertFalse(Schema::hasColumn('parties', 'branch_id'));
    }
}

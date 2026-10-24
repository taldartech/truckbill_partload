<?php

namespace Tests\Feature\Database;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class TransactionRollbackTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_failed_transaction_does_not_persist_partial_party_writes(): void
    {
        $context = $this->createTenantContext();
        $before = Party::query()->count();

        try {
            DB::transaction(function () use ($context) {
                Party::create([
                    'company_id' => $context['company']->id,
                    'name' => 'Should Roll Back',
                ]);

                throw new RuntimeException('force rollback');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame($before, Party::query()->count());
        $this->assertDatabaseMissing('parties', ['name' => 'Should Roll Back']);
    }
}

<?php

namespace Tests\Feature\Ops;

use App\Http\Controllers\Concerns\ResolvesTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ResolvesTenantTraitTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_branch_id_resolves_from_active_user_branch(): void
    {
        $context = $this->createTenantContext();
        $this->actingAs($context['user']);

        $resolver = new class
        {
            use ResolvesTenant;

            public function resolvedBranchId(): int
            {
                return $this->branchId();
            }
        };

        $this->assertSame($context['branch']->id, $resolver->resolvedBranchId());
    }
}

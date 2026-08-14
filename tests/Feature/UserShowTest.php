<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_user_show(): void
    {
        Permission::findOrCreate('view users');

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
        ]);

        $this->createActiveSubscription($company);

        $viewer = User::factory()->create(['company_id' => $company->id]);
        $viewer->givePermissionTo('view users');

        $target = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Target User',
            'email' => 'target@example.com',
        ]);

        $response = $this->actingAs($viewer)->get(route('users.show', $target));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Users/Show')
            ->where('user.id', $target->id)
            ->where('user.name', 'Target User')
            ->where('user.email', 'target@example.com')
            ->where('user.company.name', 'Show Co')
            ->missing('user.password')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_user_show(): void
    {
        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
        ]);

        $this->createActiveSubscription($company);

        $viewer = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($viewer)->get(route('users.show', $target));

        $response->assertForbidden();
    }

    public function test_user_cannot_view_user_outside_company_scope(): void
    {
        Permission::findOrCreate('view users');

        $ownerA = User::factory()->create();
        $companyA = Company::create([
            'name' => 'Company A',
            'user_id' => $ownerA->id,
        ]);
        $this->createActiveSubscription($companyA);

        $ownerB = User::factory()->create();
        $companyB = Company::create([
            'name' => 'Company B',
            'user_id' => $ownerB->id,
        ]);
        $this->createActiveSubscription($companyB);

        $viewer = User::factory()->create(['company_id' => $companyA->id]);
        $viewer->givePermissionTo('view users');

        $target = User::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($viewer)->get(route('users.show', $target));

        $response->assertForbidden();
    }

    private function createActiveSubscription(Company $company): void
    {
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);
    }
}

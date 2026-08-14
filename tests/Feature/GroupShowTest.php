<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GroupShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_group_show(): void
    {
        $context = $this->createShowContext(['view groups']);

        $response = $this->actingAs($context['user'])
            ->get(route('groups.show', $context['group']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Groups/Show')
            ->where('group.id', $context['group']->id)
            ->where('group.name', 'Show Group')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_group_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('groups.show', $context['group']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, group: Group}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
        ]);

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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $group = Group::create(['company_id' => $company->id, 'name' => 'Show Group']);

        return [
            'user' => $user,
            'group' => $group,
        ];
    }
}

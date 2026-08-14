<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\TruckType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TruckTypeShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_truck_type_show(): void
    {
        $context = $this->createShowContext(['view truck_types']);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-types.show', $context['truckType']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TruckTypes/Show')
            ->where('truckType.id', $context['truckType']->id)
            ->where('truckType.name', 'Show Truck Type')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_truck_type_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-types.show', $context['truckType']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, truckType: TruckType}
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

        $truckType = TruckType::create(['owner_id' => $owner->id, 'name' => 'Show Truck Type']);

        return [
            'user' => $user,
            'truckType' => $truckType,
        ];
    }
}

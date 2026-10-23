<?php

namespace Tests\Feature\Ops;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BranchControllerExtrasTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Second Branch',
            'address' => '2 Test Street',
            'country_id' => $context['country']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'pin_code' => '313001',
            'station_id' => $context['station']->id,
            'contact_person' => 'Manager',
            'contact_number' => '9000000001',
            'email' => 'second@example.com',
            'is_head_office' => false,
            'has_gst' => false,
            'use_company_logo' => true,
        ], $overrides);
    }

    public function test_company_user_sees_only_their_company_branches_on_index(): void
    {
        $context = $this->createTenantContext(['view branches']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Branches/Index')
                ->has('branches.data', 1)
                ->where('branches.data.0.id', $context['branch']->id)
                ->where('can_select_branch', false)
                ->etc()
            );

        $this->assertNotSame($other['branch']->company_id, $context['branch']->company_id);
    }

    public function test_company_user_can_create_update_and_delete_branches(): void
    {
        Storage::fake('public');
        $context = $this->createTenantContext(['create branches', 'edit branches', 'delete branches']);

        $this->actingAs($context['user'])
            ->post(route('branches.store'), $this->payload($context))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branches.index'));

        $branch = Branch::query()->where('name', 'Second Branch')->firstOrFail();
        $this->assertSame($context['company']->id, $branch->company_id);
        $this->assertTrue($branch->use_company_logo);

        $this->actingAs($context['user'])
            ->put(route('branches.update', $branch), $this->payload($context, [
                'name' => 'Second Branch Logo',
                'branch_logo' => UploadedFile::fake()->image('logo.png'),
            ]))
            ->assertSessionHasNoErrors();

        $branch->refresh();
        $this->assertFalse($branch->use_company_logo);
        $this->assertStringStartsWith('/storage/branch_logos/', $branch->branch_logo);

        $this->actingAs($context['user'])
            ->put(route('branches.update', $branch), $this->payload($context, [
                'name' => 'Second Branch Own Logo',
                'use_company_logo' => false,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertStringStartsWith('/storage/branch_logos/', $branch->fresh()->branch_logo);

        $this->actingAs($context['user'])
            ->delete(route('branches.destroy', $branch), ['delete_remark' => 'Closed'])
            ->assertRedirect(route('branches.index'));

        $this->assertSoftDeleted('branches', ['id' => $branch->id, 'delete_remark' => 'Closed']);
    }

    public function test_branch_cannot_use_a_station_from_another_owner(): void
    {
        $context = $this->createTenantContext(['create branches']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->post(route('branches.store'), $this->payload($context, ['station_id' => $other['station']->id]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('branches', ['name' => 'Second Branch']);
    }
}

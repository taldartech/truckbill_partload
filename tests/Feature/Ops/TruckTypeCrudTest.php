<?php

namespace Tests\Feature\Ops;

use App\Models\TruckType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class TruckTypeCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_items_and_supports_search(): void
    {
        $context = $this->createTenantContext(['view truck_types']);
        $this->makeItem($context, 'Alpha Truck');
        $this->makeItem($context, 'Beta Truck');

        $this->actingAs($context['user'])
            ->get(route('truck-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('TruckTypes/Index')
                ->has('truckTypes.data', 2)
                ->where('filters.search', ''));

        $this->actingAs($context['user'])
            ->get(route('truck-types.index', ['search' => ' Alpha ']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('truckTypes.data', 1)
                ->where('truckTypes.data.0.name', 'Alpha Truck')
                ->where('filters.search', 'Alpha'));
    }

    public function test_index_is_paginated_and_sorted_by_name(): void
    {
        $context = $this->createTenantContext(['view truck_types']);

        foreach (range(1, 12) as $i) {
            $this->makeItem($context, sprintf('Item %02d', $i));
        }

        $this->actingAs($context['user'])
            ->get(route('truck-types.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('truckTypes.data', 10)
                ->where('truckTypes.total', 12)
                ->where('truckTypes.data.0.name', 'Item 01'));

        $this->actingAs($context['user'])
            ->get(route('truck-types.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('truckTypes.data', 2));
    }

    public function test_index_rejects_overlong_search(): void
    {
        $context = $this->createTenantContext(['view truck_types']);

        $this->actingAs($context['user'])
            ->from(route('truck-types.index'))
            ->get(route('truck-types.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_index_only_lists_current_tenant_records(): void
    {
        $context = $this->createTenantContext(['view truck_types']);
        $other = $this->createTenantContext([], 'B');
        $this->makeItem($context, 'Mine');
        $this->makeItem($other, 'Theirs');

        $this->actingAs($context['user'])
            ->get(route('truck-types.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('truckTypes.data', 1)
                ->where('truckTypes.data.0.name', 'Mine'));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->get(route('truck-types.index'))
            ->assertForbidden();
    }

    public function test_show_renders_item(): void
    {
        $context = $this->createTenantContext(['view truck_types']);
        $item = $this->makeItem($context, 'Showable');

        $this->actingAs($context['user'])
            ->get(route('truck-types.show', $item))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('TruckTypes/Show')
                ->where('truckType.id', $item->id)
                ->where('truckType.name', 'Showable'));
    }

    public function test_show_requires_permission_and_hides_other_tenants(): void
    {
        $context = $this->createTenantContext(['create truck_types']);
        $item = $this->makeItem($context, 'Locked');

        $this->actingAs($context['user'])
            ->get(route('truck-types.show', $item))
            ->assertForbidden();

        $viewer = $this->createTenantContext(['view truck_types'], 'B');
        $this->actingAs($viewer['user'])
            ->get(route('truck-types.show', $item))
            ->assertNotFound();
    }

    public function test_store_creates_item_for_current_tenant(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->from(route('truck-types.index'))
            ->post(route('truck-types.store'), ['name' => 'Fresh Truck'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('truck-types.index'))
            ->assertSessionHas('success', 'Truck type created successfully.');

        $this->assertDatabaseHas('truck_types', [
            'name' => 'Fresh Truck',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_store_validates_name(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->post(route('truck-types.store'), [])
            ->assertSessionHasErrors('name');

        $this->actingAs($context['user'])
            ->post(route('truck-types.store'), ['name' => str_repeat('x', 256)])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('truck_types', 0);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view truck_types']);

        $this->actingAs($context['user'])
            ->post(route('truck-types.store'), ['name' => 'Blocked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('truck_types', ['name' => 'Blocked']);
    }

    public function test_update_renames_item(): void
    {
        $context = $this->createTenantContext(['edit truck_types']);
        $item = $this->makeItem($context, 'Before');

        $this->actingAs($context['user'])
            ->put(route('truck-types.update', $item), ['name' => 'After'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Truck type updated successfully.');

        $this->assertSame('After', $item->fresh()->name);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit truck_types']);
        $item = $this->makeItem($context, 'Keep');

        $this->actingAs($context['user'])
            ->put(route('truck-types.update', $item), ['name' => ''])
            ->assertSessionHasErrors('name');

        $viewer = $this->createTenantContext(['view truck_types'], 'B');
        $viewerItem = $this->makeItem($viewer, 'Viewer Item');
        $this->actingAs($viewer['user'])
            ->put(route('truck-types.update', $viewerItem), ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertSame('Keep', $item->fresh()->name);
        $this->assertSame('Viewer Item', $viewerItem->fresh()->name);
    }

    public function test_update_cannot_touch_other_tenants_records(): void
    {
        $context = $this->createTenantContext(['edit truck_types']);
        $other = $this->createTenantContext([], 'B');
        $foreign = $this->makeItem($other, 'Foreign');

        $this->actingAs($context['user'])
            ->put(route('truck-types.update', $foreign), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Foreign', $foreign->fresh()->name);
    }

    public function test_destroy_soft_deletes_with_remark_and_actor(): void
    {
        $context = $this->createTenantContext(['delete truck_types']);
        $item = $this->makeItem($context, 'Doomed');

        $this->actingAs($context['user'])
            ->delete(route('truck-types.destroy', $item), ['delete_remark' => 'Duplicate entry'])
            ->assertRedirect(route('truck-types.index'))
            ->assertSessionHas('success', 'Truck type deleted successfully.');

        $this->assertSoftDeleted('truck_types', ['id' => $item->id]);
        $this->assertDatabaseHas('truck_types', [
            'id' => $item->id,
            'delete_remark' => 'Duplicate entry',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_without_remark_still_deletes(): void
    {
        $context = $this->createTenantContext(['delete truck_types']);
        $item = $this->makeItem($context, 'Quiet');

        $this->actingAs($context['user'])
            ->delete(route('truck-types.destroy', $item))
            ->assertRedirect(route('truck-types.index'));

        $this->assertSoftDeleted('truck_types', ['id' => $item->id]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view truck_types', 'edit truck_types']);
        $item = $this->makeItem($context, 'Safe');

        $this->actingAs($context['user'])
            ->delete(route('truck-types.destroy', $item))
            ->assertForbidden();

        $this->assertNotSoftDeleted('truck_types', ['id' => $item->id]);
    }

    public function test_quick_store_returns_created_payload(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.quick-store'), ['name' => 'Quick Truck'])
            ->assertCreated()
            ->assertJsonPath('truck_type.name', 'Quick Truck')
            ->assertJsonStructure(['truck_type' => ['id', 'name']]);

        $this->assertDatabaseHas('truck_types', [
            'name' => 'Quick Truck',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_quick_store_ignores_client_supplied_tenant_fields(): void
    {
        $context = $this->createTenantContext(['create truck_types']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.quick-store'), [
                'name' => 'Injected',
                'owner_id' => $other['owner']->id,
                'company_id' => $other['company']->id,
                'branch_id' => $other['branch']->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('truck_types', [
            'name' => 'Injected',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_quick_store_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $viewer = $this->createTenantContext(['view truck_types'], 'B');
        $this->actingAs($viewer['user'])
            ->postJson(route('truck-types.quick-store'), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_bulk_upload_template_download(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-types.bulk-upload.template'));

        $response->assertOk();
        $this->assertStringContainsString(
            'truck_types_bulk_upload_template.csv',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bulk_upload_template_requires_permission(): void
    {
        $context = $this->createTenantContext(['view truck_types']);

        $this->actingAs($context['user'])
            ->get(route('truck-types.bulk-upload.template'))
            ->assertForbidden();
    }

    public function test_bulk_upload_creates_rows_and_reports_duplicates(): void
    {
        $context = $this->createTenantContext(['create truck_types']);
        $this->makeItem($context, 'Existing');

        $csv = "Name\nExisting\nFresh One\nFresh Two\nFresh Two\n";

        $response = $this->actingAs($context['user'])
            ->postJson(route('truck-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', $csv),
            ])
            ->assertOk();

        $this->assertSame(4, $response->json('summary.total'));
        $this->assertSame(2, $response->json('summary.successful'));
        $this->assertNotEmpty($response->json('errors'));
        $this->assertSame(1, TruckType::query()->where('name', 'Existing')->count());
        $this->assertSame(1, TruckType::query()->where('name', 'Fresh One')->count());
        $this->assertSame(1, TruckType::query()->where('name', 'Fresh Two')->count());
    }

    public function test_bulk_upload_reports_missing_required_column(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $response = $this->actingAs($context['user'])
            ->postJson(route('truck-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', "Unrelated\nValue\n"),
            ])
            ->assertOk();

        $this->assertSame(0, $response->json('summary.successful'));
        $this->assertNotEmpty($response->json('errors'));
        $this->assertDatabaseCount('truck_types', 0);
    }

    public function test_bulk_upload_rejects_invalid_file(): void
    {
        $context = $this->createTenantContext(['create truck_types']);

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.bulk-upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.bulk-upload'), [
                'file' => UploadedFile::fake()->create('rows.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_bulk_upload_requires_permission(): void
    {
        $context = $this->createTenantContext(['view truck_types']);

        $this->actingAs($context['user'])
            ->postJson(route('truck-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', "Name\nBlocked\n"),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('truck_types', ['name' => 'Blocked']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeItem(array $context, string $name): TruckType
    {
        return TruckType::create([
            'name' => $name,
            'owner_id' => $context['owner']->id,
        ]);
    }
}

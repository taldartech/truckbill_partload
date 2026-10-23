<?php

namespace Tests\Feature\Ops;

use App\Models\PackageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PackageTypeCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_items_and_supports_search(): void
    {
        $context = $this->createTenantContext(['view package_types']);
        $this->makeItem($context, 'Alpha Package');
        $this->makeItem($context, 'Beta Package');

        $this->actingAs($context['user'])
            ->get(route('package-types.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PackageTypes/Index')
                ->has('items.data', 2)
                ->where('filters.search', ''));

        $this->actingAs($context['user'])
            ->get(route('package-types.index', ['search' => ' Alpha ']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Alpha Package')
                ->where('filters.search', 'Alpha'));
    }

    public function test_index_is_paginated_and_sorted_by_name(): void
    {
        $context = $this->createTenantContext(['view package_types']);

        foreach (range(1, 12) as $i) {
            $this->makeItem($context, sprintf('Item %02d', $i));
        }

        $this->actingAs($context['user'])
            ->get(route('package-types.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 10)
                ->where('items.total', 12)
                ->where('items.data.0.name', 'Item 01'));

        $this->actingAs($context['user'])
            ->get(route('package-types.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 2));
    }

    public function test_index_rejects_overlong_search(): void
    {
        $context = $this->createTenantContext(['view package_types']);

        $this->actingAs($context['user'])
            ->from(route('package-types.index'))
            ->get(route('package-types.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_index_only_lists_current_tenant_records(): void
    {
        $context = $this->createTenantContext(['view package_types']);
        $other = $this->createTenantContext([], 'B');
        $this->makeItem($context, 'Mine');
        $this->makeItem($other, 'Theirs');

        $this->actingAs($context['user'])
            ->get(route('package-types.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Mine'));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->get(route('package-types.index'))
            ->assertForbidden();
    }

    public function test_show_renders_item(): void
    {
        $context = $this->createTenantContext(['view package_types']);
        $item = $this->makeItem($context, 'Showable');

        $this->actingAs($context['user'])
            ->get(route('package-types.show', $item))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PackageTypes/Show')
                ->where('packageType.id', $item->id)
                ->where('packageType.name', 'Showable'));
    }

    public function test_show_requires_permission_and_hides_other_tenants(): void
    {
        $context = $this->createTenantContext(['create package_types']);
        $item = $this->makeItem($context, 'Locked');

        $this->actingAs($context['user'])
            ->get(route('package-types.show', $item))
            ->assertForbidden();

        $viewer = $this->createTenantContext(['view package_types'], 'B');
        $this->actingAs($viewer['user'])
            ->get(route('package-types.show', $item))
            ->assertNotFound();
    }

    public function test_store_creates_item_for_current_tenant(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->from(route('package-types.index'))
            ->post(route('package-types.store'), ['name' => 'Fresh Package'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('package-types.index'))
            ->assertSessionHas('success', 'Package Types created successfully.');

        $this->assertDatabaseHas('package_types', [
            'name' => 'Fresh Package',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_store_validates_name(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->post(route('package-types.store'), [])
            ->assertSessionHasErrors('name');

        $this->actingAs($context['user'])
            ->post(route('package-types.store'), ['name' => str_repeat('x', 256)])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('package_types', 0);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view package_types']);

        $this->actingAs($context['user'])
            ->post(route('package-types.store'), ['name' => 'Blocked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('package_types', ['name' => 'Blocked']);
    }

    public function test_update_renames_item(): void
    {
        $context = $this->createTenantContext(['edit package_types']);
        $item = $this->makeItem($context, 'Before');

        $this->actingAs($context['user'])
            ->put(route('package-types.update', $item), ['name' => 'After'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Package Types updated successfully.');

        $this->assertSame('After', $item->fresh()->name);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit package_types']);
        $item = $this->makeItem($context, 'Keep');

        $this->actingAs($context['user'])
            ->put(route('package-types.update', $item), ['name' => ''])
            ->assertSessionHasErrors('name');

        $viewer = $this->createTenantContext(['view package_types'], 'B');
        $viewerItem = $this->makeItem($viewer, 'Viewer Item');
        $this->actingAs($viewer['user'])
            ->put(route('package-types.update', $viewerItem), ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertSame('Keep', $item->fresh()->name);
        $this->assertSame('Viewer Item', $viewerItem->fresh()->name);
    }

    public function test_update_cannot_touch_other_tenants_records(): void
    {
        $context = $this->createTenantContext(['edit package_types']);
        $other = $this->createTenantContext([], 'B');
        $foreign = $this->makeItem($other, 'Foreign');

        $this->actingAs($context['user'])
            ->put(route('package-types.update', $foreign), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Foreign', $foreign->fresh()->name);
    }

    public function test_destroy_soft_deletes_with_remark_and_actor(): void
    {
        $context = $this->createTenantContext(['delete package_types']);
        $item = $this->makeItem($context, 'Doomed');

        $this->actingAs($context['user'])
            ->delete(route('package-types.destroy', $item), ['delete_remark' => 'Duplicate entry'])
            ->assertRedirect(route('package-types.index'))
            ->assertSessionHas('success', 'Package Types deleted successfully.');

        $this->assertSoftDeleted('package_types', ['id' => $item->id]);
        $this->assertDatabaseHas('package_types', [
            'id' => $item->id,
            'delete_remark' => 'Duplicate entry',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_without_remark_still_deletes(): void
    {
        $context = $this->createTenantContext(['delete package_types']);
        $item = $this->makeItem($context, 'Quiet');

        $this->actingAs($context['user'])
            ->delete(route('package-types.destroy', $item))
            ->assertRedirect(route('package-types.index'));

        $this->assertSoftDeleted('package_types', ['id' => $item->id]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view package_types', 'edit package_types']);
        $item = $this->makeItem($context, 'Safe');

        $this->actingAs($context['user'])
            ->delete(route('package-types.destroy', $item))
            ->assertForbidden();

        $this->assertNotSoftDeleted('package_types', ['id' => $item->id]);
    }

    public function test_quick_store_returns_created_payload(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->postJson(route('package-types.quick-store'), ['name' => 'Quick Package'])
            ->assertCreated()
            ->assertJsonPath('package_type.name', 'Quick Package')
            ->assertJsonStructure(['package_type' => ['id', 'name']]);

        $this->assertDatabaseHas('package_types', [
            'name' => 'Quick Package',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_quick_store_ignores_client_supplied_tenant_fields(): void
    {
        $context = $this->createTenantContext(['create package_types']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->postJson(route('package-types.quick-store'), [
                'name' => 'Injected',
                'owner_id' => $other['owner']->id,
                'company_id' => $other['company']->id,
                'branch_id' => $other['branch']->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('package_types', [
            'name' => 'Injected',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_quick_store_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->postJson(route('package-types.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $viewer = $this->createTenantContext(['view package_types'], 'B');
        $this->actingAs($viewer['user'])
            ->postJson(route('package-types.quick-store'), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_bulk_upload_template_download(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $response = $this->actingAs($context['user'])
            ->get(route('package-types.bulk-upload.template'));

        $response->assertOk();
        $this->assertStringContainsString(
            'package_types_bulk_upload_template.csv',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bulk_upload_template_requires_permission(): void
    {
        $context = $this->createTenantContext(['view package_types']);

        $this->actingAs($context['user'])
            ->get(route('package-types.bulk-upload.template'))
            ->assertForbidden();
    }

    public function test_bulk_upload_creates_rows_and_reports_duplicates(): void
    {
        $context = $this->createTenantContext(['create package_types']);
        $this->makeItem($context, 'Existing');

        $csv = "Name\nExisting\nFresh One\nFresh Two\nFresh Two\n";

        $response = $this->actingAs($context['user'])
            ->postJson(route('package-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', $csv),
            ])
            ->assertOk();

        $this->assertSame(4, $response->json('summary.total'));
        $this->assertSame(2, $response->json('summary.successful'));
        $this->assertNotEmpty($response->json('errors'));
        $this->assertSame(1, PackageType::query()->where('name', 'Existing')->count());
        $this->assertSame(1, PackageType::query()->where('name', 'Fresh One')->count());
        $this->assertSame(1, PackageType::query()->where('name', 'Fresh Two')->count());
    }

    public function test_bulk_upload_reports_missing_required_column(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $response = $this->actingAs($context['user'])
            ->postJson(route('package-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', "Unrelated\nValue\n"),
            ])
            ->assertOk();

        $this->assertSame(0, $response->json('summary.successful'));
        $this->assertNotEmpty($response->json('errors'));
        $this->assertDatabaseCount('package_types', 0);
    }

    public function test_bulk_upload_rejects_invalid_file(): void
    {
        $context = $this->createTenantContext(['create package_types']);

        $this->actingAs($context['user'])
            ->postJson(route('package-types.bulk-upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('package-types.bulk-upload'), [
                'file' => UploadedFile::fake()->create('rows.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_bulk_upload_requires_permission(): void
    {
        $context = $this->createTenantContext(['view package_types']);

        $this->actingAs($context['user'])
            ->postJson(route('package-types.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('rows.csv', "Name\nBlocked\n"),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('package_types', ['name' => 'Blocked']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeItem(array $context, string $name): PackageType
    {
        return PackageType::create([
            'name' => $name,
            'owner_id' => $context['owner']->id,
        ]);
    }
}

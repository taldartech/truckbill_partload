<?php

namespace Tests\Feature\Ops;

use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class GroupCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_items_and_supports_search(): void
    {
        $context = $this->createTenantContext(['view groups']);
        $this->makeItem($context, 'Alpha Group');
        $this->makeItem($context, 'Beta Group');

        $this->actingAs($context['user'])
            ->get(route('groups.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Index')
                ->has('items.data', 2)
                ->where('filters.search', ''));

        $this->actingAs($context['user'])
            ->get(route('groups.index', ['search' => ' Alpha ']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Alpha Group')
                ->where('filters.search', 'Alpha'));
    }

    public function test_index_is_paginated_and_sorted_by_name(): void
    {
        $context = $this->createTenantContext(['view groups']);

        foreach (range(1, 12) as $i) {
            $this->makeItem($context, sprintf('Item %02d', $i));
        }

        $this->actingAs($context['user'])
            ->get(route('groups.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 10)
                ->where('items.total', 12)
                ->where('items.data.0.name', 'Item 01'));

        $this->actingAs($context['user'])
            ->get(route('groups.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 2));
    }

    public function test_index_rejects_overlong_search(): void
    {
        $context = $this->createTenantContext(['view groups']);

        $this->actingAs($context['user'])
            ->from(route('groups.index'))
            ->get(route('groups.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_index_only_lists_current_tenant_records(): void
    {
        $context = $this->createTenantContext(['view groups']);
        $other = $this->createTenantContext([], 'B');
        $this->makeItem($context, 'Mine');
        $this->makeItem($other, 'Theirs');

        $this->actingAs($context['user'])
            ->get(route('groups.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Mine'));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext(['create groups']);

        $this->actingAs($context['user'])
            ->get(route('groups.index'))
            ->assertForbidden();
    }

    public function test_show_renders_item(): void
    {
        $context = $this->createTenantContext(['view groups']);
        $item = $this->makeItem($context, 'Showable');

        $this->actingAs($context['user'])
            ->get(route('groups.show', $item))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Show')
                ->where('group.id', $item->id)
                ->where('group.name', 'Showable'));
    }

    public function test_show_requires_permission_and_hides_other_tenants(): void
    {
        $context = $this->createTenantContext(['create groups']);
        $item = $this->makeItem($context, 'Locked');

        $this->actingAs($context['user'])
            ->get(route('groups.show', $item))
            ->assertForbidden();

        $viewer = $this->createTenantContext(['view groups'], 'B');
        $this->actingAs($viewer['user'])
            ->get(route('groups.show', $item))
            ->assertNotFound();
    }

    public function test_store_creates_item_for_current_tenant(): void
    {
        $context = $this->createTenantContext(['create groups']);

        $this->actingAs($context['user'])
            ->from(route('groups.index'))
            ->post(route('groups.store'), ['name' => 'Fresh Group'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'))
            ->assertSessionHas('success', 'Groups created successfully.');

        $this->assertDatabaseHas('groups', [
            'name' => 'Fresh Group',
            'company_id' => $context['company']->id,
        ]);
    }

    public function test_store_validates_name(): void
    {
        $context = $this->createTenantContext(['create groups']);

        $this->actingAs($context['user'])
            ->post(route('groups.store'), [])
            ->assertSessionHasErrors('name');

        $this->actingAs($context['user'])
            ->post(route('groups.store'), ['name' => str_repeat('x', 256)])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('groups', 0);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view groups']);

        $this->actingAs($context['user'])
            ->post(route('groups.store'), ['name' => 'Blocked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('groups', ['name' => 'Blocked']);
    }

    public function test_update_renames_item(): void
    {
        $context = $this->createTenantContext(['edit groups']);
        $item = $this->makeItem($context, 'Before');

        $this->actingAs($context['user'])
            ->put(route('groups.update', $item), ['name' => 'After'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Groups updated successfully.');

        $this->assertSame('After', $item->fresh()->name);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit groups']);
        $item = $this->makeItem($context, 'Keep');

        $this->actingAs($context['user'])
            ->put(route('groups.update', $item), ['name' => ''])
            ->assertSessionHasErrors('name');

        $viewer = $this->createTenantContext(['view groups'], 'B');
        $viewerItem = $this->makeItem($viewer, 'Viewer Item');
        $this->actingAs($viewer['user'])
            ->put(route('groups.update', $viewerItem), ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertSame('Keep', $item->fresh()->name);
        $this->assertSame('Viewer Item', $viewerItem->fresh()->name);
    }

    public function test_update_cannot_touch_other_tenants_records(): void
    {
        $context = $this->createTenantContext(['edit groups']);
        $other = $this->createTenantContext([], 'B');
        $foreign = $this->makeItem($other, 'Foreign');

        $this->actingAs($context['user'])
            ->put(route('groups.update', $foreign), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Foreign', $foreign->fresh()->name);
    }

    public function test_destroy_soft_deletes_with_remark_and_actor(): void
    {
        $context = $this->createTenantContext(['delete groups']);
        $item = $this->makeItem($context, 'Doomed');

        $this->actingAs($context['user'])
            ->delete(route('groups.destroy', $item), ['delete_remark' => 'Duplicate entry'])
            ->assertRedirect(route('groups.index'))
            ->assertSessionHas('success', 'Groups deleted successfully.');

        $this->assertSoftDeleted('groups', ['id' => $item->id]);
        $this->assertDatabaseHas('groups', [
            'id' => $item->id,
            'delete_remark' => 'Duplicate entry',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_without_remark_still_deletes(): void
    {
        $context = $this->createTenantContext(['delete groups']);
        $item = $this->makeItem($context, 'Quiet');

        $this->actingAs($context['user'])
            ->delete(route('groups.destroy', $item))
            ->assertRedirect(route('groups.index'));

        $this->assertSoftDeleted('groups', ['id' => $item->id]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view groups', 'edit groups']);
        $item = $this->makeItem($context, 'Safe');

        $this->actingAs($context['user'])
            ->delete(route('groups.destroy', $item))
            ->assertForbidden();

        $this->assertNotSoftDeleted('groups', ['id' => $item->id]);
    }

    public function test_quick_store_returns_created_payload(): void
    {
        $context = $this->createTenantContext(['create groups']);

        $this->actingAs($context['user'])
            ->postJson(route('groups.quick-store'), ['name' => 'Quick Group'])
            ->assertCreated()
            ->assertJsonPath('group.name', 'Quick Group')
            ->assertJsonStructure(['group' => ['id', 'name']]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Quick Group',
            'company_id' => $context['company']->id,
        ]);
    }

    public function test_quick_store_ignores_client_supplied_tenant_fields(): void
    {
        $context = $this->createTenantContext(['create groups']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->postJson(route('groups.quick-store'), [
                'name' => 'Injected',
                'owner_id' => $other['owner']->id,
                'company_id' => $other['company']->id,
                'branch_id' => $other['branch']->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('groups', [
            'name' => 'Injected',
            'company_id' => $context['company']->id,
        ]);
    }

    public function test_quick_store_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['create groups']);

        $this->actingAs($context['user'])
            ->postJson(route('groups.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $viewer = $this->createTenantContext(['view groups'], 'B');
        $this->actingAs($viewer['user'])
            ->postJson(route('groups.quick-store'), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeItem(array $context, string $name): Group
    {
        return Group::create([
            'name' => $name,
            'company_id' => $context['company']->id,
        ]);
    }
}

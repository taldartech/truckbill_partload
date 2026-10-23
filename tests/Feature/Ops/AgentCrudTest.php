<?php

namespace Tests\Feature\Ops;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Agent;
use App\Models\City;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AgentCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view agents',
        'create agents',
        'edit agents',
        'delete agents',
    ];

    public function test_index_lists_company_agents_alphabetically_with_lookups(): void
    {
        $context = $this->agentContext(self::ALL_PERMISSIONS);
        $other = $this->agentContext(self::ALL_PERMISSIONS, 'B');
        $this->makeAgent($context, 'Zeta Agent', '9876543210');
        $this->makeAgent($context, 'Alpha Agent', '9876543211');
        $this->makeAgent($other, 'Foreign Agent', '9876543212');

        $this->actingAs($context['user'])
            ->get(route('agents.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Agents/Index')
                ->has('agents.data', 2)
                ->where('agents.data.0.name', 'Alpha Agent')
                ->where('agents.data.1.name', 'Zeta Agent')
                ->has('cities')
                ->has('states')
                ->where('filters.search', '')
            );
    }

    public function test_index_searches_by_name_mobile_and_gst(): void
    {
        $context = $this->agentContext(self::ALL_PERMISSIONS);
        $this->makeAgent($context, 'Sharma Transport', '9876543210', ['gst_no' => '27AAAAA0000A1Z5']);
        $this->makeAgent($context, 'Verma Carriers', '9123456780', ['gst_no' => '08BBBBB1111B1Z6']);

        $this->actingAs($context['user'])
            ->get(route('agents.index', ['search' => 'Sharma']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('agents.data', 1)
                ->where('agents.data.0.name', 'Sharma Transport')
                ->where('filters.search', 'Sharma')
            );

        $this->actingAs($context['user'])
            ->get(route('agents.index', ['search' => '91234567']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('agents.data', 1)
                ->where('agents.data.0.name', 'Verma Carriers')
            );

        $this->actingAs($context['user'])
            ->get(route('agents.index', ['search' => '08BBBBB']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('agents.data', 1)
                ->where('agents.data.0.name', 'Verma Carriers')
            );

        $this->actingAs($context['user'])
            ->get(route('agents.index', ['search' => 'nope']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('agents.data', 0));
    }

    public function test_index_validates_search_and_requires_permission(): void
    {
        $context = $this->agentContext(['view agents']);

        $this->actingAs($context['user'])
            ->from(route('agents.index'))
            ->get(route('agents.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');

        $noPermission = $this->agentContext([], 'B');
        $this->actingAs($noPermission['user'])->get(route('agents.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('agents.index'))->assertRedirect(route('login'));
        $this->postJson(route('agents.quick-store'), [])->assertUnauthorized();
    }

    public function test_show_hides_other_tenants_agents(): void
    {
        $context = $this->agentContext(['view agents']);
        $other = $this->agentContext([], 'B');
        $foreign = $this->makeAgent($other, 'Foreign Agent', '9876543212');

        $this->actingAs($context['user'])->get(route('agents.show', $foreign))->assertNotFound();
    }

    public function test_create_and_edit_redirect_to_index(): void
    {
        $context = $this->agentContext(['create agents', 'edit agents']);
        $agent = $this->makeAgent($context, 'Redirect Agent', '9876543210');

        $this->actingAs($context['user'])
            ->get(route('agents.create'))
            ->assertRedirect(route('agents.index'));

        $this->actingAs($context['user'])
            ->get(route('agents.edit', $agent))
            ->assertRedirect(route('agents.index'));
    }

    public function test_create_and_edit_require_permission(): void
    {
        $context = $this->agentContext(['view agents']);
        $agent = $this->makeAgent($context, 'Redirect Agent', '9876543210');

        $this->actingAs($context['user'])->get(route('agents.create'))->assertForbidden();
        $this->actingAs($context['user'])->get(route('agents.edit', $agent))->assertForbidden();
    }

    public function test_store_creates_agent_in_active_company_without_ledger_by_default(): void
    {
        $context = $this->agentContext(['create agents']);
        $accountsBefore = Account::query()->withoutGlobalScopes()->count();

        $this->actingAs($context['user'])
            ->from(route('agents.index'))
            ->post(route('agents.store'), $this->validPayload($context, [
                'name' => 'Fresh Agent',
                'email' => 'fresh@example.com',
                'gst_no' => '27AAAAA0000A1Z5',
                'transport_id' => '27aaaaa0000a1z5',
                'company_id' => 999999,
                'branch_id' => 999999,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('agents.index'))
            ->assertSessionHas('success', 'Agent created successfully.');

        $agent = Agent::query()->where('name', 'Fresh Agent')->firstOrFail();
        $this->assertSame($context['company']->id, $agent->company_id);
        $this->assertFalse($agent->consider_for_accounting);
        $this->assertSame('fresh@example.com', $agent->email);
        $this->assertSame($accountsBefore, Account::query()->withoutGlobalScopes()->count());
    }

    public function test_store_with_accounting_enabled_links_ledger_in_default_and_selected_group(): void
    {
        $context = $this->agentContext(['create agents']);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context, [
                'name' => 'Default Group Agent',
                'consider_for_accounting' => true,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context, [
                'name' => 'Chosen Group Agent',
                'mobile' => '9876543299',
                'consider_for_accounting' => true,
                'account_group_id' => $group->id,
            ]))
            ->assertSessionHasNoErrors();

        $linker = app(EntityAccountLinker::class);
        $default = Agent::query()->where('name', 'Default Group Agent')->firstOrFail();
        $this->assertTrue($default->consider_for_accounting);
        $this->assertNotNull($linker->linkedAccount($default));

        $chosen = Agent::query()->where('name', 'Chosen Group Agent')->firstOrFail();
        $this->assertSame($group->id, $linker->linkedAccount($chosen)->account_group_id);
    }

    public function test_store_rejects_account_group_from_another_company(): void
    {
        $context = $this->agentContext(['create agents']);
        $other = $this->agentContext([], 'B');
        $foreignGroup = AccountGroup::query()->where('company_id', $other['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context, [
                'consider_for_accounting' => true,
                'account_group_id' => $foreignGroup->id,
            ]))
            ->assertSessionHasErrors('account_group_id');

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->agentContext(['create agents']);

        $this->actingAs($context['user'])
            ->from(route('agents.index'))
            ->post(route('agents.store'), [])
            ->assertRedirect(route('agents.index'))
            ->assertSessionHasErrors(['name', 'address', 'city_id', 'state_id', 'mobile']);

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_store_validates_formats_with_custom_messages(): void
    {
        $context = $this->agentContext(['create agents']);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context, [
                'mobile' => '12345',
                'email' => 'not-an-email',
                'gst_no' => 'BADGST',
                'transport_id' => 'BADTRANSPORT',
                'city_id' => 999999,
                'state_id' => 999999,
                'consider_for_accounting' => 'maybe',
            ]));

        $response->assertSessionHasErrors(['mobile', 'email', 'gst_no', 'transport_id', 'city_id', 'state_id', 'consider_for_accounting']);
        $errors = session('errors')->getBag('default');
        $this->assertSame('Mobile number must be exactly 10 digits.', $errors->first('mobile'));
        $this->assertSame('GST number must be exactly 15 characters.', $errors->first('gst_no'));
        $this->assertSame('Transport id must be exactly 15 characters.', $errors->first('transport_id'));

        $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context, [
                'mobile' => '1234567890',
                'gst_no' => '27AAAAA0000A1Z5'.'',
                'transport_id' => 'XXAAAAA0000A1Z5',
            ]))
            ->assertSessionHasErrors(['mobile', 'transport_id']);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->agentContext(['view agents']);

        $this->actingAs($context['user'])
            ->post(route('agents.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_quick_form_data_returns_states(): void
    {
        $context = $this->agentContext(['create agents']);

        $this->actingAs($context['user'])
            ->getJson(route('agents.quick-form-data'))
            ->assertOk()
            ->assertJsonPath('states.0.name', 'Rajasthan');
    }

    public function test_quick_form_data_requires_permission(): void
    {
        $context = $this->agentContext(['view agents']);

        $this->actingAs($context['user'])->getJson(route('agents.quick-form-data'))->assertForbidden();
    }

    public function test_quick_store_validates_input_and_requires_permission(): void
    {
        $context = $this->agentContext(['create agents']);

        $this->actingAs($context['user'])
            ->postJson(route('agents.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address', 'city_id', 'state_id', 'mobile']);

        $viewer = $this->agentContext(['view agents'], 'B');
        $this->actingAs($viewer['user'])
            ->postJson(route('agents.quick-store'), $this->validPayload($viewer))
            ->assertForbidden();
    }

    public function test_quick_store_ignores_account_group_and_uses_default_group(): void
    {
        $context = $this->agentContext(['create agents']);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        $response = $this->actingAs($context['user'])
            ->postJson(route('agents.quick-store'), $this->validPayload($context, [
                'name' => 'Quick Ledger Agent',
                'consider_for_accounting' => true,
                'account_group_id' => $group->id,
            ]));

        $response->assertCreated()->assertJsonPath('agent.name', 'Quick Ledger Agent');

        $agent = Agent::query()->where('name', 'Quick Ledger Agent')->firstOrFail();
        $account = app(EntityAccountLinker::class)->linkedAccount($agent);
        $this->assertNotNull($account);
        $response->assertJsonPath('agent.account.id', $account->id);
        $this->assertNotSame($group->id, $account->account_group_id);
    }

    public function test_update_changes_agent_and_syncs_ledger_name_when_enabling_accounting(): void
    {
        $context = $this->agentContext(['edit agents']);
        $agent = $this->makeAgent($context, 'Old Name', '9876543210');

        $this->actingAs($context['user'])
            ->from(route('agents.index'))
            ->put(route('agents.update', $agent), $this->validPayload($context, [
                'name' => 'New Name',
                'mobile' => '9876543210',
                'consider_for_accounting' => true,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('agents.index'))
            ->assertSessionHas('success', 'Agent updated successfully.');

        $agent->refresh();
        $this->assertSame('New Name', $agent->name);
        $this->assertTrue($agent->consider_for_accounting);

        $linker = app(EntityAccountLinker::class);
        $account = $linker->linkedAccount($agent);
        $this->assertSame('New Name', $account->name);
        $this->assertTrue($account->is_selectable);

        $this->actingAs($context['user'])
            ->put(route('agents.update', $agent), $this->validPayload($context, [
                'name' => 'Renamed Again',
                'consider_for_accounting' => true,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Again', $linker->linkedAccount($agent->fresh())->name);
    }

    public function test_update_disables_ledger_when_accounting_is_turned_off(): void
    {
        $context = $this->agentContext(['edit agents']);
        $agent = $this->makeAgent($context, 'Ledger Agent', '9876543210', ['consider_for_accounting' => true]);
        $linker = app(EntityAccountLinker::class);
        $linker->enable($agent);

        $this->actingAs($context['user'])
            ->put(route('agents.update', $agent), $this->validPayload($context, ['name' => 'Ledger Agent']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($agent->fresh()->consider_for_accounting);
        $account = $linker->linkedAccount($agent);
        $this->assertFalse($account->is_selectable);
        $this->assertFalse($account->is_active);
    }

    public function test_update_without_linked_account_and_accounting_off_is_a_noop_for_ledger(): void
    {
        $context = $this->agentContext(['edit agents']);
        $agent = $this->makeAgent($context, 'Plain Agent', '9876543210');
        $accountsBefore = Account::query()->withoutGlobalScopes()->count();

        $this->actingAs($context['user'])
            ->put(route('agents.update', $agent), $this->validPayload($context, ['name' => 'Plain Agent 2']))
            ->assertSessionHasNoErrors();

        $this->assertSame($accountsBefore, Account::query()->withoutGlobalScopes()->count());
        $this->assertSame('Plain Agent 2', $agent->fresh()->name);
    }

    public function test_update_validates_input_and_requires_permission(): void
    {
        $context = $this->agentContext(['edit agents']);
        $agent = $this->makeAgent($context, 'Old Name', '9876543210');

        $this->actingAs($context['user'])
            ->put(route('agents.update', $agent), ['name' => '', 'mobile' => 'abc'])
            ->assertSessionHasErrors(['name', 'address', 'city_id', 'state_id', 'mobile']);
        $this->assertSame('Old Name', $agent->fresh()->name);

        $viewer = $this->agentContext(['view agents'], 'B');
        $viewerAgent = $this->makeAgent($viewer, 'Viewer Agent', '9876543299');
        $this->actingAs($viewer['user'])
            ->put(route('agents.update', $viewerAgent), $this->validPayload($viewer))
            ->assertForbidden();
    }

    public function test_update_cannot_touch_other_tenants_agent(): void
    {
        $context = $this->agentContext(['edit agents']);
        $other = $this->agentContext([], 'B');
        $foreign = $this->makeAgent($other, 'Foreign Agent', '9876543212');

        $this->actingAs($context['user'])
            ->put(route('agents.update', $foreign), $this->validPayload($context, ['name' => 'Hijacked']))
            ->assertNotFound();

        $this->assertSame('Foreign Agent', $foreign->fresh()->name);
    }

    public function test_destroy_soft_deletes_agent_with_remark(): void
    {
        $context = $this->agentContext(['delete agents']);
        $agent = $this->makeAgent($context, 'Doomed Agent', '9876543210');

        $this->actingAs($context['user'])
            ->delete(route('agents.destroy', $agent), ['delete_remark' => 'Left business'])
            ->assertRedirect(route('agents.index'))
            ->assertSessionHas('success', 'Agent deleted successfully.');

        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'delete_remark' => 'Left business',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->agentContext(['view agents', 'edit agents']);
        $agent = $this->makeAgent($context, 'Safe Agent', '9876543210');

        $this->actingAs($context['user'])->delete(route('agents.destroy', $agent))->assertForbidden();

        $this->assertNotSoftDeleted('agents', ['id' => $agent->id]);
    }

    public function test_bulk_upload_requires_a_csv_or_excel_file(): void
    {
        $context = $this->agentContext(['create agents']);

        $this->actingAs($context['user'])
            ->postJson(route('agents.bulk-upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('agents.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('agents.txt', "Agent Name\nX"),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('agents.bulk-upload'), [
                'file' => UploadedFile::fake()->create('agents.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).');
    }

    public function test_bulk_upload_template_requires_permission(): void
    {
        $context = $this->agentContext(['view agents']);

        $this->actingAs($context['user'])->get(route('agents.bulk-upload.template'))->assertForbidden();
    }

    public function test_bulk_upload_requires_state_and_city_values(): void
    {
        $context = $this->agentContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."No State,1 Road,,Udaipur,9876543210\n"
            ."No City,1 Road,Rajasthan,,9876543211\n";

        $response = $this->uploadAgents($context, $csv);

        $response->assertOk()->assertJson([
            'success' => false,
            'summary' => ['total' => 2, 'successful' => 0, 'failed' => 2],
        ]);
        $fields = collect($response->json('errors'))->pluck('field')->all();
        $this->assertContains('State', $fields);
        $this->assertContains('City', $fields);
        $this->assertDatabaseCount('agents', 0);
    }

    public function test_bulk_upload_resolves_state_by_code_and_id_and_city_by_id_with_cache(): void
    {
        $context = $this->agentContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile,Email,GST Number,Transporter ID\n"
            ."By Code,1 Road,rj,Udaipur,9876543210,,,\n"
            ."By Code Again,2 Road,RJ,UDAIPUR,9876543211,,,\n"
            ."By Id,3 Road,{$context['state']->id},{$context['city']->id},9876543212,by-id@example.com,27AAAAA0000A1Z5,27AAAAA0000A1Z5\n";

        $response = $this->uploadAgents($context, $csv);

        $response->assertOk()->assertJson([
            'success' => true,
            'summary' => ['total' => 3, 'successful' => 3, 'failed' => 0],
        ]);

        foreach (['By Code', 'By Code Again', 'By Id'] as $name) {
            $this->assertDatabaseHas('agents', [
                'name' => $name,
                'company_id' => $context['company']->id,
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
            ]);
        }

        $blank = Agent::query()->where('name', 'By Code')->firstOrFail();
        $this->assertNull($blank->email);
        $this->assertNull($blank->gst_no);
        $this->assertNull($blank->transport_id);
    }

    public function test_bulk_upload_rejects_city_from_a_different_state(): void
    {
        $context = $this->agentContext(['create agents']);
        $otherState = $context['state']->replicate(['code'])->forceFill(['name' => 'Gujarat', 'code' => 'GJ']);
        $otherState->save();
        City::create(['name' => 'Surat', 'state_id' => $otherState->id, 'country_id' => $otherState->country_id]);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Wrong City,1 Road,Rajasthan,Surat,9876543210\n";

        $response = $this->uploadAgents($context, $csv);

        $response->assertOk()->assertJson(['success' => false]);
        $this->assertStringContainsString(
            'was not found in city records for state "Rajasthan"',
            $response->json('errors.0.message')
        );
    }

    public function test_bulk_upload_validates_email_and_transporter_id_formats(): void
    {
        $context = $this->agentContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile,Email,Transporter ID\n"
            ."Bad Email,1 Road,Rajasthan,Udaipur,9876543210,not-an-email,\n"
            ."Bad Transporter,1 Road,Rajasthan,Udaipur,9876543211,,SHORT\n";

        $response = $this->uploadAgents($context, $csv);

        $response->assertOk()->assertJson([
            'success' => false,
            'summary' => ['total' => 2, 'successful' => 0, 'failed' => 2],
        ]);
        $fields = collect($response->json('errors'))->pluck('field')->all();
        $this->assertContains('Email', $fields);
        $this->assertContains('Transporter ID', $fields);
        $this->assertDatabaseCount('agents', 0);
    }

    public function test_bulk_upload_reports_missing_required_columns_and_values(): void
    {
        $context = $this->agentContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            .",1 Road,Rajasthan,Udaipur,9876543210\n"
            ."No Address,,Rajasthan,Udaipur,9876543211\n";

        $response = $this->uploadAgents($context, $csv);

        $response->assertOk()->assertJson([
            'success' => false,
            'summary' => ['total' => 2, 'successful' => 0, 'failed' => 2],
        ]);
        $this->assertDatabaseCount('agents', 0);
    }

    public function test_bulk_upload_accepts_header_aliases(): void
    {
        $context = $this->agentContext(['create agents']);

        $csv = "Agent,Agent Address,State Name,City Name,Phone,Email Address,GSTIN\n"
            ."Alias Agent,9 Alias Road,Rajasthan,Udaipur,9876543210,alias@example.com,27AAAAA0000A1Z5\n";

        $this->uploadAgents($context, $csv)
            ->assertOk()
            ->assertJson(['success' => true, 'summary' => ['successful' => 1]]);

        $this->assertDatabaseHas('agents', [
            'name' => 'Alias Agent',
            'mobile' => '9876543210',
            'email' => 'alias@example.com',
            'gst_no' => '27AAAAA0000A1Z5',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function uploadAgents(array $context, string $csv): TestResponse
    {
        return $this->actingAs($context['user'])->post(
            route('agents.bulk-upload'),
            ['file' => UploadedFile::fake()->createWithContent('agents.csv', $csv)]
        );
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function agentContext(array $permissions, string $suffix = 'A'): array
    {
        return $this->accountingContext($permissions, $suffix);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeAgent(array $context, string $name, string $mobile, array $attributes = []): Agent
    {
        return Agent::create(array_merge([
            'company_id' => $context['company']->id,
            'name' => $name,
            'address' => '1 Agent Street',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => $mobile,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Payload Agent',
            'address' => '1 Market Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ], $overrides);
    }
}

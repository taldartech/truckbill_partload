<?php

namespace Tests\Feature\Security;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PermissionAndIdorTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_access_protected_resources(): void
    {
        $this->get(route('parties.index'))->assertRedirect(route('login'));
        $this->get(route('bilties.index'))->assertRedirect(route('login'));
        $this->get(route('accounting.vouchers.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_parties(): void
    {
        $context = $this->createTenantContext([]);

        $this->actingAs($context['user'])
            ->get(route('parties.index'))
            ->assertForbidden();
    }

    public function test_user_cannot_view_another_company_party_by_id(): void
    {
        $a = $this->createTenantContext(['view parties'], 'A');
        $b = $this->createTenantContext(['view parties'], 'B');

        $partyB = Party::create([
            'company_id' => $b['company']->id,
            'name' => 'Secret Party B',
        ]);

        $this->actingAs($a['user'])
            ->get(route('parties.show', $partyB))
            ->assertNotFound();
    }

    public function test_user_cannot_update_another_company_party(): void
    {
        $a = $this->createTenantContext(['edit parties', 'view parties'], 'A');
        $b = $this->createTenantContext(['edit parties'], 'B');

        $partyB = Party::create([
            'company_id' => $b['company']->id,
            'name' => 'Party B',
        ]);

        $this->actingAs($a['user'])
            ->put(route('parties.update', $partyB), [
                'name' => 'Hacked Name',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('parties', [
            'id' => $partyB->id,
            'name' => 'Party B',
        ]);
    }

    public function test_bulk_upload_rejects_non_csv_or_excel_extensions(): void
    {
        $context = $this->createTenantContext(['create goods']);

        $file = UploadedFile::fake()->createWithContent('goods.exe', 'malicious');

        $this->actingAs($context['user'])
            ->post(route('goods.bulk-upload'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }
}

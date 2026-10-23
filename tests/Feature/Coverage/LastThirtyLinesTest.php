<?php

namespace Tests\Feature\Coverage;

use App\Enums\UserRole;
use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Good;
use App\Models\Invoice;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\Station;
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class LastThirtyLinesTest extends TestCase
{
    use BuildsAccountingContext;
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_master_admin_updating_super_admin_restricts_assignable_roles(): void
    {
        Role::findOrCreate(UserRole::MasterAdmin->value);
        Role::findOrCreate(UserRole::SuperAdmin->value);
        foreach (['edit users', 'view users'] as $p) {
            Permission::findOrCreate($p);
        }

        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);
        $master->givePermissionTo(['edit users', 'view users']);

        $super = User::factory()->create(['name' => 'Super Target']);
        $super->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($master)
            ->put(route('users.update', $super), [
                'name' => 'Super Target Updated',
                'email' => $super->email,
                'role' => UserRole::SuperAdmin->value,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_super_admin_company_ownership_validation_on_user_create(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        Role::findOrCreate(UserRole::Admin->value);
        foreach (['create users', 'view users'] as $p) {
            Permission::findOrCreate($p);
        }

        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        $owner->givePermissionTo(['create users', 'view users']);
        $owned = Company::create(['name' => 'Mine', 'user_id' => $owner->id]);
        $ownedBranch = Branch::withoutGlobalScopes()->create([
            'company_id' => $owned->id,
            'name' => 'Mine Branch',
            'address' => 'x',
            'pin_code' => '313001',
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'mine@example.com',
        ]);

        $other = User::factory()->create();
        $foreign = Company::create(['name' => 'Theirs', 'user_id' => $other->id]);
        $foreignBranch = Branch::withoutGlobalScopes()->create([
            'company_id' => $foreign->id,
            'name' => 'Their Branch',
            'address' => 'x',
            'pin_code' => '313001',
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'theirs@example.com',
        ]);

        CompanySubscription::create([
            'company_id' => $owned->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $this->actingAs($owner)->withSession([
            'active_company_id' => $owned->id,
            'active_branch_id' => $ownedBranch->id,
        ])->post(route('users.store'), [
            'name' => 'X',
            'email' => 'x-last@example.com',
            'password' => 'password123',
            'role' => UserRole::Admin->value,
            'company_id' => $foreign->id,
            'branch_id' => $foreignBranch->id,
        ])->assertSessionHasErrors('company_id');
    }

    public function test_invoice_update_rejects_foreign_company_bilty(): void
    {
        $a = $this->accountingContext(['edit invoices', 'view invoices'], 'UA');
        $b = $this->accountingContext(['edit invoices'], 'UB');
        app(EnsureCompanyAccounting::class)->handle($a['company']);

        $partyA = Party::create(['company_id' => $a['company']->id, 'name' => 'A']);
        $partyB = Party::create(['company_id' => $b['company']->id, 'name' => 'B']);
        $good = Good::create(['owner_id' => $a['owner']->id, 'name' => 'G']);
        $package = PackageType::create(['owner_id' => $a['owner']->id, 'name' => 'P']);

        $local = Bilty::create([
            'company_id' => $a['company']->id,
            'branch_id' => $a['branch']->id,
            'bilty_number' => 'BLT-LOCAL',
            'date' => now()->toDateString(),
            'from_station_id' => $a['fromStation']->id,
            'to_station_id' => $a['toStation']->id,
            'consignor_id' => $partyA->id,
            'consignee_id' => $partyA->id,
            'payment_type' => 'TBB',
            'freight' => 100,
            'amount' => 100,
            'status' => 'Pending',
        ]);
        BiltyDetail::create([
            'company_id' => $a['company']->id,
            'branch_id' => $a['branch']->id,
            'bilty_id' => $local->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
            'no_of_packages' => 1,
            'gross_weight' => 1,
            'charged_weight' => 1,
            'rate' => 100,
            'rate_type' => 'Fixed',
        ]);

        $foreign = Bilty::withoutGlobalScopes()->create([
            'company_id' => $b['company']->id,
            'branch_id' => $b['branch']->id,
            'bilty_number' => 'BLT-FOR',
            'date' => now()->toDateString(),
            'from_station_id' => $b['fromStation']->id,
            'to_station_id' => $b['toStation']->id,
            'consignor_id' => $partyB->id,
            'consignee_id' => $partyB->id,
            'payment_type' => 'TBB',
            'freight' => 100,
            'amount' => 100,
            'status' => 'Pending',
        ]);

        $invoice = Invoice::create([
            'company_id' => $a['company']->id,
            'branch_id' => $a['branch']->id,
            'invoice_no' => 'INV-LAST',
            'date' => now()->toDateString(),
            'party_id' => $partyA->id,
            'total_amount' => 100,
            'gst_amount' => 0,
            'net_amount' => 100,
        ]);
        $local->update(['invoice_number' => $invoice->invoice_no]);

        $this->actingAs($a['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $partyA->id,
                'remark' => null,
                'bilty_ids' => [$local->id, $foreign->id],
            ])
            ->assertSessionHasErrors('bilty_ids');
    }

    public function test_bilty_print_covers_bank_advance_and_empty_eway(): void
    {
        $context = $this->accountingContext(['view bilties', 'create bilties']);
        $party = Party::create(['company_id' => $context['company']->id, 'name' => 'Print Party']);
        $good = Good::create(['owner_id' => $context['owner']->id, 'name' => 'G2']);
        $package = PackageType::create(['owner_id' => $context['owner']->id, 'name' => 'P2']);

        $bilty = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => 'BLT-PRINT-ADV',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'payment_type' => 'Paid',
            'payment_mode' => 'Cash',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
            'less_advance_received' => 50,
            'advance_payment_mode' => 'Bank',
            'gst' => 18,
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 45,
        ]);
        BiltyDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
            'no_of_packages' => 1,
            'gross_weight' => 10,
            'charged_weight' => 10,
            'rate' => 500,
            'rate_type' => 'Fixed',
        ]);

        $this->actingAs($context['user'])
            ->get(route('bilties.print', $bilty))
            ->assertOk();

        $bilty->update(['advance_payment_mode' => 'Cash']);
        $this->actingAs($context['user'])
            ->get(route('bilties.print', $bilty))
            ->assertOk();
    }

    public function test_party_bulk_upload_without_bilty_charge_column(): void
    {
        $context = $this->createTenantContext(['create parties']);
        $csv = "Party Name,Address,State,City,Mobile\n"
            ."NoCharge Party,1 Road,Rajasthan,Udaipur,9876543210\n";
        $file = UploadedFile::fake()->createWithContent('p.csv', $csv);

        $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('summary.successful', 1);
    }

    public function test_station_show_without_company_context(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        Permission::findOrCreate('view stations');
        $owner->givePermissionTo('view stations');

        $station = Station::create([
            'owner_id' => $owner->id,
            'name' => 'No Co Station',
        ]);

        $this->actingAs($owner)->withSession([])
            ->get(route('stations.show', $station))
            ->assertOk();
    }

    public function test_branch_context_rejects_foreign_company_branch_for_super_admin(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        Company::create(['name' => 'Own', 'user_id' => $owner->id]);

        $other = User::factory()->create();
        $foreign = Company::create(['name' => 'For', 'user_id' => $other->id]);
        $branch = Branch::withoutGlobalScopes()->create([
            'company_id' => $foreign->id,
            'name' => 'For Branch',
            'address' => 'x',
            'pin_code' => '313001',
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'for@example.com',
        ]);

        $this->actingAs($owner)
            ->post(route('branch-context.store'), ['branch_id' => $branch->id])
            ->assertNotFound();
    }
}

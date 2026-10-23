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
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class FinalLineCoverageTest extends TestCase
{
    use BuildsAccountingContext;
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_invoice_store_rejects_other_company_bilty_after_exists_validation(): void
    {
        $a = $this->accountingContext(['create invoices'], 'A');
        $b = $this->accountingContext(['create invoices'], 'B');
        app(EnsureCompanyAccounting::class)->handle($a['company']);

        $partyA = Party::create(['company_id' => $a['company']->id, 'name' => 'A Party']);
        $partyB = Party::create(['company_id' => $b['company']->id, 'name' => 'B Party']);
        $foreign = Bilty::withoutGlobalScopes()->create([
            'company_id' => $b['company']->id,
            'branch_id' => $b['branch']->id,
            'bilty_number' => 'BLT-FOREIGN',
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

        $this->actingAs($a['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-FOREIGN',
                'date' => now()->toDateString(),
                'party_id' => $partyA->id,
                'remark' => null,
                'bilty_ids' => [$foreign->id],
            ])
            ->assertSessionHasErrors('bilty_ids');
    }

    public function test_invoice_update_covers_rate_types_and_addon_charges(): void
    {
        $context = $this->accountingContext(['create invoices', 'edit invoices', 'view invoices']);
        app(EnsureCompanyAccounting::class)->handle($context['company']);
        $party = Party::create(['company_id' => $context['company']->id, 'name' => 'Upd Party']);
        $good = Good::create(['owner_id' => $context['owner']->id, 'name' => 'G']);
        $package = PackageType::create(['owner_id' => $context['owner']->id, 'name' => 'P']);

        $bilty = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => 'BLT-UPD-RATE',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'payment_type' => 'TBB',
            'freight' => 100,
            'amount' => 100,
            'status' => 'Pending',
            'gst' => 0,
            'addon_charges' => [
                ['label' => 'Extra', 'amount' => 10],
                ['label' => 'NoAmount'],
            ],
        ]);

        $d1 = BiltyDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
            'no_of_packages' => 2,
            'gross_weight' => 10,
            'charged_weight' => 12,
            'rate' => 5,
            'rate_type' => 'Fixed',
        ]);
        $d2 = BiltyDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
            'no_of_packages' => 3,
            'gross_weight' => 8,
            'charged_weight' => 9,
            'rate' => 4,
            'rate_type' => 'Fixed',
        ]);
        $d3 = BiltyDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
            'no_of_packages' => 4,
            'gross_weight' => 6,
            'charged_weight' => 7,
            'rate' => 3,
            'rate_type' => 'Fixed',
        ]);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-UPD-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'total_amount' => 100,
            'gst_amount' => 0,
            'net_amount' => 100,
        ]);
        $bilty->update(['invoice_number' => $invoice->invoice_no]);

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $party->id,
                'remark' => null,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    (string) $bilty->id => [
                        ['id' => $d1->id, 'rate_type' => 'Per KG', 'rate' => 5],
                        ['id' => $d2->id, 'rate_type' => 'Per Charged Weight', 'rate' => 4],
                        ['id' => $d3->id, 'rate_type' => 'Per Package', 'rate' => 3],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_super_admin_cannot_assign_user_to_foreign_company(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        Role::findOrCreate(UserRole::Admin->value);

        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        $company = Company::create(['name' => 'Owned', 'user_id' => $owner->id]);
        $foreignOwner = User::factory()->create();
        $foreign = Company::create(['name' => 'Foreign', 'user_id' => $foreignOwner->id]);
        $branch = Branch::create([
            'company_id' => $foreign->id,
            'name' => 'Foreign Branch',
            'address' => 'x',
            'pin_code' => '313001',
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'f@example.com',
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

        foreach (['create users', 'edit users', 'view users'] as $permission) {
            Permission::findOrCreate($permission);
        }
        $owner->givePermissionTo(['create users', 'edit users', 'view users']);

        $this->actingAs($owner)
            ->withSession(['active_company_id' => $company->id, 'active_branch_id' => null])
            ->post(route('users.store'), [
                'name' => 'Staff',
                'email' => 'staff-final@example.com',
                'password' => 'password123',
                'role' => UserRole::Admin->value,
                'company_id' => $foreign->id,
                'branch_id' => $branch->id,
            ])
            ->assertSessionHasErrors();
    }

    public function test_cannot_assign_super_admin_role_via_user_form(): void
    {
        Role::findOrCreate(UserRole::MasterAdmin->value);
        Role::findOrCreate(UserRole::SuperAdmin->value);

        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);
        foreach (['create users', 'view users'] as $permission) {
            Permission::findOrCreate($permission);
        }
        $master->givePermissionTo(['create users', 'view users']);

        $this->actingAs($master)
            ->post(route('users.store'), [
                'name' => 'Nope',
                'email' => 'nope-final@example.com',
                'password' => 'password123',
                'role' => UserRole::SuperAdmin->value,
            ])
            ->assertSessionHasErrors();
    }

    public function test_party_bulk_upload_clears_blank_bilty_charge(): void
    {
        $context = $this->createTenantContext(['create parties']);
        $csv = "Party Name,Address,State,City,Mobile,Bilty Charge\n"
            ."Charge Party,1 Road,Rajasthan,Udaipur,9876543210,\n";
        $file = UploadedFile::fake()->createWithContent('p.csv', $csv);

        $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('summary.successful', 1);
    }
}

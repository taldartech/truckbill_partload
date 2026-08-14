<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_invoice_show(): void
    {
        $context = $this->createShowContext(['view invoices']);

        $response = $this->actingAs($context['user'])
            ->get(route('invoices.show', $context['invoice']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Invoices/Show')
            ->where('invoice.id', $context['invoice']->id)
            ->where('invoice.invoice_no', 'SHOW-INV-100')
            ->where('invoice.party.name', 'Show Party')
            ->has('invoice.details', 1)
            ->where('invoice.details.0.bilty.bilty_number', 'SHOW-BLT-100')
            ->where('invoice.details.0.bilty.from_station.name', 'From Show')
            ->where('invoice.details.0.bilty.to_station.name', 'To Show')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_invoice_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('invoices.show', $context['invoice']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, invoice: Invoice}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Invoice Show Co',
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

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $fromStation = Station::create(['name' => 'From Show', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'To Show', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Invoice Show Branch',
            'address' => '1 Invoice Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'invoice-show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $party = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Party',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignee',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'SHOW-BLT-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $party->id,
            'consignee_id' => $consignee->id,
            'payment_type' => 'TBB',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
            'invoice_number' => 'SHOW-INV-100',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'invoice_no' => 'SHOW-INV-100',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 500,
            'gst_amount' => 0,
            'net_amount' => 500,
        ]);

        InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'bilty_id' => $bilty->id,
            'amount' => 500,
        ]);

        return [
            'user' => $user,
            'invoice' => $invoice,
        ];
    }
}

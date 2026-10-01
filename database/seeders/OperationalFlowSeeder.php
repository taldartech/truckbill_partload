<?php

namespace Database\Seeders;

use App\Enums\AccountingVoucherType;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\Company;
use App\Models\CompanyStationAgent;
use App\Models\CompanySubscription;
use App\Models\DeliveryReceipt;
use App\Models\Good;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;
use App\Models\UserOnboarding;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\VoucherService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperationalFlowSeeder extends Seeder
{
    private const DEMO_SUPER_ADMIN_EMAIL = 'super@example.com';

    private const DEMO_OPS_EMAIL = 'admin@example.com';

    private const DEMO_COMPANY_NAME = 'Shree Ganesh Transport Corporation';

    /** @var array<string, int> */
    private array $counts = [];

    public function run(): void
    {
        $this->ensurePrerequisites();

        DB::transaction(function (): void {
            $this->purgeDemoData();

            $geo = $this->resolveGeoIds();
            $superAdmin = $this->createSuperAdmin();
            $company = $this->createCompany($superAdmin, $geo);
            $branch = $this->createBranch($company, $geo);
            $this->createOpsUser($company, $branch);
            $this->createSubscription($company);

            $masters = $this->createMasterData($superAdmin, $company, $geo);
            $branch->update(['station_id' => $masters['stations'][0]->id]);

            $accounting = $this->seedAccountingFoundation($company, $branch, $masters);
            $seeded = $this->seedOperationalRecords($company, $branch, $masters, $accounting);
            $this->seedAccountingPostings($company, $branch, $masters, $accounting, $seeded);
        });

        $this->printSummary();
    }

    private function ensurePrerequisites(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CountriesSeeder::class,
            StatesSeeder::class,
            CitiesSeeder::class,
        ]);
    }

    private function purgeDemoData(): void
    {
        $demoEmails = [self::DEMO_SUPER_ADMIN_EMAIL, self::DEMO_OPS_EMAIL];

        $demoUserIds = User::query()
            ->whereIn('email', $demoEmails)
            ->pluck('id');

        $companyIds = Company::withTrashed()
            ->where(function ($query) use ($demoUserIds) {
                if ($demoUserIds->isNotEmpty()) {
                    $query->whereIn('user_id', $demoUserIds);
                }

                $query->orWhere('name', self::DEMO_COMPANY_NAME)
                    ->orWhere('email', 'accounts@shreeganeshtransport.in');
            })
            ->pluck('id');

        if ($demoUserIds->isNotEmpty()) {
            UserOnboarding::query()->whereIn('user_id', $demoUserIds)->delete();
            Station::withTrashed()->whereIn('owner_id', $demoUserIds)->forceDelete();
            Good::query()->whereIn('owner_id', $demoUserIds)->delete();
            PackageType::query()->whereIn('owner_id', $demoUserIds)->delete();
            TruckType::query()->whereIn('owner_id', $demoUserIds)->delete();
        }

        if ($companyIds->isNotEmpty()) {
            $this->purgeAccountingForCompanies($companyIds->all());
        }

        foreach ($companyIds as $companyId) {
            Company::withTrashed()->whereKey($companyId)->forceDelete();
        }

        User::withTrashed()->whereIn('email', $demoEmails)->forceDelete();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function purgeAccountingForCompanies(array $companyIds): void
    {
        $voucherIds = DB::table('accounting_vouchers')
            ->whereIn('company_id', $companyIds)
            ->pluck('id');

        if ($voucherIds->isNotEmpty()) {
            DB::table('accounting_vouchers')
                ->whereIn('id', $voucherIds)
                ->update(['reversed_voucher_id' => null]);

            DB::table('accounting_voucher_entries')
                ->whereIn('voucher_id', $voucherIds)
                ->delete();

            DB::table('accounting_vouchers')
                ->whereIn('id', $voucherIds)
                ->delete();
        }

        DB::table('voucher_sequences')->whereIn('company_id', $companyIds)->delete();
        DB::table('accounting_mappings')->whereIn('company_id', $companyIds)->delete();

        $accountIds = DB::table('accounts')->whereIn('company_id', $companyIds)->pluck('id');
        if ($accountIds->isNotEmpty()) {
            DB::table('bilties')
                ->whereIn('cartage_account_id', $accountIds)
                ->update(['cartage_account_id' => null]);
        }

        DB::table('accounts')->whereIn('company_id', $companyIds)->delete();
        DB::table('account_groups')->whereIn('company_id', $companyIds)->delete();
        DB::table('financial_years')->whereIn('company_id', $companyIds)->delete();
    }

    /**
     * @return array{country_id: int, state_id: int, city_id: int}
     */
    private function resolveGeoIds(): array
    {
        $countryId = DB::table('countries')->where('name', 'India')->value('id');
        $stateId = DB::table('states')
            ->where('country_id', $countryId)
            ->where('name', 'Maharashtra')
            ->value('id');
        $cityId = DB::table('cities')
            ->where('state_id', $stateId)
            ->where('name', 'like', '%Mumbai%')
            ->orderBy('name')
            ->value('id');

        if (! $countryId || ! $stateId || ! $cityId) {
            throw new \RuntimeException('India/Maharashtra/Mumbai geo data is required. Run base seeders first.');
        }

        return [
            'country_id' => (int) $countryId,
            'state_id' => (int) $stateId,
            'city_id' => (int) $cityId,
        ];
    }

    private function createSuperAdmin(): User
    {
        $user = User::create([
            'name' => 'Rajesh Mehta',
            'email' => self::DEMO_SUPER_ADMIN_EMAIL,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
            'mobile' => '9820012345',
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        return $user;
    }

    /**
     * @param  array{country_id: int, state_id: int, city_id: int}  $geo
     */
    private function createCompany(User $owner, array $geo): Company
    {
        return Company::create([
            'name' => self::DEMO_COMPANY_NAME,
            'email' => 'accounts@shreeganeshtransport.in',
            'address' => 'Plot No. 42, MIDC Road, Andheri East, Mumbai - 400069',
            'user_id' => $owner->id,
            'city_id' => $geo['city_id'],
            'state_id' => $geo['state_id'],
            'country_id' => $geo['country_id'],
            'pin_code' => '400069',
            'pan_no' => 'AABCS1234F',
            'contact_person' => 'Rajesh Mehta',
            'contact_number' => '02226891234',
        ]);
    }

    /**
     * @param  array{country_id: int, state_id: int, city_id: int}  $geo
     */
    private function createBranch(Company $company, array $geo): Branch
    {
        return tap(Branch::create([
            'company_id' => $company->id,
            'name' => 'Mumbai Head Office',
            'address' => 'Andheri East, Mumbai',
            'city_id' => $geo['city_id'],
            'state_id' => $geo['state_id'],
            'country_id' => $geo['country_id'],
            'pin_code' => '400069',
            'is_head_office' => true,
            'has_gst' => true,
            'gst_number' => '27AABCS1234F1Z8',
            'gst_type' => 'Regular',
            'gst_scheme' => 'Monthly',
            'gst_slab' => '18',
            'use_company_logo' => true,
            'contact_person' => 'Priya Sharma',
            'contact_number' => '9820098765',
            'email' => 'mumbai@shreeganeshtransport.in',
        ]), function (Branch $branch): void {
            $branch->settings()->update([
                'bilty_initial' => 'SGT/',
                'challan_initial' => 'CH/',
                'main_challan_initial' => 'MCH/',
                'invoice_initial' => 'INV/',
                'inward_register_initial' => 'IR/',
            ]);
        });
    }

    private function createOpsUser(Company $company, Branch $branch): User
    {
        $user = User::create([
            'name' => 'Priya Sharma',
            'email' => self::DEMO_OPS_EMAIL,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'mobile' => '9820098765',
        ]);

        $user->assignRole(UserRole::Admin->value);

        return $user;
    }

    private function createSubscription(Company $company): void
    {
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addYear(),
            'trial_ends_on' => Carbon::today()->subWeek(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
            'last_renewed_at' => now(),
        ]);
    }

    /**
     * @param  array{country_id: int, state_id: int, city_id: int}  $geo
     * @return array<string, mixed>
     */
    private function createMasterData(User $owner, Company $company, array $geo): array
    {
        $ownerId = $owner->id;
        $companyId = $company->id;

        $stationNames = [
            'Mumbai', 'Delhi', 'Pune', 'Ahmedabad', 'Surat', 'Jaipur',
            'Indore', 'Nagpur', 'Hyderabad', 'Bengaluru', 'Chennai', 'Kolkata',
        ];

        $agents = $this->seedCollection($this->agentNames(), fn (string $name, int $i) => Agent::create([
            'company_id' => $companyId,
            'name' => $name,
            'address' => 'Agent Office, Transport Nagar',
            'mobile' => '98'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT),
            'state_id' => $geo['state_id'],
            'city_id' => $geo['city_id'],
            'gst_no' => '27AABCA'.str_pad((string) $i, 4, '0', STR_PAD_LEFT).'Z'.($i % 9 + 1),
            'consider_for_accounting' => true,
        ]));

        $stations = $this->seedCollection($stationNames, fn (string $name, int $i) => Station::create([
            'owner_id' => $ownerId,
            'name' => $name,
            'country_id' => $geo['country_id'],
            'state_id' => $geo['state_id'],
            'city_id' => $geo['city_id'],
        ]));

        foreach ($stations as $index => $station) {
            CompanyStationAgent::create([
                'company_id' => $companyId,
                'station_id' => $station->id,
                'agent_id' => $agents[$index % count($agents)]->id,
            ]);
        }

        $goods = $this->seedCollection($this->goodNames(), fn (string $name) => Good::create([
            'owner_id' => $ownerId,
            'name' => $name,
        ]));

        $packageTypes = $this->seedCollection($this->packageTypeNames(), fn (string $name) => PackageType::create([
            'owner_id' => $ownerId,
            'name' => $name,
        ]));

        $truckTypes = $this->seedCollection($this->truckTypeNames(), fn (string $name) => TruckType::create([
            'owner_id' => $ownerId,
            'name' => $name,
        ]));

        $groups = $this->seedCollection($this->groupNames(), fn (string $name) => Group::create([
            'company_id' => $companyId,
            'name' => $name,
        ]));

        $parties = $this->seedCollection($this->partyNames(), function (array $party, int $i) use ($companyId, $groups, $geo) {
            return Party::create([
                'company_id' => $companyId,
                'group_id' => $groups[$i % count($groups)]->id,
                'name' => $party['name'],
                'mobile' => '98'.str_pad((string) (30000000 + $i), 8, '0', STR_PAD_LEFT),
                'gst_number' => $party['gst'] ?? null,
                'address' => $party['address'] ?? 'Demo address',
                'state_id' => $geo['state_id'],
                'city_id' => $geo['city_id'],
                'consider_for_accounting' => true,
            ]);
        });

        $truckNumbers = [
            'MH04AB1234', 'MH12CD5678', 'MH43EF9012', 'GJ01GH3456',
            'GJ06JK7890', 'RJ27AB0001', 'MP09NP6789', 'KA05QR1122',
            'TN07ST3344', 'DL01UV5566', 'MH14WX7788', 'MH31YZ9900',
        ];

        $trucks = $this->seedCollection($truckNumbers, function (string $number, int $i) use (
            $companyId, $truckTypes, $stations, $geo, $agents
        ) {
            return Truck::create([
                'company_id' => $companyId,
                'truck_number' => $number,
                'truck_type_id' => $truckTypes[$i % count($truckTypes)]->id,
                'capacity' => ($i % 2 === 0) ? '15 Ton' : '9 Ton',
                'agent_id' => $agents[$i % count($agents)]->id,
                'status' => $i < 8 ? 'Available' : 'Booked',
                'current_state_id' => $geo['state_id'],
                'current_city_id' => $geo['city_id'],
                'current_station_id' => $stations[0]->id,
            ]);
        });

        $this->counts['stations'] = count($stations);
        $this->counts['goods'] = count($goods);
        $this->counts['package_types'] = count($packageTypes);
        $this->counts['truck_types'] = count($truckTypes);
        $this->counts['groups'] = count($groups);
        $this->counts['parties'] = count($parties);
        $this->counts['agents'] = count($agents);
        $this->counts['trucks'] = count($trucks);

        return compact('stations', 'goods', 'packageTypes', 'truckTypes', 'groups', 'parties', 'agents', 'trucks');
    }

    /**
     * @param  array<string, mixed>  $masters
     * @return array{bank_accounts: list<BankAccount>, cash_account: Account, capital_account: Account, party_accounts: array<int, Account>, agent_accounts: array<int, Account>}
     */
    private function seedAccountingFoundation(Company $company, Branch $branch, array $masters): array
    {
        app(EnsureCompanyAccounting::class)->handle($company);

        $linker = app(EntityAccountLinker::class);
        $chart = app(ChartOfAccountsService::class);
        $years = app(FinancialYearService::class);
        $vouchers = app(VoucherService::class);

        $partyAccounts = [];
        foreach ($masters['parties'] as $party) {
            $partyAccounts[$party->id] = $linker->enable($party);
        }

        $agentAccounts = [];
        foreach ($masters['agents'] as $agent) {
            $agentAccounts[$agent->id] = $linker->enable($agent);
        }

        $bankDefs = [
            ['name' => 'HDFC Current A/c', 'bank_name' => 'HDFC Bank', 'account_number' => '50200012345678', 'ifsc_code' => 'HDFC0001234'],
            ['name' => 'ICICI Operating A/c', 'bank_name' => 'ICICI Bank', 'account_number' => '000401234567', 'ifsc_code' => 'ICIC0000004'],
        ];

        $bankAccounts = [];
        foreach ($bankDefs as $def) {
            $bank = BankAccount::create([
                'company_id' => $company->id,
                ...$def,
                'is_active' => true,
            ]);
            $linker->syncBankAccount($bank);
            $bankAccounts[] = $bank->fresh('account');
        }

        $cash = $chart->systemAccount($company->id, 'cash');
        $capital = $chart->systemAccount($company->id, 'owners_capital');
        $hdfcAccount = $bankAccounts[0]->account;
        $fy = $years->ensureCurrent($company->id);

        $vouchers->post([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => AccountingVoucherType::Opening,
            'date' => $fy->starts_on->toDateString(),
            'narration' => 'Opening balances — demo seed',
            'created_by' => null,
            'updated_by' => null,
        ], [
            ['account_id' => $cash->id, 'debit' => 250000, 'credit' => 0, 'narration' => 'Cash opening'],
            ['account_id' => $hdfcAccount->id, 'debit' => 750000, 'credit' => 0, 'narration' => 'Bank opening'],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 1000000, 'narration' => "Owner's capital"],
        ]);

        $this->counts['bank_accounts'] = count($bankAccounts);
        $this->counts['linked_party_accounts'] = count($partyAccounts);
        $this->counts['linked_agent_accounts'] = count($agentAccounts);
        $this->counts['opening_vouchers'] = 1;

        return [
            'bank_accounts' => $bankAccounts,
            'cash_account' => $cash,
            'capital_account' => $capital,
            'party_accounts' => $partyAccounts,
            'agent_accounts' => $agentAccounts,
        ];
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array{bank_accounts: list<BankAccount>, cash_account: Account, capital_account: Account, party_accounts: array<int, Account>, agent_accounts: array<int, Account>}  $accounting
     * @return array{bilties: Collection, challans: Collection, inward_registers: Collection, invoices: Collection}
     */
    private function seedOperationalRecords(Company $company, Branch $branch, array $masters, array $accounting): array
    {
        $companyId = $company->id;
        $branchId = $branch->id;
        $today = Carbon::today();

        /** @var Collection<int, Station> $stations */
        $stations = collect($masters['stations']);
        /** @var Collection<int, Party> $parties */
        $parties = collect($masters['parties']);
        /** @var Collection<int, Truck> $trucks */
        $trucks = collect($masters['trucks']);
        /** @var Collection<int, Good> $goods */
        $goods = collect($masters['goods']);
        /** @var Collection<int, PackageType> $packageTypes */
        $packageTypes = collect($masters['packageTypes']);

        $mumbai = $stations->firstWhere('name', 'Mumbai');
        $consignors = $parties->values();
        $consignees = $parties->values();
        $drivers = $this->driverNames();
        $routes = [
            ['from' => 'Mumbai', 'to' => 'Delhi'],
            ['from' => 'Mumbai', 'to' => 'Pune'],
            ['from' => 'Mumbai', 'to' => 'Ahmedabad'],
            ['from' => 'Mumbai', 'to' => 'Surat'],
            ['from' => 'Mumbai', 'to' => 'Jaipur'],
            ['from' => 'Mumbai', 'to' => 'Indore'],
            ['from' => 'Mumbai', 'to' => 'Nagpur'],
            ['from' => 'Mumbai', 'to' => 'Hyderabad'],
            ['from' => 'Mumbai', 'to' => 'Bengaluru'],
            ['from' => 'Mumbai', 'to' => 'Chennai'],
            ['from' => 'Delhi', 'to' => 'Mumbai'],
            ['from' => 'Pune', 'to' => 'Mumbai'],
        ];
        $paymentTypes = ['To Pay', 'Paid', 'TBB'];
        $statuses = ['Pending', 'Pending', 'Pending', 'In Transit', 'Pending'];

        $bilties = collect();
        $agents = collect($masters['agents']);
        $agentAccounts = $accounting['agent_accounts'];

        for ($i = 1; $i <= 24; $i++) {
            $route = $routes[($i - 1) % count($routes)];
            $fromStation = $stations->firstWhere('name', $route['from']);
            $toStation = $stations->firstWhere('name', $route['to']);
            $paymentType = $paymentTypes[$i % count($paymentTypes)];
            $freight = 1800 + ($i * 175);
            $packages = 4 + ($i % 12);
            $weight = 350 + ($i * 45);

            $cartageAttrs = [];
            if ($i % 4 === 0) {
                $agent = $agents[($i / 4) % $agents->count()];
                $cartageAttrs = [
                    'cartage' => 250 + ($i * 15),
                    'cartage_type' => 'Due',
                    'cartage_account_id' => $agentAccounts[$agent->id]->id,
                ];
            }

            $bilties->push($this->createBilty([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'bilty_number' => 'SGT/'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'consignor_id' => $consignors[$i % $consignors->count()]->id,
                'consignee_id' => $consignees[($i + 2) % $consignees->count()]->id,
                'from_station_id' => $fromStation->id,
                'to_station_id' => $toStation->id,
                'date' => $today->copy()->subDays(30 - $i),
                'freight' => $freight,
                'amount' => $freight + (float) ($cartageAttrs['cartage'] ?? 0),
                'payment_type' => $paymentType,
                'status' => $statuses[$i % count($statuses)],
                ...$cartageAttrs,
            ], $goods[$i % $goods->count()], $packageTypes[$i % $packageTypes->count()], $packages, (float) $weight));
        }

        $this->counts['bilties'] = $bilties->count();

        $pendingBilties = $bilties->filter(fn (Bilty $b) => $b->status === 'Pending')->values();

        $inTransitBilties = $bilties->filter(fn (Bilty $b) => $b->status === 'In Transit')->values();
        $challanBilties = $inTransitBilties->take(3);
        if ($challanBilties->count() < 3) {
            $challanBilties = $pendingBilties->slice(5, 3);
        }

        $challans = collect();
        foreach ($challanBilties->values() as $index => $bilty) {
            $challanNo = $index + 1;
            $detail = $bilty->biltyDetails->first();
            $packages = (int) ($detail?->no_of_packages ?? 10);
            $weight = (float) ($detail?->gross_weight ?? 1000);

            $challan = Challan::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'from_station_id' => $bilty->from_station_id,
                'to_station_id' => $bilty->to_station_id,
                'truck_id' => $trucks[$index % $trucks->count()]->id,
                'driver_name' => $drivers[($index + 3) % count($drivers)],
                'challan_number' => 'CH/'.str_pad((string) $challanNo, 4, '0', STR_PAD_LEFT),
                'date' => $today->copy()->subDays(5 - $index),
                'total_weight' => $weight,
                'total_packages' => $packages,
                'to_pay_amount' => $bilty->payment_type === 'To Pay' ? $bilty->freight : 0,
                'booking_freight' => $bilty->freight,
                'crossing_freight' => 0,
                'total_amount' => $bilty->freight,
                'pay_by_agent_id' => $agents[$index % $agents->count()]->id,
                'status' => $index === 0 ? 'In Progress' : 'Created',
            ]);

            ChallanDetail::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'challan_id' => $challan->id,
                'bilty_id' => $bilty->id,
                'to_station_id' => $bilty->to_station_id,
            ]);

            $challans->push($challan);
        }

        $inwardRegisters = collect();
        $allInwardDetails = collect();
        $forwardPendingDetails = collect();
        $deliveryPendingDetails = collect();

        for ($ir = 1; $ir <= 5; $ir++) {
            $fromStation = $stations->firstWhere('name', ['Delhi', 'Pune', 'Ahmedabad', 'Jaipur', 'Nagpur'][$ir - 1]);
            $truck = $trucks[($ir + 2) % $trucks->count()];
            $register = InwardRegister::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'inward_register_no' => 'IR/'.str_pad((string) $ir, 4, '0', STR_PAD_LEFT),
                'date' => $today->copy()->subDays(8 - $ir),
                'from_station_id' => $fromStation->id,
                'to_station_id' => $mumbai->id,
                'challan_number' => 'EXT/'.(7800 + $ir),
                'challan_date' => $today->copy()->subDays(9 - $ir),
                'driver_name' => $drivers[($ir + 5) % count($drivers)],
                'truck_id' => $truck->id,
                'total_packages' => 0,
                'total_delivery_freight' => 0,
                'total_crossing_freight' => 0,
            ]);

            $registerPackages = 0;
            $registerDeliveryFreight = 0;
            $registerCrossingFreight = 0;

            for ($item = 1; $item <= 12; $item++) {
                $seq = (($ir - 1) * 12) + $item;
                $isDelivery = $item % 3 === 0;
                $toStation = $isDelivery ? $mumbai : $stations[($seq + $ir) % $stations->count()];
                $packages = 3 + ($seq % 8);
                $weight = 200 + ($seq * 18);
                $freight = 500 + ($seq * 35);
                $homeDelivery = $isDelivery ? (150 + ($seq % 4) * 50) : 0;

                $detail = InwardRegisterDetail::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'inward_register_id' => $register->id,
                    'gr_no' => 'GR/'.str_pad((string) (1000 + $seq), 5, '0', STR_PAD_LEFT),
                    'date' => $register->date,
                    'from_station_id' => $fromStation->id,
                    'to_station_id' => $toStation->id,
                    'consignor_id' => $consignors[$seq % $consignors->count()]->id,
                    'consignee_id' => $consignees[($seq + 1) % $consignees->count()]->id,
                    'good_id' => $goods[$seq % $goods->count()]->id,
                    'no_of_packages' => $packages,
                    'weight' => $weight,
                    'basis_of_booking' => $paymentTypes[$seq % count($paymentTypes)],
                    'freight' => $freight,
                    'home_delivery_charge' => $homeDelivery,
                    'agent_id' => $agents[$seq % $agents->count()]->id,
                    'disposition' => $isDelivery ? 'delivery' : 'forward',
                    'status' => 'pending',
                ]);

                $registerPackages += $packages;
                if ($isDelivery) {
                    $registerDeliveryFreight += $freight + $homeDelivery;
                } else {
                    $registerCrossingFreight += $freight;
                }

                $allInwardDetails->push($detail);
                if ($isDelivery) {
                    $deliveryPendingDetails->push($detail);
                } else {
                    $forwardPendingDetails->push($detail);
                }
            }

            $register->update([
                'total_packages' => $registerPackages,
                'total_delivery_freight' => $registerDeliveryFreight,
                'total_crossing_freight' => $registerCrossingFreight,
            ]);

            $inwardRegisters->push($register);
        }

        $this->counts['inward_registers'] = $inwardRegisters->count();
        $this->counts['inward_details'] = $allInwardDetails->count();

        $forwardForChallan = $forwardPendingDetails->slice(0, 2);
        foreach ($forwardForChallan->values() as $index => $detail) {
            $challanNo = $challans->count() + $index + 1;
            $challan = Challan::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'from_station_id' => $mumbai->id,
                'to_station_id' => $detail->to_station_id,
                'truck_id' => $trucks[($index + 6) % $trucks->count()]->id,
                'driver_name' => $drivers[($index + 8) % count($drivers)],
                'challan_number' => 'CH/'.str_pad((string) $challanNo, 4, '0', STR_PAD_LEFT),
                'date' => $today->copy()->subDays(3 - $index),
                'total_weight' => $detail->weight,
                'total_packages' => $detail->no_of_packages,
                'to_pay_amount' => $detail->basis_of_booking === 'To Pay' ? $detail->freight : 0,
                'booking_freight' => 0,
                'crossing_freight' => $detail->freight,
                'total_amount' => $detail->freight,
                'pay_by_agent_id' => $detail->agent_id,
                'status' => 'Created',
            ]);

            ChallanDetail::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'challan_id' => $challan->id,
                'inward_register_detail_id' => $detail->id,
                'to_station_id' => $detail->to_station_id,
            ]);

            $detail->update(['status' => 'challaned']);
            $challans->push($challan);
        }
        $this->counts['challans'] = $challans->count();

        $mainChallans = collect();
        $challanChunks = $challans->chunk(max(1, (int) ceil($challans->count() / 3)));
        foreach ($challanChunks->take(3)->values() as $mcIndex => $chunk) {
            if ($chunk->isEmpty()) {
                continue;
            }

            $first = $chunk->first();
            $totalWeight = $chunk->sum('total_weight');
            $totalPackages = $chunk->sum('total_packages');
            $totalAmount = $chunk->sum('total_amount');
            $toPay = $chunk->sum('to_pay_amount');

            $mainChallan = MainChallan::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'main_challan_number' => 'MCH/'.str_pad((string) ($mcIndex + 1), 4, '0', STR_PAD_LEFT),
                'date' => $today->copy()->subDays(2 - $mcIndex),
                'from_station_id' => $first->from_station_id,
                'to_station_id' => $first->to_station_id,
                'truck_id' => $first->truck_id,
                'driver_name' => $first->driver_name,
                'total_weight' => $totalWeight,
                'total_packages' => $totalPackages,
                'to_pay_amount' => $toPay,
                'crossing_freight' => $chunk->sum('crossing_freight'),
                'total_amount' => $totalAmount,
            ]);

            foreach ($chunk as $challan) {
                MainChallanDetail::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'main_challan_id' => $mainChallan->id,
                    'challan_id' => $challan->id,
                ]);
            }

            $mainChallans->push($mainChallan);
        }
        $this->counts['main_challans'] = $mainChallans->count();

        $deliveryReceipts = collect();
        foreach ($deliveryPendingDetails->slice(0, 8)->values() as $drIndex => $detail) {
            $total = $detail->freight + ($detail->home_delivery_charge ?? 0);
            $deliveryReceipts->push(DeliveryReceipt::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'delivery_receipt_no' => 'DR/'.str_pad((string) ($drIndex + 1), 4, '0', STR_PAD_LEFT),
                'date' => $today->copy()->subDays(4 - ($drIndex % 3)),
                'gr_no_bilty_no' => $detail->gr_no,
                'mode_of_payment' => ['Cash', 'UPI', 'Cheque', 'NEFT'][$drIndex % 4],
                'consignee_id' => $detail->consignee_id,
                'freight' => $detail->freight,
                'delivery_charge' => $detail->home_delivery_charge ?? 0,
                'stationery_charge' => 0,
                'carriage_charge' => 0,
                'gst_on' => 'total_charge',
                'gst_rate' => 18,
                'gst_pay_by' => 'Consignee',
                'payment_due_from_id' => $detail->consignee_id,
                'total_amount' => $total,
                'final_amount' => $total,
            ]));
        }
        $this->counts['delivery_receipts'] = $deliveryReceipts->count();

        $tbbBilties = $bilties->filter(fn (Bilty $b) => $b->payment_type === 'TBB' && empty($b->invoice_number))->values();
        $invoices = collect();
        $invoiceGroups = $tbbBilties->groupBy('consignor_id')->take(5);

        foreach ($invoiceGroups as $partyId => $partyBilties) {
            $invoiceNo = 'INV/'.str_pad((string) ($invoices->count() + 1), 4, '0', STR_PAD_LEFT);
            $totalAmount = $partyBilties->sum('freight');
            $gstAmount = round($totalAmount * 0.18, 2);
            $netAmount = $totalAmount + $gstAmount;

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'invoice_no' => $invoiceNo,
                'date' => $today->copy()->subDays(10 - $invoices->count()),
                'party_id' => $partyId,
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'net_amount' => $netAmount,
                'status' => 'Pending',
                'remark' => 'Monthly TBB billing',
            ]);

            foreach ($partyBilties as $bilty) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'bilty_id' => $bilty->id,
                    'amount' => $bilty->freight,
                ]);

                $bilty->update(['invoice_number' => $invoiceNo]);
            }

            $invoices->push($invoice);
        }
        $this->counts['invoices'] = $invoices->count();

        return [
            'bilties' => $bilties,
            'challans' => $challans,
            'inward_registers' => $inwardRegisters,
            'invoices' => $invoices,
        ];
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array{bank_accounts: list<BankAccount>, cash_account: Account, capital_account: Account, party_accounts: array<int, Account>, agent_accounts: array<int, Account>}  $accounting
     * @param  array{bilties: Collection, challans: Collection, inward_registers: Collection, invoices: Collection}  $seeded
     */
    private function seedAccountingPostings(
        Company $company,
        Branch $branch,
        array $masters,
        array $accounting,
        array $seeded,
    ): void {
        $bridge = app(OperationalAccountingBridge::class);
        $vouchers = app(VoucherService::class);
        $posted = 0;

        foreach ($seeded['invoices'] as $invoice) {
            if ($bridge->sync($invoice)) {
                $posted++;
            }
        }

        foreach ($seeded['bilties'] as $bilty) {
            if ($bridge->sync($bilty)) {
                $posted++;
            }
        }

        foreach ($seeded['inward_registers'] as $register) {
            if ($bridge->sync($register->fresh('details.agent'))) {
                $posted++;
            }
        }

        foreach ($seeded['challans'] as $challan) {
            if ($bridge->sync($challan)) {
                $posted++;
            }
        }

        $cash = $accounting['cash_account'];
        $bank = $accounting['bank_accounts'][0]->account;
        $partyAccounts = $accounting['party_accounts'];
        $receiptCount = 0;

        foreach ($seeded['invoices']->take(3)->values() as $index => $invoice) {
            $partyAccount = $partyAccounts[$invoice->party_id] ?? null;
            if (! $partyAccount) {
                continue;
            }

            $amount = round(((float) $invoice->net_amount) * 0.4, 2);
            if ($amount <= 0) {
                continue;
            }

            $cashAccount = $index % 2 === 0 ? $cash : $bank;

            $vouchers->post([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'voucher_type' => AccountingVoucherType::Receipt,
                'date' => Carbon::parse($invoice->date)->addDays(2)->toDateString(),
                'narration' => 'Receipt against '.$invoice->invoice_no,
                'created_by' => null,
                'updated_by' => null,
            ], [
                ['account_id' => $cashAccount->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Received'],
                ['account_id' => $partyAccount->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Party receipt'],
            ]);
            $receiptCount++;
        }

        $firstAgent = collect($masters['agents'])->first();
        $agentAccount = $accounting['agent_accounts'][$firstAgent->id] ?? null;
        if ($agentAccount) {
            $vouchers->post([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'voucher_type' => AccountingVoucherType::Payment,
                'date' => Carbon::today()->subDay()->toDateString(),
                'narration' => 'Agent settlement — '.$firstAgent->name,
                'created_by' => null,
                'updated_by' => null,
            ], [
                ['account_id' => $agentAccount->id, 'debit' => 5000, 'credit' => 0, 'narration' => 'Paid to agent'],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 5000, 'narration' => 'Bank payment'],
            ]);
            $posted++;
        }

        $this->counts['accounting_source_vouchers'] = $posted;
        $this->counts['receipt_vouchers'] = $receiptCount;
        $this->counts['payment_vouchers'] = $agentAccount ? 1 : 0;
        $this->counts['accounting_vouchers_total'] = \App\Models\AccountingVoucher::query()
            ->where('company_id', $company->id)
            ->count();
    }

    private function createBilty(array $attributes, Good $good, PackageType $packageType, int $packages, float $weight): Bilty
    {
        $bilty = Bilty::create($attributes);

        BiltyDetail::create([
            'company_id' => $bilty->company_id,
            'branch_id' => $bilty->branch_id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => $packages,
            'gross_weight' => $weight,
            'charged_weight' => $weight,
            'rate' => (float) ($attributes['freight'] ?? 0),
            'rate_type' => 'Fixed',
        ]);

        return $bilty->load('biltyDetails');
    }

    /**
     * @template T
     *
     * @param  array<int, mixed>  $items
     * @param  callable(mixed, int): T  $factory
     * @return array<int, T>
     */
    private function seedCollection(array $items, callable $factory): array
    {
        $result = [];
        foreach ($items as $index => $item) {
            $result[] = $factory($item, $index);
        }

        return $result;
    }

    /** @return array<int, string> */
    private function agentNames(): array
    {
        return [
            'Delhi Booking Agency', 'Pune Cargo Associates', 'Ahmedabad Freight Link',
            'Surat Transport Desk', 'Jaipur Goods Agency', 'Indore Parcel Hub',
            'Nagpur Booking Point', 'Hyderabad Cargo Care', 'Bengaluru Load Desk',
            'Chennai Freight Services', 'Kolkata Transport Agency', 'Mumbai City Agency',
        ];
    }

    /** @return array<int, string> */
    private function goodNames(): array
    {
        return [
            'Furniture', 'Electronics', 'Textiles', 'Pharmaceuticals', 'Hardware',
            'Auto Parts', 'Plastic Goods', 'Food Grains', 'Chemical Drums', 'Paper Rolls',
            'Ceramic Tiles', 'Garments',
        ];
    }

    /** @return array<int, string> */
    private function packageTypeNames(): array
    {
        return [
            'Box', 'Bag', 'Carton', 'Bale', 'Drum', 'Crate', 'Bundle', 'Roll', 'Pallet', 'Loose',
        ];
    }

    /** @return array<int, string> */
    private function truckTypeNames(): array
    {
        return [
            '32 FT Container', '20 FT Container', '22 FT Open Body', '14 FT Tata 407',
            '10 Tyre Truck', '12 Tyre Truck', 'Trailer 40 FT', 'Eicher 14 FT',
            'Bolero Pickup', 'Tata Ace',
        ];
    }

    /** @return array<int, string> */
    private function groupNames(): array
    {
        return [
            'Wholesale Traders', 'Retail Customers', 'Corporate Accounts', 'Pharma Clients',
            'Textile Merchants', 'Hardware Dealers', 'FMCG Distributors', 'Export Houses',
            'E-commerce Sellers', 'Industrial Suppliers', 'Agro Commodity', 'Local Retailers',
        ];
    }

    /**
     * @return array<int, array{name: string, gst?: string, address?: string}>
     */
    private function partyNames(): array
    {
        return [
            ['name' => 'Patel Trading Company', 'gst' => '27AABCP1234A1Z5', 'address' => 'Lamington Road, Mumbai'],
            ['name' => 'Sharma Electricals Pvt Ltd', 'gst' => '27AABCS5678B1Z3', 'address' => 'Andheri East, Mumbai'],
            ['name' => 'Mehta Textiles', 'gst' => '27AABCM9012C1Z7', 'address' => 'Dadar, Mumbai'],
            ['name' => 'Jain Hardware Mart', 'gst' => '27AABCJ3456D1Z2', 'address' => 'Bhuleshwar, Mumbai'],
            ['name' => 'Kulkarni Pharma Distributors', 'gst' => '27AABCK7890E1Z9', 'address' => 'Vile Parle, Mumbai'],
            ['name' => 'Desai Furniture Works', 'address' => 'Karol Bagh, Delhi'],
            ['name' => 'Singh Auto Components', 'address' => 'Pimpri, Pune'],
            ['name' => 'Gupta Plastic Industries', 'address' => 'Naroda, Ahmedabad'],
            ['name' => 'Reddy Agro Products', 'address' => 'Secunderabad, Hyderabad'],
            ['name' => 'Iyer Garments Export', 'address' => 'T Nagar, Chennai'],
            ['name' => 'Bose Paper Mills', 'gst' => '27AABCB1122F1Z4', 'address' => 'Howrah, Kolkata'],
            ['name' => 'Verma Industrial Supplies', 'gst' => '27AABCV3344G1Z6', 'address' => 'Vishwakarma Industrial Area, Jaipur'],
        ];
    }

    /** @return array<int, string> */

    /** @return array<int, string> */
    private function driverNames(): array
    {
        return [
            'Ramesh Kumar', 'Suresh Patil', 'Mahesh Yadav', 'Vikram Singh', 'Anil Deshmukh',
            'Sunil Kulkarni', 'Prakash More', 'Dinesh Pawar', 'Ganesh Shinde', 'Harish Chavan',
            'Nitin Bhosale', 'Kiran Naik', 'Santosh Jadhav', 'Yogesh Thakur', 'Deepak Sharma',
        ];
    }

    private function printSummary(): void
    {
        $this->command?->newLine();
        $this->command?->info('Shree Ganesh Transport Corporation — demo data seeded successfully.');
        $this->command?->newLine();
        $this->command?->line('Login credentials (password: password):');
        $this->command?->line('  Super Admin: '.self::DEMO_SUPER_ADMIN_EMAIL.' (Rajesh Mehta)');
        $this->command?->line('  Branch Admin: '.self::DEMO_OPS_EMAIL.' (Priya Sharma, Mumbai Head Office)');
        $this->command?->newLine();
        $this->command?->line('Master data created:');
        foreach (['stations', 'goods', 'package_types', 'truck_types', 'groups', 'parties', 'agents', 'trucks'] as $key) {
            $this->command?->line(sprintf('  %-16s %d', ucwords(str_replace('_', ' ', $key)).':', $this->counts[$key] ?? 0));
        }
        $this->command?->newLine();
        $this->command?->line('Operational records:');
        foreach (['bilties', 'challans', 'main_challans', 'inward_registers', 'inward_details', 'delivery_receipts', 'invoices'] as $key) {
            $this->command?->line(sprintf('  %-16s %d', ucwords(str_replace('_', ' ', $key)).':', $this->counts[$key] ?? 0));
        }
        $this->command?->newLine();
        $this->command?->line('Accounting seeded:');
        foreach ([
            'bank_accounts',
            'linked_party_accounts',
            'linked_agent_accounts',
            'opening_vouchers',
            'accounting_source_vouchers',
            'receipt_vouchers',
            'payment_vouchers',
            'accounting_vouchers_total',
        ] as $key) {
            $this->command?->line(sprintf('  %-28s %d', ucwords(str_replace('_', ' ', $key)).':', $this->counts[$key] ?? 0));
        }
        $this->command?->newLine();
        $this->command?->line('Sample open items for testing:');
        $this->command?->line('  Pending bilties available for new loading reports & challans');
        $this->command?->line('  Inward forward GRs pending challan (not yet challaned)');
        $this->command?->line('  Inward delivery GRs pending delivery receipt');
        $this->command?->line('  TBB bilties without invoice still available for billing');
        $this->command?->line('  Chart of Accounts, Day Book, Trial Balance, Receivables/Payables ready');
        $this->command?->newLine();
    }
}

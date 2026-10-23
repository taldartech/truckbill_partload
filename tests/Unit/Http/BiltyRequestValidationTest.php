<?php

namespace Tests\Unit\Http;

use App\Http\Requests\BiltyRequest;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BiltyRequestValidationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createTenantContext(['create bilties']);
        $this->actingAs($this->context['user']);

        $owner = $this->context['owner'];
        $this->context['to_station'] = Station::create([
            'name' => 'Far Station',
            'owner_id' => $owner->id,
            'state_id' => $this->context['state']->id,
            'city_id' => $this->context['city']->id,
            'country_id' => $this->context['country']->id,
        ]);
        $this->context['consignor'] = $this->makeParty('Consignor');
        $this->context['consignee'] = $this->makeParty('Consignee');
        $this->context['good'] = Good::create(['owner_id' => $owner->id, 'name' => 'Cloth']);
        $this->context['package'] = PackageType::create(['owner_id' => $owner->id, 'name' => 'Bale']);
    }

    public function test_authorize_always_allows(): void
    {
        $this->assertTrue((new BiltyRequest)->authorize());
    }

    public function test_valid_to_pay_payload_passes(): void
    {
        [$errors] = $this->validate($this->validPayload(['payment_type' => 'To Pay']));

        $this->assertSame([], $errors);
    }

    public function test_valid_paid_cash_payload_passes_and_normalizes_mode(): void
    {
        [$errors, $request] = $this->validate($this->validPayload([
            'payment_type' => 'Paid',
            'payment_mode' => 'cash',
        ]));

        $this->assertSame([], $errors);
        $this->assertSame('Cash', $request->input('payment_mode'));
        $this->assertNull($request->input('bank_account_id'));
    }

    public function test_paid_requires_payment_mode(): void
    {
        [$errors] = $this->validate($this->validPayload(['payment_type' => 'Paid']));

        $this->assertArrayHasKey('payment_mode', $errors);
        $this->assertSame(
            'Please select whether the paid amount was received in Cash or Bank.',
            $errors['payment_mode'][0]
        );
    }

    public function test_paid_bank_requires_bank_account(): void
    {
        [$errors] = $this->validate($this->validPayload([
            'payment_type' => 'Paid',
            'payment_mode' => 'Bank',
        ]));

        $this->assertArrayHasKey('bank_account_id', $errors);
    }

    public function test_paid_bank_with_company_bank_account_passes(): void
    {
        $bank = $this->makeBankAccount($this->context['company']->id);

        [$errors, $request] = $this->validate($this->validPayload([
            'payment_type' => 'Paid',
            'payment_mode' => 'bank',
            'bank_account_id' => $bank->id,
        ]));

        $this->assertSame([], $errors);
        $this->assertSame('Bank', $request->input('payment_mode'));
        $this->assertSame($bank->id, $request->input('bank_account_id'));
    }

    public function test_paid_bank_rejects_bank_account_of_another_company(): void
    {
        $other = $this->createTenantContext([], 'B');
        $foreign = $this->makeBankAccount($other['company']->id);

        [$errors] = $this->validate($this->validPayload([
            'payment_type' => 'Paid',
            'payment_mode' => 'Bank',
            'bank_account_id' => $foreign->id,
        ]));

        $this->assertArrayHasKey('bank_account_id', $errors);
    }

    public function test_to_pay_discards_payment_mode_and_bank_account(): void
    {
        $bank = $this->makeBankAccount($this->context['company']->id);

        [$errors, $request] = $this->validate($this->validPayload([
            'payment_type' => 'To Pay',
            'payment_mode' => 'Bank',
            'bank_account_id' => $bank->id,
        ]));

        $this->assertSame([], $errors);
        $this->assertNull($request->input('payment_mode'));
        $this->assertNull($request->input('bank_account_id'));
    }

    public function test_payment_type_must_be_known_value(): void
    {
        [$errors] = $this->validate($this->validPayload(['payment_type' => 'Credit']));

        $this->assertSame('Payment type must be To Pay, Paid, TBB or FOC.', $errors['payment_type'][0]);
    }

    #[DataProvider('nonPaidTypesProvider')]
    public function test_non_paid_types_do_not_require_payment_mode(string $paymentType): void
    {
        [$errors] = $this->validate($this->validPayload(['payment_type' => $paymentType]));

        $this->assertArrayNotHasKey('payment_mode', $errors);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonPaidTypesProvider(): array
    {
        return [
            'to pay' => ['To Pay'],
            'tbb' => ['TBB'],
            'foc' => ['FOC'],
        ];
    }

    public function test_tbb_does_not_require_rate_or_rate_type(): void
    {
        $payload = $this->validPayload(['payment_type' => 'TBB']);
        unset($payload['details'][0]['rate'], $payload['details'][0]['rate_type']);

        [$errors] = $this->validate($payload);

        $this->assertSame([], $errors);
    }

    public function test_non_tbb_requires_rate_and_rate_type(): void
    {
        $payload = $this->validPayload(['payment_type' => 'To Pay']);
        unset($payload['details'][0]['rate'], $payload['details'][0]['rate_type']);

        [$errors] = $this->validate($payload);

        $this->assertArrayHasKey('details.0.rate', $errors);
        $this->assertArrayHasKey('details.0.rate_type', $errors);
    }

    public function test_invalid_rate_type_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['details'][0]['rate_type'] = 'Per Mile';

        [$errors] = $this->validate($payload);

        $this->assertSame('Freight type is invalid.', $errors['details.0.rate_type'][0]);
    }

    public function test_required_fields_report_friendly_messages(): void
    {
        [$errors] = $this->validate([]);

        $this->assertSame('Please select consignor.', $errors['consignor_id'][0]);
        $this->assertSame('Please select consignee.', $errors['consignee_id'][0]);
        $this->assertSame('Please select from station.', $errors['from_station_id'][0]);
        $this->assertSame('Please select to station.', $errors['to_station_id'][0]);
        $this->assertSame('Please select bilty date.', $errors['date'][0]);
        $this->assertSame('Please add at least one detail row.', $errors['details'][0]);
    }

    public function test_details_without_goods_are_rejected_by_after_hook(): void
    {
        $payload = $this->validPayload();
        $payload['details'][0]['good_id'] = '';

        [$errors] = $this->validate($payload);

        $this->assertArrayHasKey('details.0.good_id', $errors);
        $this->assertContains('At least one bilty detail row with goods is required.', $errors['details']);
    }

    public function test_detail_numeric_limits_are_enforced(): void
    {
        $payload = $this->validPayload();
        $payload['details'][0]['no_of_packages'] = 500001;
        $payload['details'][0]['gross_weight'] = 120001;
        $payload['details'][0]['charged_weight'] = -1;

        [$errors] = $this->validate($payload);

        $this->assertSame('Number of packages cannot be greater than 500000.', $errors['details.0.no_of_packages'][0]);
        $this->assertSame('Actual weight cannot be greater than 120000.', $errors['details.0.gross_weight'][0]);
        $this->assertSame('Charged weight cannot be negative.', $errors['details.0.charged_weight'][0]);
    }

    public function test_charge_fields_cannot_be_negative_and_gst_capped(): void
    {
        [$errors] = $this->validate($this->validPayload([
            'freight' => -1,
            'bilty_charge' => -1,
            'surcharge' => -1,
            'handling_charge' => -1,
            'risk_dd_charge' => -1,
            'gst' => 101,
            'cgst' => 101,
            'sgst' => 101,
            'igst' => 101,
        ]));

        foreach (['freight', 'bilty_charge', 'surcharge', 'handling_charge', 'risk_dd_charge', 'gst', 'cgst', 'sgst', 'igst'] as $field) {
            $this->assertArrayHasKey($field, $errors, "{$field} should be rejected");
        }
        $this->assertSame('GST cannot be more than 100%.', $errors['gst'][0]);
    }

    public function test_status_and_gst_liability_must_be_known_values(): void
    {
        [$errors] = $this->validate($this->validPayload([
            'status' => 'Lost',
            'gst_liability_by' => 'Nobody',
        ]));

        $this->assertSame('Status must be Pending, In Transit, Delivered or Cancelled.', $errors['status'][0]);
        $this->assertArrayHasKey('gst_liability_by', $errors);
    }

    public function test_cartage_requires_type_when_positive(): void
    {
        [$errors] = $this->validate($this->validPayload(['cartage' => 100]));

        $this->assertSame('Please select whether cartage is Cash or Due.', $errors['cartage_type'][0]);
    }

    public function test_cartage_due_requires_account(): void
    {
        [$errors, $request] = $this->validate($this->validPayload([
            'cartage' => 100,
            'cartage_type' => 'due',
        ]));

        $this->assertSame('Due', $request->input('cartage_type'));
        $this->assertSame('Please select an account for due cartage.', $errors['cartage_account_id'][0]);
    }

    public function test_cartage_due_with_company_account_passes(): void
    {
        $account = $this->makeAccount($this->context['company']->id);

        [$errors] = $this->validate($this->validPayload([
            'cartage' => 100,
            'cartage_type' => 'Due',
            'cartage_account_id' => $account->id,
        ]));

        $this->assertSame([], $errors);
    }

    public function test_cartage_cash_drops_account_and_zero_cartage_clears_type(): void
    {
        $account = $this->makeAccount($this->context['company']->id);

        [$errors, $request] = $this->validate($this->validPayload([
            'cartage' => 100,
            'cartage_type' => 'cash',
            'cartage_account_id' => $account->id,
        ]));
        $this->assertSame([], $errors);
        $this->assertSame('Cash', $request->input('cartage_type'));
        $this->assertNull($request->input('cartage_account_id'));

        [$errors, $request] = $this->validate($this->validPayload([
            'cartage' => 0,
            'cartage_type' => 'Due',
            'cartage_account_id' => $account->id,
        ]));
        $this->assertSame([], $errors);
        $this->assertNull($request->input('cartage_type'));
        $this->assertNull($request->input('cartage_account_id'));
    }

    public function test_cartage_account_from_other_company_is_rejected(): void
    {
        $other = $this->createTenantContext([], 'B');
        $foreign = $this->makeAccount($other['company']->id);

        [$errors] = $this->validate($this->validPayload([
            'cartage' => 100,
            'cartage_type' => 'Due',
            'cartage_account_id' => $foreign->id,
        ]));

        $this->assertArrayHasKey('cartage_account_id', $errors);
    }

    public function test_advance_requires_payment_mode_when_amount_positive(): void
    {
        [$errors] = $this->validate($this->validPayload(['less_advance_received' => 500]));

        $this->assertSame('Please select payment mode for advance received.', $errors['advance_payment_mode'][0]);
    }

    public function test_advance_bank_requires_bank_account(): void
    {
        [$errors, $request] = $this->validate($this->validPayload([
            'less_advance_received' => 500,
            'advance_payment_mode' => 'bank',
        ]));

        $this->assertSame('Bank', $request->input('advance_payment_mode'));
        $this->assertSame(
            'Please select the bank account the advance was received in.',
            $errors['advance_bank_account_id'][0]
        );
    }

    public function test_advance_bank_with_company_account_passes(): void
    {
        $bank = $this->makeBankAccount($this->context['company']->id);

        [$errors] = $this->validate($this->validPayload([
            'less_advance_received' => 500,
            'advance_payment_mode' => 'Bank',
            'advance_bank_account_id' => $bank->id,
        ]));

        $this->assertSame([], $errors);
    }

    public function test_zero_advance_clears_mode_and_bank_account(): void
    {
        $bank = $this->makeBankAccount($this->context['company']->id);

        [$errors, $request] = $this->validate($this->validPayload([
            'less_advance_received' => 0,
            'advance_payment_mode' => 'Bank',
            'advance_bank_account_id' => $bank->id,
        ]));

        $this->assertSame([], $errors);
        $this->assertNull($request->input('advance_payment_mode'));
        $this->assertNull($request->input('advance_bank_account_id'));
    }

    public function test_prepare_for_validation_injects_tenant_ids_ignoring_client_values(): void
    {
        [, $request] = $this->validate($this->validPayload([
            'company_id' => 9999,
            'branch_id' => 9999,
        ]));

        $this->assertSame($this->context['company']->id, $request->input('company_id'));
        $this->assertSame($this->context['branch']->id, $request->input('branch_id'));
    }

    public function test_prepare_for_validation_filters_blank_addon_charges(): void
    {
        [$errors, $request] = $this->validate($this->validPayload([
            'addon_charges' => [
                ['particulars' => '', 'amount' => ''],
                ['description' => 'Loading', 'amount' => '25'],
                ['particulars' => 'Unloading', 'amount' => 10],
            ],
        ]));

        $this->assertSame([], $errors);
        $this->assertSame([
            ['particulars' => 'Loading', 'amount' => '25'],
            ['particulars' => 'Unloading', 'amount' => 10],
        ], $request->input('addon_charges'));
    }

    public function test_prepare_for_validation_nulls_empty_addon_and_eway_collections(): void
    {
        [, $request] = $this->validate($this->validPayload([
            'addon_charges' => [['particulars' => '', 'amount' => null]],
            'eway_bills' => [['eway_bill_no' => '', 'eway_bill_valid_till' => '']],
        ]));

        $this->assertNull($request->input('addon_charges'));
        $this->assertNull($request->input('eway_bills'));
    }

    public function test_addon_amount_requires_particulars(): void
    {
        [$errors] = $this->validate($this->validPayload([
            'addon_charges' => [['amount' => 40]],
        ]));

        // The description fallback is null, so the row is kept with a null particulars value.
        $this->assertArrayHasKey('addon_charges.0.particulars', $errors);
    }

    public function test_eway_bill_requires_number_and_validity_together(): void
    {
        [$errors] = $this->validate($this->validPayload([
            'eway_bills' => [['eway_bill_no' => 'EWB123456789']],
        ]));

        $this->assertSame(
            'Please select E-Way Bill valid till date.',
            $errors['eway_bills.0.eway_bill_valid_till'][0]
        );

        [$errors] = $this->validate($this->validPayload([
            'eway_bills' => [['eway_bill_valid_till' => now()->addDay()->toDateString()]],
        ]));

        $this->assertSame('Please enter E-Way Bill number.', $errors['eway_bills.0.eway_bill_no'][0]);
    }

    public function test_valid_eway_bill_passes(): void
    {
        [$errors, $request] = $this->validate($this->validPayload([
            'eway_bills' => [[
                'eway_bill_no' => 'EWB123456789',
                'eway_bill_valid_till' => now()->addDays(3)->toDateString(),
            ]],
        ]));

        $this->assertSame([], $errors);
        $this->assertSame('EWB123456789', $request->input('eway_bills.0.eway_bill_no'));
        $this->assertNull($request->input('eway_bills.0.id'));
    }

    public function test_bilty_number_must_be_unique_within_branch(): void
    {
        $this->makeBilty('LR-100');

        [$errors] = $this->validate($this->validPayload(['bilty_number' => 'LR-100']));

        $this->assertSame('This bilty number already exists.', $errors['bilty_number'][0]);
    }

    public function test_bilty_number_may_repeat_in_another_branch(): void
    {
        $other = $this->createTenantContext([], 'B');
        Bilty::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'bilty_number' => 'LR-200',
            'date' => now()->toDateString(),
            'from_station_id' => $other['station']->id,
            'to_station_id' => $other['station']->id,
            'consignor_id' => $this->context['consignor']->id,
            'consignee_id' => $this->context['consignee']->id,
        ]);

        [$errors] = $this->validate($this->validPayload(['bilty_number' => 'LR-200']));

        $this->assertArrayNotHasKey('bilty_number', $errors);
    }

    public function test_update_ignores_own_bilty_number(): void
    {
        $bilty = $this->makeBilty('LR-300');

        [$errors] = $this->validate($this->validPayload(['bilty_number' => 'LR-300']), $bilty);

        $this->assertArrayNotHasKey('bilty_number', $errors);
    }

    public function test_update_rejects_eway_bill_id_from_another_bilty(): void
    {
        $bilty = $this->makeBilty('LR-400');
        $otherBilty = $this->makeBilty('LR-401');
        $foreignEwayId = DB::table('bilty_eway_bills')->insertGetId([
            'company_id' => $this->context['company']->id,
            'branch_id' => $this->context['branch']->id,
            'bilty_id' => $otherBilty->id,
            'eway_bill_no' => 'EWB000000001',
            'eway_bill_valid_till' => now()->addDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$errors] = $this->validate($this->validPayload([
            'eway_bills' => [[
                'id' => $foreignEwayId,
                'eway_bill_no' => 'EWB000000002',
                'eway_bill_valid_till' => now()->addDay()->toDateString(),
            ]],
        ]), $bilty);

        $this->assertSame('One or more E-Way Bill rows are invalid.', $errors['eway_bills.0.id'][0]);
    }

    public function test_messages_cover_key_rules(): void
    {
        $messages = (new BiltyRequest)->messages();

        $this->assertSame('Please select consignor.', $messages['consignor_id.required']);
        $this->assertSame('Freight cannot be negative.', $messages['freight.min']);
        $this->assertArrayHasKey('details.*.rate.required', $messages);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: array<string, list<string>>, 1: BiltyRequest}
     */
    private function validate(array $payload, ?Bilty $routeBilty = null): array
    {
        $request = BiltyRequest::create('/bilties', $routeBilty ? 'PUT' : 'POST', $payload);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        if ($routeBilty) {
            $request->setRouteResolver(fn () => new class($routeBilty)
            {
                public function __construct(private readonly Bilty $bilty) {}

                public function parameter(string $name, mixed $default = null): mixed
                {
                    return $name === 'bilty' ? $this->bilty : $default;
                }
            });
        }

        try {
            $request->validateResolved();
        } catch (ValidationException $exception) {
            return [$exception->errors(), $request];
        }

        return [[], $request];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'consignor_id' => $this->context['consignor']->id,
            'consignee_id' => $this->context['consignee']->id,
            'from_station_id' => $this->context['station']->id,
            'to_station_id' => $this->context['to_station']->id,
            'date' => now()->toDateString(),
            'payment_type' => 'To Pay',
            'details' => [[
                'good_id' => $this->context['good']->id,
                'package_type_id' => $this->context['package']->id,
                'no_of_packages' => 5,
                'gross_weight' => 100,
                'charged_weight' => 100,
                'rate_type' => 'Fixed',
                'rate' => 250,
            ]],
        ], $overrides);
    }

    private function makeParty(string $name): Party
    {
        return Party::create([
            'company_id' => $this->context['company']->id,
            'name' => $name,
            'state_id' => $this->context['state']->id,
            'city_id' => $this->context['city']->id,
        ]);
    }

    private function makeBankAccount(int $companyId): BankAccount
    {
        return BankAccount::create([
            'company_id' => $companyId,
            'name' => 'Main Bank',
            'bank_name' => 'SBI',
        ]);
    }

    private function makeAccount(int $companyId): Account
    {
        $group = AccountGroup::create([
            'company_id' => $companyId,
            'name' => 'Sundry Debtors',
            'nature' => 'assets',
        ]);

        return Account::create([
            'company_id' => $companyId,
            'account_group_id' => $group->id,
            'name' => 'Cartage Payable',
        ]);
    }

    private function makeBilty(string $number): Bilty
    {
        return Bilty::create([
            'company_id' => $this->context['company']->id,
            'branch_id' => $this->context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $this->context['station']->id,
            'to_station_id' => $this->context['to_station']->id,
            'consignor_id' => $this->context['consignor']->id,
            'consignee_id' => $this->context['consignee']->id,
        ]);
    }
}

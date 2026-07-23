<?php

namespace Tests\Unit;

use App\Models\Bilty;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Support\BiltyPrint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiltyPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_show_amount_respects_basis_of_booking_toggle(): void
    {
        $bilty = new Bilty(['payment_type' => 'To Pay']);

        $this->assertTrue(BiltyPrint::shouldShowAmount($bilty, [
            'to_pay' => true,
            'paid' => false,
            'tbb' => false,
            'foc' => false,
        ]));

        $this->assertFalse(BiltyPrint::shouldShowAmount($bilty, [
            'to_pay' => false,
            'paid' => true,
            'tbb' => false,
            'foc' => false,
        ]));
    }

    public function test_tbb_amount_can_be_shown_when_enabled_in_settings(): void
    {
        $bilty = new Bilty(['payment_type' => 'TBB']);

        $this->assertTrue(BiltyPrint::shouldShowAmount($bilty, [
            'to_pay' => false,
            'paid' => false,
            'tbb' => true,
            'foc' => false,
        ]));
    }

    public function test_unknown_payment_type_hides_amount(): void
    {
        $bilty = new Bilty(['payment_type' => 'Unknown']);

        $this->assertFalse(BiltyPrint::shouldShowAmount($bilty, BranchSetting::defaultShowAmountByBob(true)));
    }

    public function test_legacy_show_amount_migrates_to_bob_toggles(): void
    {
        $setting = new BranchSetting([
            'bilty_print_config' => [
                'copies' => [
                    'consignor' => [
                        'enabled' => true,
                        'show_amount' => true,
                    ],
                ],
            ],
        ]);

        $resolved = $setting->resolvedBiltyPrintConfig();

        $this->assertTrue($resolved['copies']['consignor']['show_amount_by_bob']['to_pay']);
        $this->assertTrue($resolved['copies']['consignor']['show_amount_by_bob']['paid']);
        $this->assertFalse($resolved['copies']['consignor']['show_amount_by_bob']['tbb']);
        $this->assertTrue($resolved['copies']['consignor']['show_amount_by_bob']['foc']);
    }

    public function test_legacy_show_amount_false_migrates_to_all_bob_disabled(): void
    {
        $setting = new BranchSetting([
            'bilty_print_config' => [
                'copies' => [
                    'consignee' => [
                        'enabled' => true,
                        'show_amount' => false,
                    ],
                ],
            ],
        ]);

        $resolved = $setting->resolvedBiltyPrintConfig();

        foreach (BranchSetting::BOB_KEYS as $bob) {
            $this->assertFalse($resolved['copies']['consignee']['show_amount_by_bob'][$bob]);
        }
    }

    public function test_payment_stamp_label_matches_sample_wording(): void
    {
        $this->assertSame('To BE BILLED', BiltyPrint::paymentStampLabel('TBB'));
        $this->assertSame('TO PAY', BiltyPrint::paymentStampLabel('To Pay'));
        $this->assertSame('PAID', BiltyPrint::paymentStampLabel('Paid'));
        $this->assertSame('FOC', BiltyPrint::paymentStampLabel('FOC'));
    }

    public function test_copies_to_print_uses_branch_settings_and_payment_type(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Test Co', 'user_id' => $user->id]);
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Maharashtra', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $station = Station::create(['name' => 'Mumbai', 'owner_id' => $user->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Test',
            'contact_number' => '9876543210',
            'email' => 'branch@example.com',
        ]);

        $branch->settings()->update([
            'bilty_print_config' => [
                'note' => '',
                'copies' => [
                    'consignor' => [
                        'enabled' => true,
                        'show_amount_by_bob' => [
                            'to_pay' => true,
                            'paid' => false,
                            'tbb' => false,
                            'foc' => false,
                        ],
                    ],
                    'consignee' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                    'driver' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                    'office' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                ],
            ],
        ]);

        $bilty = new Bilty(['payment_type' => 'To Pay']);
        $copies = BiltyPrint::copiesToPrint($branch->fresh('settings'), $bilty);

        $this->assertCount(1, $copies);
        $this->assertSame('consignor', $copies[0]['key']);
        $this->assertTrue($copies[0]['show_amount']);
    }
}

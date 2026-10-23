<?php

namespace Tests\Unit\Support;

use App\Models\Bilty;
use App\Models\Branch;
use App\Support\BiltyPrint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BiltyPrintHelpersTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_payment_type_helpers_cover_every_basis_of_booking(): void
    {
        $this->assertSame('paid', BiltyPrint::paymentTypeToBobKey(' paid '));
        $this->assertSame('foc', BiltyPrint::paymentTypeToBobKey('FOC'));
        $this->assertSame('tbb', BiltyPrint::paymentTypeToBobKey('tbb'));
        $this->assertNull(BiltyPrint::paymentTypeToBobKey(null));

        $this->assertSame('To Pay', BiltyPrint::basisOfBookingLabel('to pay'));
        $this->assertSame('Paid', BiltyPrint::basisOfBookingLabel('PAID'));
        $this->assertSame('Tbb', BiltyPrint::basisOfBookingLabel('TBB'));
        $this->assertSame('FOC', BiltyPrint::basisOfBookingLabel('foc'));
        $this->assertSame('Custom', BiltyPrint::basisOfBookingLabel('Custom'));
        $this->assertSame('—', BiltyPrint::basisOfBookingLabel(null));

        $this->assertSame('CUSTOM', BiltyPrint::paymentStampLabel('custom'));
        $this->assertSame('—', BiltyPrint::paymentStampLabel(''));

        $this->assertTrue(BiltyPrint::isPaymentType(' paid ', 'PAID'));
        $this->assertFalse(BiltyPrint::isPaymentType('TBB', 'PAID'));
        $this->assertTrue(BiltyPrint::isLiability('Consignor', 'consignor'));
        $this->assertFalse(BiltyPrint::isLiability(null, 'consignor'));
    }

    public function test_copies_fall_back_to_all_defaults_when_every_copy_is_disabled(): void
    {
        $branch = $this->createTenantContext()['branch'];
        $disabled = [];

        foreach (BiltyPrint::COPY_KEYS as $key) {
            $disabled[$key] = ['enabled' => false];
        }

        $branch->settings()->update(['bilty_print_config' => ['note' => '', 'copies' => $disabled]]);

        $copies = BiltyPrint::copiesToPrint(
            Branch::query()->find($branch->id),
            new Bilty(['payment_type' => 'To Pay'])
        );

        $this->assertSame(BiltyPrint::COPY_KEYS, array_column($copies, 'key'));
        $this->assertContains(true, array_column($copies, 'show_amount'));
    }
}

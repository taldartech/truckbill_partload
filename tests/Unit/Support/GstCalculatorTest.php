<?php

namespace Tests\Unit\Support;

use App\Support\GstCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Independent expected values are hand-calculated (literal floats).
 * Do not derive expectations by calling GstCalculator / Money helpers.
 */
class GstCalculatorTest extends TestCase
{
    public function test_amount_rounds_to_paise(): void
    {
        // 1000 * 18% = 180.00
        $this->assertSame(180.0, GstCalculator::amount(1000.0, 18.0));
        // 1234.56 * 12% = 148.1472 → 148.15
        $this->assertSame(148.15, GstCalculator::amount(1234.56, 12.0));
    }

    public function test_amount_is_zero_for_non_positive_inputs(): void
    {
        $this->assertSame(0.0, GstCalculator::amount(0.0, 18.0));
        $this->assertSame(0.0, GstCalculator::amount(100.0, 0.0));
        $this->assertSame(0.0, GstCalculator::amount(-50.0, 18.0));
    }

    public function test_inter_state_detection(): void
    {
        $this->assertTrue(GstCalculator::isInterState(1, 2));
        $this->assertFalse(GstCalculator::isInterState(1, 1));
        $this->assertFalse(GstCalculator::isInterState(null, 2));
        $this->assertFalse(GstCalculator::isInterState(1, null));
    }

    public function test_intra_state_split_sums_to_gst_with_odd_paise_on_sgst(): void
    {
        // 18.01 → 1801 paise → CGST 900, SGST 901
        $split = GstCalculator::split(18.01, 10, 10);

        $this->assertEquals(9.0, $split['cgst']);
        $this->assertEquals(9.01, $split['sgst']);
        $this->assertEquals(0.0, $split['igst']);
        $this->assertEquals(18.01, round($split['cgst'] + $split['sgst'] + $split['igst'], 2));
    }

    public function test_inter_state_split_uses_igst_only(): void
    {
        $split = GstCalculator::split(180.0, 10, 20);

        $this->assertEquals(0.0, $split['cgst']);
        $this->assertEquals(0.0, $split['sgst']);
        $this->assertEquals(180.0, $split['igst']);
    }

    #[DataProvider('biltyCases')]
    public function test_for_bilty_matches_independent_expectations(
        array $bilty,
        float $exemption,
        ?int $supplierState,
        ?int $recipientState,
        array $expected,
    ): void {
        $result = GstCalculator::forBilty($bilty, $exemption, $supplierState, $recipientState);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $result[$key], "Mismatch on {$key}");
        }
    }

    /**
     * @return array<string, array{0: array, 1: float, 2: ?int, 3: ?int, 4: array}>
     */
    public static function biltyCases(): array
    {
        return [
            'transporter_liable_intra_state' => [
                [
                    'freight' => 1000,
                    'bilty_charge' => 50,
                    'surcharge' => 0,
                    'handling_charge' => 0,
                    'risk_dd_charge' => 0,
                    'cartage' => 0,
                    'gst' => 18,
                    'gst_liability_by' => 'Transporter',
                    'less_advance_received' => 100,
                    'addon_charges' => [['amount' => 50]],
                ],
                0.0,
                1,
                1,
                // charges = 1000+50+50 = 1100; GST 18% = 198; CGST/SGST 99/99;
                // amount = 1100 + 198 - 100 = 1198
                [
                    'charges_total' => 1100.0,
                    'gst_rate' => 18.0,
                    'gst_amount' => 198.0,
                    'cgst_amount' => 99.0,
                    'sgst_amount' => 99.0,
                    'igst_amount' => 0.0,
                    'gst_in_total' => 198.0,
                    'amount' => 1198.0,
                ],
            ],
            'exempted_when_below_limit' => [
                [
                    'freight' => 500,
                    'bilty_charge' => 0,
                    'surcharge' => 0,
                    'handling_charge' => 0,
                    'risk_dd_charge' => 0,
                    'cartage' => 0,
                    'gst' => 18,
                    'gst_liability_by' => 'Transporter',
                    'less_advance_received' => 0,
                ],
                1000.0,
                1,
                2,
                [
                    'charges_total' => 500.0,
                    'gst_rate' => 0.0,
                    'gst_amount' => 0.0,
                    'cgst_amount' => 0.0,
                    'sgst_amount' => 0.0,
                    'igst_amount' => 0.0,
                    'gst_in_total' => 0.0,
                    'amount' => 500.0,
                ],
            ],
            'consignor_liable_does_not_add_gst_to_total' => [
                [
                    'freight' => 2000,
                    'bilty_charge' => 0,
                    'surcharge' => 0,
                    'handling_charge' => 0,
                    'risk_dd_charge' => 0,
                    'cartage' => 0,
                    'gst' => 18,
                    'gst_liability_by' => 'Consignor',
                    'less_advance_received' => 0,
                ],
                0.0,
                5,
                9,
                // IGST 360 computed but not in payable total
                [
                    'charges_total' => 2000.0,
                    'gst_rate' => 18.0,
                    'gst_amount' => 360.0,
                    'cgst_amount' => 0.0,
                    'sgst_amount' => 0.0,
                    'igst_amount' => 360.0,
                    'gst_in_total' => 0.0,
                    'amount' => 2000.0,
                ],
            ],
        ];
    }

    public function test_delivery_receipt_gst_on_delivery_charge_excludes_freight(): void
    {
        // Independent: freight 400 + delivery 100 + other 50 = 550 subtotal;
        // gst_on delivery_charge → base = 550 - 400 = 150; 18% of 150 = 27;
        // Transporter → final = 550 + 27 - less 10 = 567
        $result = GstCalculator::forDeliveryReceipt([
            'freight' => 400,
            'handling_charge' => 0,
            'delivery_charge' => 100,
            'stationery_charge' => 0,
            'demurrage' => 0,
            'other_charge' => 50,
            'carriage_charge' => 0,
            'gst_on' => 'delivery_charge',
            'gst_pay_by' => 'Transporter',
            'less' => 10,
        ], 18.0, 0.0);

        $this->assertSame(550.0, $result['total_amount']);
        $this->assertSame(150.0, $result['gst_base']);
        $this->assertSame(18.0, $result['gst_rate']);
        $this->assertSame(27.0, $result['gst']);
        $this->assertSame(27.0, $result['gst_in_total']);
        $this->assertSame(567.0, $result['final_amount']);
    }
}

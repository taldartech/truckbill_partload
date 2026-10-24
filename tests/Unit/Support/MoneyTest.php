<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_round_and_format_use_two_decimal_places(): void
    {
        $this->assertSame(12.35, Money::round(12.345));
        $this->assertSame(12.34, Money::round(12.344));
        $this->assertSame('12.35', Money::format(12.345));
    }

    public function test_display_includes_rupee_grouping(): void
    {
        $this->assertSame('₹25,164.24', Money::display(25164.24));
    }

    public function test_paise_conversion_is_symmetric_for_common_amounts(): void
    {
        // Hand expectation: 99.99 rupees = 9999 paise
        $this->assertSame(9999, Money::toPaise(99.99));
        $this->assertSame(99.99, Money::fromPaise(9999));
        $this->assertSame(0, Money::toPaise(0));
    }
}

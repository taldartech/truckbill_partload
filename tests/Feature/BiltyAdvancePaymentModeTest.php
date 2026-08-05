<?php

namespace Tests\Feature;

use App\Http\Requests\BiltyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BiltyAdvancePaymentModeTest extends TestCase
{
    public function test_advance_payment_mode_is_required_when_advance_is_received(): void
    {
        $errors = $this->validateAdvancePaymentMode([
            'less_advance_received' => 500,
            'advance_payment_mode' => '',
        ]);

        $this->assertArrayHasKey('advance_payment_mode', $errors);
    }

    public function test_advance_payment_mode_cash_is_allowed(): void
    {
        $errors = $this->validateAdvancePaymentMode([
            'less_advance_received' => 500,
            'advance_payment_mode' => 'Cash',
        ]);

        $this->assertArrayNotHasKey('advance_payment_mode', $errors);
    }

    public function test_advance_payment_mode_bank_is_allowed(): void
    {
        $errors = $this->validateAdvancePaymentMode([
            'less_advance_received' => 500,
            'advance_payment_mode' => 'Bank',
        ]);

        $this->assertArrayNotHasKey('advance_payment_mode', $errors);
    }

    public function test_advance_payment_mode_is_optional_when_advance_is_zero(): void
    {
        $errors = $this->validateAdvancePaymentMode([
            'less_advance_received' => 0,
            'advance_payment_mode' => '',
        ]);

        $this->assertArrayNotHasKey('advance_payment_mode', $errors);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, list<string>>
     */
    private function validateAdvancePaymentMode(array $payload): array
    {
        $request = BiltyRequest::create('/bilties', 'POST', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, [
            'less_advance_received' => 'nullable|numeric|min:0',
            'advance_payment_mode' => 'nullable|string|in:Cash,Bank,cash,bank',
            'details' => 'required|array|min:1',
        ]);
        $request->withValidator($validator);
        $validator->fails();

        return $validator->errors()->toArray();
    }
}

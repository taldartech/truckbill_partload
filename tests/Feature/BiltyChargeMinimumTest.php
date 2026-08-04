<?php

namespace Tests\Feature;

use App\Http\Requests\BiltyRequest;
use App\Models\Company;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BiltyChargeMinimumTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilty_charge_below_party_charge_is_allowed(): void
    {
        $payload = $this->payload(partyBiltyCharge: 50, biltyCharge: 25);

        $errors = $this->validateBiltyCharge($payload);

        $this->assertArrayNotHasKey('bilty_charge', $errors);
    }

    public function test_bilty_charge_equal_to_party_charge_is_allowed(): void
    {
        $payload = $this->payload(partyBiltyCharge: 50, biltyCharge: 50);

        $errors = $this->validateBiltyCharge($payload);

        $this->assertArrayNotHasKey('bilty_charge', $errors);
    }

    public function test_bilty_charge_above_party_charge_is_allowed(): void
    {
        $payload = $this->payload(partyBiltyCharge: 50, biltyCharge: 75);

        $errors = $this->validateBiltyCharge($payload);

        $this->assertArrayNotHasKey('bilty_charge', $errors);
    }

    public function test_bilty_charge_zero_is_allowed_when_party_charge_is_set(): void
    {
        $payload = $this->payload(partyBiltyCharge: 50, biltyCharge: 0);

        $errors = $this->validateBiltyCharge($payload);

        $this->assertArrayNotHasKey('bilty_charge', $errors);
    }

    /**
     * @return array<string, list<string>>
     */
    private function validateBiltyCharge(array $payload): array
    {
        $request = BiltyRequest::create('/bilties', 'POST', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, [
            'consignor_id' => 'required',
            'bilty_charge' => 'nullable|numeric|min:0',
            'details' => 'required|array|min:1',
        ]);
        $request->withValidator($validator);
        $validator->fails();

        return $validator->errors()->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?float $partyBiltyCharge, float $biltyCharge): array
    {
        $company = Company::create(['name' => 'Test Co']);

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Consignor',
            'bilty_charge' => $partyBiltyCharge,
        ]);

        return [
            'consignor_id' => $consignor->id,
            'bilty_charge' => $biltyCharge,
            'details' => [
                ['good_id' => 1],
            ],
        ];
    }
}

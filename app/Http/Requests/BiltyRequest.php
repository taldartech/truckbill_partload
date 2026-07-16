<?php

namespace App\Http\Requests;

use App\Models\Party;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BiltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $addonCharges = collect($this->input('addon_charges', []))
            ->filter(function ($charge) {
                return ! empty($charge['particulars']) || ! empty($charge['description']) || (isset($charge['amount']) && $charge['amount'] !== '' && $charge['amount'] !== null);
            })
            ->map(function ($charge) {
                return [
                    'particulars' => $charge['particulars'] ?? $charge['description'] ?? null,
                    'amount' => $charge['amount'] ?? null,
                ];
            })
            ->values()
            ->all();

        $ewayBills = collect($this->input('eway_bills', []))
            ->filter(function ($row) {
                return ! empty($row['eway_bill_no']) || ! empty($row['eway_bill_valid_till']);
            })
            ->map(function ($row) {
                return [
                    'id' => ! empty($row['id']) ? (int) $row['id'] : null,
                    'eway_bill_no' => $row['eway_bill_no'] ?? null,
                    'eway_bill_valid_till' => $row['eway_bill_valid_till'] ?? null,
                ];
            })
            ->values()
            ->all();

        $cartageType = $this->input('cartage_type');
        if (in_array($cartageType, ['cash', 'due'], true)) {
            $cartageType = $cartageType === 'cash' ? 'Paid' : 'Due';
        }

        $cartage = (float) ($this->input('cartage') ?? 0);
        $vendorId = $this->input('vendor_id');

        if ($cartage <= 0) {
            $cartageType = null;
            $vendorId = null;
        } elseif ($cartageType === 'Paid') {
            $vendorId = null;
        }

        $context = app(TenantContext::class);

        $this->merge([
            'company_id' => $context->requireCompanyId(),
            'branch_id' => $context->requireBranchId(),
            'cartage_type' => $cartageType,
            'vendor_id' => $vendorId,
            'addon_charges' => empty($addonCharges) ? null : $addonCharges,
            'eway_bills' => empty($ewayBills) ? null : $ewayBills,
        ]);
    }

    public function rules(): array
    {
        $bilty = $this->route('bilty');
        $biltyId = $bilty?->id ?? $bilty;
        $effectiveBranchId = $this->input('branch_id');

        $ewayBillIdRule = $biltyId
            ? [
                'eway_bills.*.id' => [
                    'nullable',
                    'integer',
                    Rule::exists('bilty_eway_bills', 'id')->where(
                        fn ($query) => $query->where('bilty_id', $biltyId)
                    ),
                ],
            ]
            : [];

        return [
            'consignor_id' => 'required|exists:parties,id',
            'consignee_id' => 'required|exists:parties,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'bilty_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('bilties', 'bilty_number')
                    ->where(fn ($query) => $query->where('branch_id', $effectiveBranchId))
                    ->ignore($biltyId),
            ],
            'date' => 'required|date',
            'freight' => 'nullable|numeric|min:0',
            'bilty_charge' => 'nullable|numeric|min:0',
            'surcharge' => 'nullable|numeric|min:0',
            'other_charge' => 'nullable|numeric|min:0',
            'handling_charge' => 'nullable|numeric|min:0',
            'risk_dd_charge' => 'nullable|numeric|min:0',
            'cartage' => 'nullable|numeric|min:0',
            'cartage_type' => 'nullable|string|in:Paid,Due,cash,due',
            'vendor_id' => 'nullable|exists:vendors,id',
            'addon_charges' => 'nullable|array',
            'addon_charges.*.particulars' => 'nullable|string|max:255|required_with:addon_charges.*.amount',
            'addon_charges.*.amount' => 'nullable|numeric|min:0',
            'cartage_due_with_whom' => 'nullable|string|max:100',
            'less_advance_received' => 'nullable|numeric|min:0',
            'previous_freight' => 'nullable|numeric|min:0',
            'previous_freight_no' => 'nullable|string|max:100',
            'gst' => 'nullable|numeric|min:0|max:100',
            'cgst' => 'nullable|numeric|min:0|max:100',
            'sgst' => 'nullable|numeric|min:0|max:100',
            'igst' => 'nullable|numeric|min:0|max:100',
            'gst_liability_by' => 'nullable|string|in:Consignor,Consignee,Transporter,Exempted',
            'weight_actual' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:1000',
            'private_marka' => 'nullable|string|max:255',
            'delivery_at' => 'nullable|string|max:255',
            'eway_bills' => 'nullable|array',
            ...$ewayBillIdRule,
            'eway_bills.*.eway_bill_no' => 'nullable|string|max:50|required_with:eway_bills.*.eway_bill_valid_till',
            'eway_bills.*.eway_bill_valid_till' => 'nullable|date|required_with:eway_bills.*.eway_bill_no',
            'declared_value' => 'nullable|numeric|min:0',
            'invoice_number' => 'nullable|string|max:50',
            'payment_type' => 'nullable|string|in:To Pay,Paid,TBB,FOC',
            'advance_payment_received' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|string|max:50',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:Pending,In Transit,Delivered,Cancelled',
            'details' => 'required|array|min:1',
            'details.*.good_id' => 'required|exists:goods,id',
            'details.*.package_type_id' => 'required|exists:package_types,id',
            'details.*.no_of_packages' => 'required|numeric|min:0',
            'details.*.gross_weight' => 'required|numeric|min:0',
            'details.*.charged_weight' => 'required|numeric|min:0',
            'details.*.rate_type' => [
                Rule::requiredIf(fn () => $this->input('payment_type') !== 'TBB'),
                'nullable',
                'string',
                'in:Fixed,Per Package,Per KG,Per Actual Weight,Per Charged Weight',
            ],
            'details.*.rate' => [
                Rule::requiredIf(fn () => $this->input('payment_type') !== 'TBB'),
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $details = $this->input('details', []);
            $validRows = collect($details)->filter(function ($detail) {
                return ! empty($detail['good_id']);
            });

            if ($validRows->isEmpty()) {
                $validator->errors()->add('details', 'At least one bilty detail row with goods is required.');
            }

            $cartage = (float) ($this->input('cartage') ?? 0);
            $cartageType = $this->input('cartage_type');

            if ($cartage > 0 && empty($cartageType)) {
                $validator->errors()->add('cartage_type', 'Please select whether cartage is Paid or Due.');
            }

            if ($cartage > 0 && $cartageType === 'Due' && empty($this->input('vendor_id'))) {
                $validator->errors()->add('vendor_id', 'Please select vendor for due cartage.');
            }

            $consignorId = $this->input('consignor_id');
            if ($consignorId) {
                $partyMinCharge = Party::query()->whereKey($consignorId)->value('bilty_charge');

                if ($partyMinCharge !== null && $partyMinCharge !== '') {
                    $minCharge = round((float) $partyMinCharge, 2);
                    $biltyCharge = round((float) ($this->input('bilty_charge') ?? 0), 2);

                    if ($biltyCharge < $minCharge) {
                        $validator->errors()->add(
                            'bilty_charge',
                            "Bilty charge cannot be less than the consignor's defined charge ({$minCharge})."
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'consignor_id.required' => 'Please select consignor.',
            'consignee_id.required' => 'Please select consignee.',
            'from_station_id.required' => 'Please select from station.',
            'to_station_id.required' => 'Please select to station.',
            'bilty_number.unique' => 'This bilty number already exists.',
            'date.required' => 'Please select bilty date.',
            'payment_type.in' => 'Payment type must be To Pay, Paid, TBB or FOC.',
            'status.in' => 'Status must be Pending, In Transit, Delivered or Cancelled.',
            'gst.min' => 'GST cannot be negative.',
            'gst.max' => 'GST cannot be more than 100%.',
            'cgst.max' => 'CGST cannot be more than 100%.',
            'sgst.max' => 'SGST cannot be more than 100%.',
            'igst.max' => 'IGST cannot be more than 100%.',
            'eway_bills.*.id.exists' => 'One or more E-Way Bill rows are invalid.',
            'eway_bills.*.eway_bill_no.required_with' => 'Please enter E-Way Bill number.',
            'eway_bills.*.eway_bill_valid_till.required_with' => 'Please select E-Way Bill valid till date.',
            'cartage_type.in' => 'Cartage type must be Paid or Due.',
            'vendor_id.exists' => 'Selected vendor is invalid.',

            'details.required' => 'Please add at least one detail row.',
            'details.array' => 'Details must be a valid row list.',
            'details.min' => 'Please add at least one detail row.',

            'details.*.good_id.required' => 'Please select goods.',
            'details.*.good_id.exists' => 'Selected goods is invalid.',
            'details.*.package_type_id.required' => 'Please select packing type.',
            'details.*.package_type_id.exists' => 'Selected packing type is invalid.',
            'details.*.no_of_packages.required' => 'Please enter number of packages.',
            'details.*.no_of_packages.numeric' => 'Number of packages must be numeric.',
            'details.*.no_of_packages.min' => 'Number of packages cannot be negative.',
            'details.*.gross_weight.required' => 'Please enter actual weight.',
            'details.*.gross_weight.numeric' => 'Actual weight must be numeric.',
            'details.*.gross_weight.min' => 'Actual weight cannot be negative.',
            'details.*.charged_weight.required' => 'Please enter charged weight.',
            'details.*.charged_weight.numeric' => 'Charged weight must be numeric.',
            'details.*.charged_weight.min' => 'Charged weight cannot be negative.',
            'details.*.rate_type.required' => 'Please select freight type.',
            'details.*.rate_type.in' => 'Freight type is invalid.',
            'details.*.rate.required' => 'Please enter freight rate.',
            'details.*.rate.numeric' => 'Freight rate must be numeric.',
            'details.*.rate.min' => 'Freight rate cannot be negative.',
        ];
    }
}

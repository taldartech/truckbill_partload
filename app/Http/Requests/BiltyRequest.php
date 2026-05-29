<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BiltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $biltyId = $this->route('bilty')?->id ?? $this->route('bilty');
        $effectiveCompanyId = $this->input('company_id') ?: auth()->user()?->company_id;

        return [
            'company_id' => 'nullable|exists:companies,id',
            'consignor_id' => 'required|exists:parties,id',
            'consignee_id' => 'required|exists:parties,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'bilty_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('bilties', 'bilty_number')
                    ->where(fn ($query) => $query->where('company_id', $effectiveCompanyId))
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
            'cartage_type' => 'nullable|string|max:50',
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
            'eway_bill_no' => 'nullable|string|max:50',
            'eway_bill_expiry_date' => 'nullable|date|required_with:eway_bill_no',
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
            'details.*.rate_type' => 'required|string|in:Fixed,Per Package,Per KG,Per Actual Weight,Per Charged Weight',
            'details.*.rate' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $details = $this->input('details', []);
            $validRows = collect($details)->filter(function ($detail) {
                return !empty($detail['good_id']);
            });

            if ($validRows->isEmpty()) {
                $validator->errors()->add('details', 'At least one bilty detail row with goods is required.');
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
            'eway_bill_expiry_date.required_with' => 'Please select E-Way Bill expiry date.',

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

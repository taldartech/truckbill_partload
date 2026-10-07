<template>
    <AdminLayout title="Edit Bilty">
        <div class="bilty-form-page">
            <h1 class="bilty-form-page__title">Edit Bilty</h1>

            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Booking -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Booking</h2>
                                <p class="bilty-section__desc">Payment type, stations and bilty identity</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Bilty Number</span>
                                    <input ref="biltyNumberInput" type="text" class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.bilty_number }" v-model="form.bilty_number">
                                    <div v-if="form.errors.bilty_number" class="text-danger small mt-1">{{
                                        form.errors.bilty_number }}</div>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date</span>
                                    <input type="date" class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.date }" v-model="form.date">
                                    <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Payment</label>
                                <select class="form-select form-select-sm" v-model="form.payment_type">
                                    <option value="To Pay">To Pay</option>
                                    <option value="Paid">Paid</option>
                                    <option value="TBB">TBB</option>
                                    <option value="FOC">FOC</option>
                                </select>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">From Station<span
                                        class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.from_station_id" :options="stationOptions"
                                    master="station" placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)" />
                                <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{
                                    form.errors.from_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">To Station<span
                                        class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.to_station_id" :options="stationOptions"
                                    master="station" placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)" />
                                <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{
                                    form.errors.to_station_id }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Parties -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Parties</h2>
                                <p class="bilty-section__desc">Consignor and consignee</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--2">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Consignor<span
                                        class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.consignor_id" :options="partyOptions"
                                    master="party" placeholder="Select Consignor"
                                    @created="(record) => appendMasterOption(partyOptions, record)" />
                                <div v-if="form.errors.consignor_id" class="text-danger small mt-1">{{
                                    form.errors.consignor_id }}</div>
                                <div v-if="form.consignor_id" class="bilty-party-details">
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">State</span>
                                        <span class="bilty-party-details__value">{{ partyState(form.consignor_id) || '—'
                                            }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">City</span>
                                        <span class="bilty-party-details__value">{{ partyCity(form.consignor_id) || '—'
                                            }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">Mobile</span>
                                        <span class="bilty-party-details__value">{{ partyMobile(form.consignor_id) ||
                                            '—' }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">GST</span>
                                        <span class="bilty-party-details__value">{{ getConsignorGST() || '—' }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Consignee<span
                                        class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.consignee_id" :options="partyOptions"
                                    master="party" placeholder="Select Consignee"
                                    @created="(record) => appendMasterOption(partyOptions, record)" />
                                <div v-if="form.errors.consignee_id" class="text-danger small mt-1">{{
                                    form.errors.consignee_id }}</div>
                                <div v-if="form.consignee_id" class="bilty-party-details">
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">State</span>
                                        <span class="bilty-party-details__value">{{ partyState(form.consignee_id) || '—'
                                            }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">City</span>
                                        <span class="bilty-party-details__value">{{ partyCity(form.consignee_id) || '—'
                                            }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">Mobile</span>
                                        <span class="bilty-party-details__value">{{ partyMobile(form.consignee_id) ||
                                            '—' }}</span>
                                    </span>
                                    <span class="bilty-party-details__item">
                                        <span class="bilty-party-details__label">GST</span>
                                        <span class="bilty-party-details__value">{{ getConsigneeGST() || '—' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. Goods -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">3</div>
                            <div>
                                <h2 class="bilty-section__title">Goods</h2>
                                <p class="bilty-section__desc">Line items, packing, weight and freight</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid" style="min-width: 1000px;"
                                data-dynamic-add-message="Do you want to add another item?">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%" class="text-center">Sr.No.</th>
                                        <th width="16%">Goods Name</th>
                                        <th width="11%">Packing</th>
                                        <th width="10%" class="text-center">No. of Pkg</th>
                                        <th width="10%" class="text-center">Actual Wt(Kg)</th>
                                        <th width="10%" class="text-center">Charged Wt(Kg)</th>
                                        <th width="15%" class="text-center">Frt.Type<span v-if="!isTbb"
                                                class="text-danger">*</span></th>
                                        <th width="10%" class="text-center">Frt.Rate<span v-if="!isTbb"
                                                class="text-danger">*</span></th>
                                        <th width="11%" class="text-center">Goods Frt</th>
                                        <th width="2%" class="text-center"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(detail, index) in form.details" :key="index" class="bg-white">
                                        <td class="text-center align-middle">{{ index + 1 }}</td>
                                        <td>
                                            <SearchableSelect :small="true" v-model="detail.good_id"
                                                :options="goodOptions" master="good" placeholder="Select Good"
                                                @created="(record) => appendMasterOption(goodOptions, record)" />
                                            <div v-if="detailError(index, 'good_id')" class="text-danger small">{{
                                                detailError(index, 'good_id') }}</div>
                                        </td>
                                        <td>
                                            <SearchableSelect :small="true" v-model="detail.package_type_id"
                                                :options="packageTypeOptions" master="package_type"
                                                placeholder="Select Packing"
                                                @created="(record) => appendMasterOption(packageTypeOptions, record)" />
                                            <div v-if="detailError(index, 'package_type_id')" class="text-danger small">
                                                {{ detailError(index, 'package_type_id') }}</div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" max="500000"
                                                class="form-control form-control-sm text-center"
                                                :class="{ 'is-invalid': detailError(index, 'no_of_packages') }"
                                                v-model="detail.no_of_packages" @input="calculateGridRow(index)">
                                            <div v-if="detailError(index, 'no_of_packages')" class="text-danger small">
                                                {{ detailError(index, 'no_of_packages') }}</div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" max="120000" step="any"
                                                class="form-control form-control-sm text-center"
                                                :class="{ 'is-invalid': detailError(index, 'gross_weight') }"
                                                v-model="detail.gross_weight" @input="calculateGridRow(index)">
                                            <div v-if="detailError(index, 'gross_weight')" class="text-danger small">{{
                                                detailError(index, 'gross_weight') }}</div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" max="120000" step="any"
                                                class="form-control form-control-sm text-center"
                                                :class="{ 'is-invalid': detailError(index, 'charged_weight') }"
                                                v-model="detail.charged_weight" @input="calculateGridRow(index)">
                                            <div v-if="detailError(index, 'charged_weight')" class="text-danger small">
                                                {{ detailError(index, 'charged_weight') }}</div>
                                        </td>
                                        <td>
                                            <SearchableSelect v-model="detail.rate_type" :options="rateTypeOptions"
                                                placeholder="Select Type" :small="true" class="rate-type-select" />
                                            <div v-if="detailError(index, 'rate_type')" class="text-danger small">{{
                                                detailError(index, 'rate_type') }}</div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="any"
                                                class="form-control form-control-sm text-center"
                                                :class="{ 'is-invalid': detailError(index, 'rate') }"
                                                v-model="detail.rate" @input="calculateGridRow(index)">
                                            <div v-if="detailError(index, 'rate')" class="text-danger small">{{
                                                detailError(index, 'rate') }}</div>
                                        </td>
                                        <td class="text-center align-middle fw-bold">
                                            {{ detail.amount || '0.00' }}
                                        </td>
                                        <td class="text-center align-middle">
                                            <button v-if="form.details.length > 1" type="button"
                                                class="btn btn-sm btn-link text-danger p-0 bg-transparent"
                                                title="Remove row" @click="removeRow(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button v-else type="button"
                                                class="btn btn-sm btn-link text-secondary p-0 bg-transparent"
                                                title="Reset row" @click="resetRow(index)">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center bg-light p-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addRow">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                            <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. Charges & Total -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">4</div>
                            <div>
                                <h2 class="bilty-section__title">Charges &amp; Total</h2>
                                <p class="bilty-section__desc">Freight, extras and payable amount</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="bilty-charge-list">
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Total Freight</label>
                                        <input type="number" step="any"
                                            class="form-control form-control-sm bg-light fw-bold" v-model="form.freight"
                                            readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Bilty Charge</label>
                                        <div>
                                            <input type="number" step="any" min="0" class="form-control form-control-sm"
                                                :class="{ 'is-invalid': form.errors.bilty_charge }"
                                                v-model="form.bilty_charge" @input="calculateFinalTotal">
                                            <div v-if="form.errors.bilty_charge" class="text-danger small">{{
                                                form.errors.bilty_charge }}</div>
                                        </div>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Surcharge</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm"
                                            v-model="form.surcharge" @input="calculateFinalTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cartage</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm"
                                            v-model="form.cartage" @input="onCartageChange">
                                    </div>
                                    <div v-if="showCartageOptions" class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cartage Type</label>
                                        <div>
                                            <select class="form-select form-select-sm" v-model="form.cartage_type"
                                                @change="onCartageTypeChange">
                                                <option value="">Select</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Due">Due</option>
                                            </select>
                                            <div v-if="form.errors.cartage_type" class="text-danger small">{{
                                                form.errors.cartage_type }}</div>
                                        </div>
                                    </div>
                                    <div v-if="showCartageOptions && form.cartage_type === 'Due'"
                                        class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Vendor</label>
                                        <div>
                                            <SearchableSelect :small="true" v-model="form.vendor_id"
                                                :options="vendorOptions" master="vendor" placeholder="Select Vendor"
                                                @created="(record) => appendMasterOption(vendorOptions, record)" />
                                            <div v-if="form.errors.vendor_id" class="text-danger small">{{
                                                form.errors.vendor_id }}</div>
                                        </div>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Handling Charge</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm"
                                            v-model="form.handling_charge" @input="calculateFinalTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Risk/Door Delivery</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm"
                                            v-model="form.risk_dd_charge" @input="calculateFinalTotal"
                                            data-dynamic-grid-trigger="addon">
                                    </div>
                                    <div class="bilty-charge-row bilty-charge-row--stack">
                                        <label class="bilty-charge-row__label">Addon Charges</label>
                                        <div class="bilty-addon-panel dynamic-grid" data-dynamic-grid="addon"
                                            data-dynamic-add-message="Do you want to add another addon charge?">
                                            <div class="bilty-addon-panel__head">
                                                <p class="bilty-addon-panel__desc mb-0">Optional — add particulars and
                                                    amount</p>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-dynamic-add @click="addAddonCharge">
                                                    <i class="fas fa-plus me-1"></i> Add
                                                </button>
                                            </div>

                                            <div v-if="form.addon_charges.length === 0" class="bilty-addon-empty">
                                                No addon charges added yet.
                                            </div>

                                            <div v-else class="bilty-addon-list">
                                                <div class="bilty-addon-row bilty-addon-row--head">
                                                    <span>Particulars</span>
                                                    <span>Amount</span>
                                                    <span class="text-center">Action</span>
                                                </div>
                                                <div v-for="(charge, index) in form.addon_charges" :key="index"
                                                    class="row g-2 align-items-center">
                                                    <div class="col-12 col-md-6">
                                                        <input type="text" class="form-control form-control-sm"
                                                            v-model="charge.particulars" placeholder="Enter particulars"
                                                            @input="calculateFinalTotal">
                                                    </div>
                                                    <div class="col-10 col-md-4">
                                                        <input type="number" min="0" step="any"
                                                            class="form-control form-control-sm" v-model="charge.amount"
                                                            placeholder="0.00" @input="calculateFinalTotal">
                                                    </div>
                                                    <div class="col-2 col-md-2 text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger bilty-addon-remove"
                                                            title="Remove addon charge"
                                                            @click="removeAddonCharge(index)">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="bilty-total-panel">
                                    <div v-if="showGstFields" class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">GST Amount ({{ Number(form.gst || 0)
                                            }}%)</label>
                                        <input type="number" step="any" class="form-control form-control-sm bg-light"
                                            :value="gstAmount" readonly>
                                    </div>
                                    <div v-if="showGstFields && showGstLiability" class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">GST Liability By</label>
                                        <select class="form-select form-select-sm" v-model="form.gst_liability_by">
                                            <option value="">Select</option>
                                            <option value="Consignor">Consignor</option>
                                            <option value="Consignee">Consignee</option>
                                            <option value="Transporter">Transporter</option>
                                            <option value="Exempted">Exempted</option>
                                        </select>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Less Advance Received</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm"
                                            v-model="form.less_advance_received" @input="onAdvanceReceivedChange">
                                    </div>
                                    <div v-if="showAdvancePaymentMode" class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Advance Payment Mode</label>
                                        <div>
                                            <select class="form-select form-select-sm"
                                                v-model="form.advance_payment_mode">
                                                <option value="">Select</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Bank">Bank</option>
                                            </select>
                                            <div v-if="form.errors.advance_payment_mode" class="text-danger small">{{
                                                form.errors.advance_payment_mode }}</div>
                                        </div>
                                    </div>
                                    <div class="bilty-total">
                                        <span class="bilty-total__label">TOTAL AMOUNT</span>
                                        <input type="number" step="any"
                                            class="form-control form-control-sm bilty-total__input"
                                            v-model="form.amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. Shipment details -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--gray">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--gray">5</div>
                            <div>
                                <h2 class="bilty-section__title">Shipment Details</h2>
                                <p class="bilty-section__desc">Marka, delivery, weight, invoice and e-way bills</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--5">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Private Marka</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.private_marka">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Delivery At</label>
                                <SearchableSelect :small="true" v-model="form.delivery_at" :options="filteredAgents"
                                    valueKey="name" labelKey="name" master="agent" placeholder="Select Delivery Agent"
                                    @created="(record) => appendMasterOption(agentOptions, record, 'name')" />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Weight Actual</label>
                                <input type="number" step="any" class="form-control form-control-sm bg-light"
                                    v-model="form.weight_actual" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Declared Value <span
                                        class="text-muted fw-normal">(Optional)</span></label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm"
                                    v-model="form.declared_value">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Invoice No. <span
                                        class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" class="form-control form-control-sm" v-model="form.invoice_number"
                                    data-dynamic-grid-trigger="eway">
                            </div>
                        </div>

                        <div class="bilty-eway-block dynamic-grid" data-dynamic-grid="eway"
                            data-dynamic-add-message="Do you want to add another e-way bill?">
                            <div class="bilty-eway-block__head">
                                <div>
                                    <h3 class="bilty-eway-block__title">E-Way Bills</h3>
                                    <p class="bilty-eway-block__desc">Optional — add bill number and valid till date</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-dynamic-add
                                    @click="addEwayBill">
                                    <i class="fas fa-plus me-1"></i> Add
                                </button>
                            </div>

                            <div v-if="form.eway_bills.length === 0" class="bilty-eway-empty">
                                No e-way bills added yet.
                            </div>

                            <div v-else class="bilty-eway-list">
                                <div class="bilty-eway-row bilty-eway-row--head">
                                    <span>E-Way Bill No.</span>
                                    <span>Valid Till</span>
                                    <span class="text-center">Action</span>
                                </div>
                                <div v-for="(eway, index) in form.eway_bills" :key="eway.id ?? `new-${index}`"
                                    class="bilty-eway-row dynamic-row">
                                    <div class="bilty-eway-row__field">
                                        <input type="text" class="form-control form-control-sm"
                                            :class="{ 'is-invalid': ewayError(index, 'eway_bill_no') }"
                                            v-model="eway.eway_bill_no" placeholder="Enter e-way bill number">
                                        <div v-if="ewayError(index, 'eway_bill_no')" class="text-danger small mt-1">{{
                                            ewayError(index, 'eway_bill_no') }}</div>
                                    </div>
                                    <div class="bilty-eway-row__field">
                                        <input type="date" class="form-control form-control-sm"
                                            :class="{ 'is-invalid': ewayError(index, 'eway_bill_valid_till') }"
                                            v-model="eway.eway_bill_valid_till">
                                        <div v-if="ewayError(index, 'eway_bill_valid_till')"
                                            class="text-danger small mt-1">{{ ewayError(index, 'eway_bill_valid_till')
                                            }}</div>
                                    </div>
                                    <div class="bilty-eway-row__action">
                                        <button type="button" class="btn btn-sm btn-outline-danger bilty-eway-remove"
                                            title="Remove e-way bill" @click="removeEwayBill(index)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/bilties" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" :disabled="form.processing"
                        @click="clearForm">
                        <i class="fas fa-eraser me-1"></i> Clear
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>

        <BiltyConfirmModal :show="showConfirm" :form="form" :parties="partyOptions" :stations="stationOptions"
            :goods="goodOptions" :package-types="packageTypeOptions" :vendors="vendorOptions"
            :gst-exemption-limit="gst_exemption_limit" @cancel="closeConfirm" @confirm="confirmSave"
            @confirm-print="confirmSaveAndPrint" />
    </AdminLayout>
</template>


<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BiltyConfirmModal from '@/Components/FormConfirm/BiltyConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { watch, ref, onMounted, computed, nextTick } from 'vue';

const gstAmount = ref('0.00');
const biltyNumberInput = ref(null);
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const props = defineProps({
    bilty: Object,
    parties: Array,
    vendors: Array,
    stations: Array,
    goods: Array,
    package_types: Array,
    agents: Array,
    default_gst_percent: { type: Number, default: 0 },
    gst_exemption_limit: { type: Number, default: 0 },
});

const partyOptions = ref([...(props.parties || [])]);
const stationOptions = ref([...(props.stations || [])]);
const goodOptions = ref([...(props.goods || [])]);
const packageTypeOptions = ref([...(props.package_types || [])]);
const vendorOptions = ref([...(props.vendors || [])]);
const agentOptions = ref([...(props.agents || [])]);

watch(() => props.parties, (v) => { partyOptions.value = [...(v || [])]; });
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.goods, (v) => { goodOptions.value = [...(v || [])]; });
watch(() => props.package_types, (v) => { packageTypeOptions.value = [...(v || [])]; });
watch(() => props.vendors, (v) => { vendorOptions.value = [...(v || [])]; });
watch(() => props.agents, (v) => { agentOptions.value = [...(v || [])]; });

const rowAmountFromDetail = (d) => {
    const weight = parseFloat(d.gross_weight) || 0;
    const chargedWeight = parseFloat(d.charged_weight) || 0;
    const packages = parseFloat(d.no_of_packages) || 0;
    const rate = parseFloat(d.rate) || 0;
    const rateType = d.rate_type || 'Fixed';

    if (rateType === 'Per Actual Weight' || rateType === 'Per KG') {
        return (rate * weight).toFixed(2);
    }
    if (rateType === 'Per Charged Weight') {
        return (rate * chargedWeight).toFixed(2);
    }
    if (rateType === 'Per Package') {
        return (rate * packages).toFixed(2);
    }
    return rate.toFixed(2);
};

const savedDetails = props.bilty.bilty_details ?? props.bilty.biltyDetails ?? [];

const initialDetails = savedDetails.length > 0
    ? savedDetails.map((d) => ({
        good_id: d.good_id || '',
        package_type_id: d.package_type_id || '',
        no_of_packages: d.no_of_packages || '',
        gross_weight: d.gross_weight || '',
        charged_weight: d.charged_weight || '',
        rate_type: d.rate_type || 'Fixed',
        rate: d.rate || '',
        amount: rowAmountFromDetail(d),
    }))
    : [{ good_id: '', package_type_id: '', no_of_packages: '', gross_weight: '', charged_weight: '', rate_type: 'Fixed', rate: '', amount: 0 }];

const savedAddonCharges = Array.isArray(props.bilty.addon_charges) ? props.bilty.addon_charges : [];

const initialAddonCharges = savedAddonCharges.length > 0
    ? savedAddonCharges.map((charge) => ({
        particulars: charge.particulars || charge.description || '',
        amount: charge.amount ?? '',
    }))
    : [];

const savedEwayBills = props.bilty.eway_bills ?? props.bilty.ewayBills ?? [];

const formatDateInput = (value) => (value ? String(value).substr(0, 10) : '');

const initialEwayBills = savedEwayBills.length > 0
    ? savedEwayBills.map((eway) => ({
        id: eway.id ?? null,
        eway_bill_no: eway.eway_bill_no || '',
        eway_bill_valid_till: formatDateInput(eway.eway_bill_valid_till),
    }))
    : [];

const normalizeCartageType = (value) => {
    if (!value) return '';
    const normalized = String(value).toLowerCase();
    if (normalized === 'cash' || normalized === 'paid') return 'Cash';
    if (normalized === 'due') return 'Due';
    return value;
};

const form = useForm({
    consignor_id: props.bilty.consignor_id || '',
    consignee_id: props.bilty.consignee_id || '',
    from_station_id: props.bilty.from_station_id || '',
    to_station_id: props.bilty.to_station_id || '',
    bilty_number: props.bilty.bilty_number || '',
    date: props.bilty.date || new Date().toISOString().substr(0, 10),
    payment_type: props.bilty.payment_type || 'To Pay',

    // Details Grid
    details: initialDetails,

    // Middle section
    private_marka: props.bilty.private_marka || '',
    delivery_at: props.bilty.delivery_at || '',
    eway_bills: initialEwayBills,
    weight_actual: props.bilty.weight_actual || '',
    declared_value: props.bilty.declared_value || '',
    invoice_number: props.bilty.invoice_number || '',
    gst: props.bilty.gst || '',
    gst_liability_by: props.bilty.gst_liability_by || '',

    // Totals
    freight: props.bilty.freight || 0,
    bilty_charge: props.bilty.bilty_charge || 0,
    surcharge: props.bilty.surcharge || 0,
    handling_charge: props.bilty.handling_charge || 0,
    risk_dd_charge: props.bilty.risk_dd_charge || 0,
    cartage: props.bilty.cartage || 0,
    cartage_type: normalizeCartageType(props.bilty.cartage_type),
    vendor_id: props.bilty.vendor_id || '',
    addon_charges: initialAddonCharges,
    less_advance_received: props.bilty.less_advance_received || 0,
    advance_payment_mode: props.bilty.advance_payment_mode || '',
    amount: props.bilty.amount || 0,
    status: props.bilty.status || 'Pending'
});

const isTbb = computed(() => form.payment_type === 'TBB');

const filteredAgents = computed(() => {
    if (!form.to_station_id) {
        return agentOptions.value;
    }
    const station = stationOptions.value.find(s => s.id === form.to_station_id);
    if (!station || !station.city_id) {
        return agentOptions.value;
    }
    const filtered = agentOptions.value.filter(agent => agent.city_id === station.city_id);
    return filtered.length > 0 ? filtered : agentOptions.value;
});

const stationDefaultAgentName = (stationId) => {
    const station = stationOptions.value.find((item) => String(item.id) === String(stationId));
    return station?.default_agent_name || '';
};

let previousToStationDefaultAgent = stationDefaultAgentName(form.to_station_id);

watch(() => form.to_station_id, (nextStationId, previousStationId) => {
    if (String(nextStationId || '') === String(previousStationId || '')) {
        return;
    }

    const nextDefault = stationDefaultAgentName(nextStationId);
    const currentDeliveryAt = String(form.delivery_at || '');
    const previousDefault = previousToStationDefaultAgent;

    if (!currentDeliveryAt || currentDeliveryAt === previousDefault) {
        form.delivery_at = nextDefault;
    }

    previousToStationDefaultAgent = nextDefault;
});

const getConsignorGST = () => {
    if (!form.consignor_id) return '';
    const party = partyOptions.value.find(p => p.id === form.consignor_id);
    return party ? party.gst_number : '';
};

const getConsigneeGST = () => {
    if (!form.consignee_id) return '';
    const party = partyOptions.value.find(p => p.id === form.consignee_id);
    return party ? party.gst_number : '';
};

const findParty = (partyId) => {
    if (!partyId) {
        return null;
    }

    return partyOptions.value.find((party) => party.id === partyId) || null;
};

const partyState = (partyId) => findParty(partyId)?.state?.name || '';
const partyCity = (partyId) => findParty(partyId)?.city?.name || '';
const partyMobile = (partyId) => findParty(partyId)?.mobile || '';

const applyDefaultBiltyCharge = () => {
    if (!form.consignor_id) {
        calculateFinalTotal();
        return;
    }

    const party = partyOptions.value.find((p) => p.id === form.consignor_id);
    const charge = parseFloat(party?.bilty_charge);
    if (Number.isFinite(charge) && charge >= 0) {
        form.bilty_charge = charge;
    }

    calculateFinalTotal();
};

watch(() => form.consignor_id, () => {
    applyDefaultBiltyCharge();
});

const showCartageOptions = computed(() => parseFloat(form.cartage || 0) > 0);
const showAdvancePaymentMode = computed(() => parseFloat(form.less_advance_received || 0) > 0);
const gstChargeBase = computed(() => (
    parseFloat(form.freight || 0) +
    parseFloat(form.bilty_charge || 0) +
    parseFloat(form.surcharge || 0) +
    parseFloat(form.handling_charge || 0) +
    parseFloat(form.risk_dd_charge || 0) +
    parseFloat(form.cartage || 0) +
    totalAddonCharges()
));
const showGstFields = computed(() => gstChargeBase.value > Number(props.gst_exemption_limit || 0));
const showGstLiability = computed(() => {
    return Number(form.gst || 0) > 0 || form.gst_liability_by === 'Exempted';
});
const onCartageChange = () => {
    if (!showCartageOptions.value) {
        form.cartage_type = '';
        form.vendor_id = '';
    }
    calculateFinalTotal();
};

const onAdvanceReceivedChange = () => {
    if (!showAdvancePaymentMode.value) {
        form.advance_payment_mode = '';
    }
    calculateFinalTotal();
};

const onCartageTypeChange = () => {
    if (form.cartage_type !== 'Due') {
        form.vendor_id = '';
    }
};

const applyCompanyGst = () => {
    if (form.gst_liability_by === 'Exempted') {
        form.gst = 0;
    } else {
        form.gst = props.default_gst_percent;
    }
    calculateFinalTotal();
};

watch(() => form.gst_liability_by, applyCompanyGst);

onMounted(async () => {
    calculateFinalTotal();
    await nextTick();
    biltyNumberInput.value?.focus();
});

const addEwayBill = () => {
    form.eway_bills.push({ eway_bill_no: '', eway_bill_valid_till: '' });
};

const ewayRowHasData = (eway) => {
    if (!eway) {
        return false;
    }

    return Boolean(
        (eway.eway_bill_no !== '' && eway.eway_bill_no !== null && eway.eway_bill_no !== undefined)
        || (eway.eway_bill_valid_till !== '' && eway.eway_bill_valid_till !== null && eway.eway_bill_valid_till !== undefined)
    );
};

const removeEwayBill = (index) => {
    const eway = form.eway_bills[index];
    if (ewayRowHasData(eway) && !confirm('This e-way bill has data. Remove it?')) {
        return;
    }

    form.eway_bills.splice(index, 1);
};

const ewayError = (index, field) => form.errors[`eway_bills.${index}.${field}`];

const addAddonCharge = () => {
    form.addon_charges.push({ particulars: '', amount: '' });
};

const addonRowHasData = (charge) => {
    if (!charge) {
        return false;
    }

    return Boolean(
        (charge.particulars !== '' && charge.particulars !== null && charge.particulars !== undefined)
        || (charge.amount !== '' && charge.amount !== null && charge.amount !== undefined)
    );
};

const removeAddonCharge = (index) => {
    const charge = form.addon_charges[index];
    if (addonRowHasData(charge) && !confirm('This addon charge has data. Remove it?')) {
        return;
    }

    form.addon_charges.splice(index, 1);
    calculateFinalTotal();
};

const totalAddonCharges = () => {
    return form.addon_charges.reduce((sum, charge) => sum + (parseFloat(charge.amount) || 0), 0);
};

const emptyGoodsRow = () => ({
    good_id: '',
    package_type_id: '',
    no_of_packages: '',
    gross_weight: '',
    charged_weight: '',
    rate_type: 'Fixed',
    rate: '',
    amount: 0,
});

const addRow = () => {
    form.details.push(emptyGoodsRow());
};

const removeRow = (index) => {
    const row = form.details[index];
    if (goodsRowHasData(row) && !confirm('This goods row has data. Remove it?')) {
        return;
    }

    form.details.splice(index, 1);
    calculateTotalFreight();
};

const resetRow = (index) => {
    const row = form.details[index];
    if (goodsRowHasData(row) && !confirm('This goods row has data. Reset it?')) {
        return;
    }

    Object.assign(form.details[index], emptyGoodsRow());
    calculateTotalFreight();
};

const goodsRowHasData = (row) => {
    if (!row) {
        return false;
    }

    return Boolean(
        row.good_id
        || row.package_type_id
        || (row.no_of_packages !== '' && row.no_of_packages !== null && row.no_of_packages !== undefined)
        || (row.gross_weight !== '' && row.gross_weight !== null && row.gross_weight !== undefined)
        || (row.charged_weight !== '' && row.charged_weight !== null && row.charged_weight !== undefined)
        || (row.rate !== '' && row.rate !== null && row.rate !== undefined)
        || parseFloat(row.amount || 0) > 0
    );
};

const calculateGridRow = (index) => {
    const row = form.details[index];
    const maxWeight = 120000;
    const maxPackages = 500000;

    ['no_of_packages', 'gross_weight', 'charged_weight', 'rate'].forEach((field) => {
        const numeric = parseFloat(row[field]);
        if (Number.isFinite(numeric) && numeric < 0) {
            row[field] = 0;
        }
    });

    const packagesValue = parseFloat(row.no_of_packages);
    if (Number.isFinite(packagesValue) && packagesValue > maxPackages) {
        row.no_of_packages = maxPackages;
    }

    ['gross_weight', 'charged_weight'].forEach((field) => {
        const numeric = parseFloat(row[field]);
        if (Number.isFinite(numeric) && numeric > maxWeight) {
            row[field] = maxWeight;
        }
    });

    const weight = Math.min(maxWeight, Math.max(0, parseFloat(row.gross_weight) || 0));
    const chargedWeight = Math.min(maxWeight, Math.max(0, parseFloat(row.charged_weight) || 0));
    const packages = Math.min(maxPackages, Math.max(0, parseFloat(row.no_of_packages) || 0));
    const rate = Math.max(0, parseFloat(row.rate) || 0);

    let amount = 0;
    if (row.rate_type === 'Per Actual Weight' || row.rate_type === 'Per KG') {
        amount = rate * weight;
    } else if (row.rate_type === 'Per Charged Weight') {
        amount = rate * chargedWeight;
    } else if (row.rate_type === 'Per Package') {
        amount = rate * packages;
    } else {
        amount = rate;
    }

    row.amount = Math.max(0, amount).toFixed(2);
    calculateTotalFreight();
};

const calculateTotalFreight = () => {
    let totalWeight = 0;
    let totalFreight = 0;

    form.details.forEach(row => {
        totalWeight += parseFloat(row.gross_weight || 0);
        totalFreight += parseFloat(row.amount || 0);
    });

    form.weight_actual = totalWeight.toFixed(2);
    form.freight = totalFreight.toFixed(2);

    calculateFinalTotal();
};

const calculateFinalTotal = () => {
    const subtotal =
        parseFloat(form.freight || 0) +
        parseFloat(form.bilty_charge || 0) +
        parseFloat(form.surcharge || 0) +
        parseFloat(form.handling_charge || 0) +
        parseFloat(form.risk_dd_charge || 0) +
        parseFloat(form.cartage || 0) +
        totalAddonCharges();

    let gstRate = form.gst_liability_by === 'Exempted' ? 0 : (parseFloat(form.gst) || 0);
    if (subtotal <= Number(props.gst_exemption_limit || 0)) {
        gstRate = 0;
    }
    const gstValue = subtotal * (gstRate / 100);

    gstAmount.value = gstValue.toFixed(2);

    const lessAdvanceReceived = parseFloat(form.less_advance_received || 0);
    const gstInTotal = form.gst_liability_by === 'Transporter' ? gstValue : 0;
    form.amount = (subtotal + gstInTotal - lessAdvanceReceived).toFixed(2);
};

const submit = () => {
    openConfirm();
};

const biltyPrintUrl = `/bilties/${props.bilty.id}/print`;

const submitBilty = (printAfterSave = false) => {
    confirmAndRun(() => {
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .put(`/bilties/${props.bilty.id}`, withPrintAfterSave(printAfterSave, biltyPrintUrl));
    });
};

const confirmSave = () => submitBilty(false);
const confirmSaveAndPrint = () => submitBilty(true);

const clearForm = () => {
    form.reset();
    form.clearErrors();
    form.details = initialDetails.map((detail) => ({ ...detail }));
    form.addon_charges = initialAddonCharges.map((charge) => ({ ...charge }));
    form.eway_bills = initialEwayBills.map((eway) => ({ ...eway }));
    calculateFinalTotal();
};

const detailError = (index, field) => form.errors[`details.${index}.${field}`];
const rateTypeOptions = [
    { id: 'Fixed', name: 'Fixed' },
    { id: 'Per Actual Weight', name: 'Per Actual Weight' },
    { id: 'Per Charged Weight', name: 'Per Charged Weight' },
    { id: 'Per Package', name: 'Per Package' },
];
</script>

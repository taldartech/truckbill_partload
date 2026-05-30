<template>
    <AdminLayout title="Add Bilty">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary">
                    <!-- Title Bar -->
                    <div class="card-header">
                        <h3 class="card-title">Booking Bilty</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- Top Section: Core Details -->
                        <div class="card-body pt-3 pb-2 border-bottom">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Bilty No.</label>
                                    <div class="w-100">
                                        <input type="text" class="form-control form-control-sm border-secondary shadow-none" :class="{ 'is-invalid': form.errors.bilty_number }" v-model="form.bilty_number">
                                        <div v-if="form.errors.bilty_number" class="text-danger small mt-1">{{ form.errors.bilty_number }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Date</label>
                                    <div class="w-100">
                                        <input type="date" class="form-control form-control-sm border-secondary shadow-none" :class="{ 'is-invalid': form.errors.date }" v-model="form.date">
                                        <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center" v-if="$page.props.auth.is_admin">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Company</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="form.company_id"
                                        :options="companies"
                                        placeholder="Select Company"
                                    />
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Payment</label>
                                    <select class="form-select form-select-sm border-secondary shadow-none" v-model="form.payment_type">
                                        <option value="To Pay">To Pay</option>
                                        <option value="Paid">Paid</option>
                                        <option value="TBB">TBB</option>
                                        <option value="FOC">FOC</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row align-items-center mb-2">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">From St.</label>
                                    <SelectWithActions
                                        :small="true"
                                        v-model="form.from_station_id"
                                        :options="stationOptions"
                                        placeholder="Select Station"
                                        @view="entityModals?.viewStation(form.from_station_id)"
                                        @add="entityModals?.openStationAdd({ targetField: 'from_station_id' })"
                                    />
                                    <div v-if="form.errors.from_station_id" class="text-danger small ms-2">{{ form.errors.from_station_id }}</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Consignor</label>
                                    <SelectWithActions
                                        :small="true"
                                        v-model="form.consignor_id"
                                        :options="partyOptions"
                                        placeholder="Select Consignor"
                                        @view="entityModals?.viewParty(form.consignor_id)"
                                        @add="entityModals?.openPartyAdd({ targetField: 'consignor_id' })"
                                    />
                                    <div v-if="form.errors.consignor_id" class="text-danger small ms-2">{{ form.errors.consignor_id }}</div>
                                </div>
                            </div>

                            <div class="row align-items-center mb-2">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">To St.</label>
                                    <SelectWithActions
                                        :small="true"
                                        v-model="form.to_station_id"
                                        :options="stationOptions"
                                        placeholder="Select Station"
                                        @view="entityModals?.viewStation(form.to_station_id)"
                                        @add="entityModals?.openStationAdd({ targetField: 'to_station_id' })"
                                    />
                                    <div v-if="form.errors.to_station_id" class="text-danger small ms-2">{{ form.errors.to_station_id }}</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Consignee</label>
                                    <SelectWithActions
                                        :small="true"
                                        v-model="form.consignee_id"
                                        :options="partyOptions"
                                        placeholder="Select Consignee"
                                        @view="entityModals?.viewParty(form.consignee_id)"
                                        @add="entityModals?.openPartyAdd({ targetField: 'consignee_id' })"
                                    />
                                    <div v-if="form.errors.consignee_id" class="text-danger small ms-2">{{ form.errors.consignee_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Mid Section: Goods Grid -->
                        <div class="card-body p-0 border-bottom">
                            <div class="table-responsive" style="min-height: 200px;">
                                <table class="table table-bordered table-sm m-0 table-grid">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%" class="text-center">Sr.No.</th>
                                            <th width="30%">Goods Name</th>
                                            <th width="20%">Packing</th>
                                            <th width="10%" class="text-center">No.Of Pkg</th>
                                            <th width="10%" class="text-center">Actual Wt(Kg)</th>
                                            <th width="10%" class="text-center">Charged Wt(Kg)</th>
                                            <th width="10%" class="text-center">Frt.Type</th>
                                            <th width="10%" class="text-center">Frt.Rate</th>
                                            <th width="10%" class="text-center">Goods Frt</th>
                                            <th width="5%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" class="bg-white">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td>
                                                <SearchableSelect
                                                    :small="true"
                                                    v-model="detail.good_id"
                                                    :options="goods"
                                                    placeholder="Select Good"
                                                />
                                                <div v-if="detailError(index, 'good_id')" class="text-danger small">{{ detailError(index, 'good_id') }}</div>
                                            </td>
                                            <td>
                                                <SearchableSelect
                                                :small="true"
                                                v-model="detail.package_type_id"
                                                :options="package_types"
                                                placeholder="Select Packing"
                                            />
                                                <div v-if="detailError(index, 'package_type_id')" class="text-danger small">{{ detailError(index, 'package_type_id') }}</div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" :class="{ 'is-invalid': detailError(index, 'no_of_packages') }" v-model="detail.no_of_packages" @input="calculateGridRow(index)">
                                                <div v-if="detailError(index, 'no_of_packages')" class="text-danger small">{{ detailError(index, 'no_of_packages') }}</div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" :class="{ 'is-invalid': detailError(index, 'gross_weight') }" v-model="detail.gross_weight" @input="calculateGridRow(index)">
                                                <div v-if="detailError(index, 'gross_weight')" class="text-danger small">{{ detailError(index, 'gross_weight') }}</div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" :class="{ 'is-invalid': detailError(index, 'charged_weight') }" v-model="detail.charged_weight" @input="calculateGridRow(index)">
                                                <div v-if="detailError(index, 'charged_weight')" class="text-danger small">{{ detailError(index, 'charged_weight') }}</div>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm border-0 shadow-none text-center bg-transparent" :class="{ 'is-invalid': detailError(index, 'rate_type') }" v-model="detail.rate_type" @change="calculateGridRow(index)">
                                                    <option value="Fixed">Fixed</option>
                                                    <option value="Per Actual Weight">Per Actual Weight</option>
                                                    <option value="Per Charged Weight">Per Charged Weight</option>
                                                    <option value="Per Package">Per Package</option>
                                                </select>
                                                <div v-if="detailError(index, 'rate_type')" class="text-danger small">{{ detailError(index, 'rate_type') }}</div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" :class="{ 'is-invalid': detailError(index, 'rate') }" v-model="detail.rate" @input="calculateGridRow(index)">
                                                <div v-if="detailError(index, 'rate')" class="text-danger small">{{ detailError(index, 'rate') }}</div>
                                            </td>
                                            <td class="text-center align-middle fw-bold">
                                                {{ detail.amount || '0.00' }}
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeRow(index)" v-if="form.details.length > 1">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="bg-light p-1 text-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addRow">
                                                    <i class="fas fa-plus"></i> Add Row
                                                </button>
                                                <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Section: Freight, Tax & EWay -->
                        <div class="card-body pt-3 pb-2">
                            <div class="row">
                                <!-- Left Column: GST, EWay, Weight -->
                                <div class="col-md-5">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Consignor GST No:</label></div>
                                        <div class="col-7"><span class="small fw-bold text-muted">{{ getConsignorGST() }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Consignee GST No:</label></div>
                                        <div class="col-7"><span class="small fw-bold text-muted">{{ getConsigneeGST() }}</span></div>
                                    </div>
                                    
                                    <hr class="my-2 border-secondary">
                                    
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">GST Tax (%)</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-light" v-model="form.gst" readonly></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Liabilities By</label></div>
                                        <div class="col-7">
                                            <select class="form-select form-select-sm border-secondary shadow-none" v-model="form.gst_liability_by">
                                                <option value="">Select</option>
                                                <option value="Consignor">Consignor</option>
                                                <option value="Consignee">Consignee</option>
                                                <option value="Transporter">Transporter</option>
                                                <option value="Exempted">Exempted</option>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Weight Actual</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.weight_actual"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Declared Value <span class="text-muted fw-normal">(Optional)</span></label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.declared_value"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Invoice No. <span class="text-muted fw-normal">(Optional)</span></label></div>
                                        <div class="col-7"><input type="text" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.invoice_number"></div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="row align-items-center mb-1">
                                            <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">E-Way Bills</label></div>
                                            <div class="col-7 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addEwayBill">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                        <div v-for="(eway, index) in form.eway_bills" :key="eway.id ?? `new-${index}`" class="row mb-1 align-items-center">
                                            <div class="col-1 text-end">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeEwayBill(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control form-control-sm border-secondary shadow-none bg-white" :class="{ 'is-invalid': ewayError(index, 'eway_bill_no') }" v-model="eway.eway_bill_no" placeholder="E-Way Bill No">
                                                <div v-if="ewayError(index, 'eway_bill_no')" class="text-danger small">{{ ewayError(index, 'eway_bill_no') }}</div>
                                            </div>
                                            <div class="col-7">
                                                <input type="date" class="form-control form-control-sm border-secondary shadow-none bg-white" :class="{ 'is-invalid': ewayError(index, 'eway_bill_valid_till') }" v-model="eway.eway_bill_valid_till" placeholder="Valid Till">
                                                <div v-if="ewayError(index, 'eway_bill_valid_till')" class="text-danger small">{{ ewayError(index, 'eway_bill_valid_till') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle Column: Cartage, Marka, Delivery -->
                                <div class="col-md-3 border-start border-end border-secondary">
                                    <div class="row mb-1">
                                        <div class="col-12"><label class="mb-0 fw-bold small text-dark">Cartage</label></div>
                                        <div class="col-12"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none" v-model="form.cartage" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-12"><label class="mb-0 fw-bold small text-dark">Private Marka</label></div>
                                        <div class="col-12"><input type="text" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.private_marka"></div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-12"><label class="mb-0 fw-bold small text-dark">Delivery At</label></div>
                                        <div class="col-12"><input type="text" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.delivery_at"></div>
                                    </div>
                                </div>

                                <!-- Right Column: Freight & Totals -->
                                <div class="col-md-4">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Total Freight</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-light fw-bold" v-model="form.freight" readonly></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">BILTY CHARGE</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.bilty_charge" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">SURCHARGE</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.surcharge" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">HANDLING CH.</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.handling_charge" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">RISK/DD</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.risk_dd_charge" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="row align-items-center mb-1">
                                            <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Addon Charges</label></div>
                                            <div class="col-6 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addAddonCharge">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                        <div v-for="(charge, index) in form.addon_charges" :key="index" class="row mb-1 align-items-center">
                                            <div class="col-1 text-end">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeAddonCharge(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="col-5">
                                                <input type="text" class="form-control form-control-sm border-secondary shadow-none" v-model="charge.particulars" placeholder="Particulars" @input="calculateFinalTotal">
                                            </div>
                                            <div class="col-6">
                                                <input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none" v-model="charge.amount" placeholder="Amount" @input="calculateFinalTotal">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Less Adv.Rec</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.less_advance_received" @input="calculateFinalTotal"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">GST Amount</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-light" :value="gstAmount" readonly></div>
                                    </div>
                                    
                                    <hr class="my-2 border-secondary">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold text-danger">TOTAL AMOUNT</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-danger shadow-none bg-white fw-bold text-danger" v-model="form.amount" readonly></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons (Bottom Bar) -->
                        <div class="card-footer bg-light d-flex justify-content-center gap-2 border-top-0 pt-3 pb-3">
                            <Link href="/bilties" class="btn btn-outline-secondary px-4"><i class="fas fa-times me-1"></i> Exit</Link>
                            <button type="button" class="btn btn-outline-info px-4" @click="form.reset()"><i class="fas fa-eraser me-1"></i> Clear</button>
                            <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <BiltyEntityModals
            ref="entityModals"
            :default-company-id="form.company_id"
            @party-created="onPartyCreated"
            @station-created="onStationCreated"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SelectWithActions from '@/Components/SelectWithActions.vue';
import BiltyEntityModals from '@/Components/Bilty/BiltyEntityModals.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { watch, ref, onMounted } from 'vue';

const page = usePage();
const gstAmount = ref('0.00');
const entityModals = ref(null);
const partyOptions = ref([]);
const stationOptions = ref([]);

const props = defineProps({
    companies: Array,
    parties: Array,
    stations: Array,
    goods: Array,
    package_types: Array,
    next_bilty_number: String,
    company_gst_rates: Object,
});

partyOptions.value = [...props.parties];
stationOptions.value = [...props.stations];

const gstForCompany = (companyId) => props.company_gst_rates?.[companyId] ?? 18;

const form = useForm({
    company_id: '',
    consignor_id: '',
    consignee_id: '',
    from_station_id: '',
    to_station_id: '',
    bilty_number: props.next_bilty_number || '',
    date: new Date().toISOString().substr(0, 10),
    payment_type: 'To Pay',
    
    // Details Grid
    details: [
        { good_id: '', package_type_id: '', no_of_packages: '', gross_weight: '', charged_weight: '', rate_type: 'Fixed', rate: '', amount: 0 }
    ],
    
    // Middle section
    private_marka: '',
    delivery_at: '',
    eway_bills: [],
    weight_actual: '',
    declared_value: '',
    invoice_number: '',
    gst: gstForCompany(page.props.auth.user?.company_id),
    gst_liability_by: '',
    
    // Totals
    freight: 0,
    bilty_charge: 0,
    surcharge: 0,
    handling_charge: 0,
    risk_dd_charge: 0,
    cartage: 0,
    addon_charges: [],
    less_advance_received: 0,
    amount: 0,
    status: 'Pending'
});

const getConsignorGST = () => {
    if(!form.consignor_id) return '';
    const party = partyOptions.value.find(p => p.id === form.consignor_id);
    return party ? party.gst_number : '';
};

const getConsigneeGST = () => {
    if(!form.consignee_id) return '';
    const party = partyOptions.value.find(p => p.id === form.consignee_id);
    return party ? party.gst_number : '';
};

const onPartyCreated = ({ party, targetField }) => {
    if (!partyOptions.value.some((item) => item.id === party.id)) {
        partyOptions.value.push(party);
    }
    if (targetField) {
        form[targetField] = party.id;
    }
};

const onStationCreated = ({ station, targetField }) => {
    if (!stationOptions.value.some((item) => item.id === station.id)) {
        stationOptions.value.push(station);
    }
    if (targetField) {
        form[targetField] = station.id;
    }
};

const resolveCompanyId = () => form.company_id || page.props.auth.user?.company_id;

const applyCompanyGst = () => {
    if (form.gst_liability_by === 'Exempted') {
        form.gst = 0;
    } else {
        form.gst = gstForCompany(resolveCompanyId());
    }
    calculateFinalTotal();
};

watch(() => form.company_id, applyCompanyGst);
watch(() => form.gst_liability_by, applyCompanyGst);

onMounted(() => calculateFinalTotal());

const addEwayBill = () => {
    form.eway_bills.push({ eway_bill_no: '', eway_bill_valid_till: '' });
};

const removeEwayBill = (index) => {
    form.eway_bills.splice(index, 1);
};

const ewayError = (index, field) => form.errors[`eway_bills.${index}.${field}`];

const addAddonCharge = () => {
    form.addon_charges.push({ particulars: '', amount: '' });
};

const removeAddonCharge = (index) => {
    form.addon_charges.splice(index, 1);
    calculateFinalTotal();
};

const totalAddonCharges = () => {
    return form.addon_charges.reduce((sum, charge) => sum + (parseFloat(charge.amount) || 0), 0);
};

const addRow = () => {
    form.details.push({ good_id: '', package_type_id: '', no_of_packages: '', gross_weight: '', charged_weight: '', rate_type: 'Fixed', rate: '', amount: 0 });
};

const removeRow = (index) => {
    form.details.splice(index, 1);
    calculateTotalFreight();
};

const calculateGridRow = (index) => {
    const row = form.details[index];
    const weight = parseFloat(row.gross_weight) || 0;
    const chargedWeight = parseFloat(row.charged_weight) || 0;
    const packages = parseFloat(row.no_of_packages) || 0;
    const rate = parseFloat(row.rate) || 0;
    
    if (row.rate_type === 'Per Actual Weight' || row.rate_type === 'Per KG') {
        row.amount = (rate * weight).toFixed(2);
    } else if (row.rate_type === 'Per Charged Weight') {
        row.amount = (rate * chargedWeight).toFixed(2);
    } else if (row.rate_type === 'Per Package') {
        row.amount = (rate * packages).toFixed(2);
    } else {
        row.amount = rate.toFixed(2);
    }
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
        totalAddonCharges() -
        parseFloat(form.less_advance_received || 0);

    const gstRate = form.gst_liability_by === 'Exempted' ? 0 : (parseFloat(form.gst) || 0);
    const gstValue = subtotal * (gstRate / 100);

    gstAmount.value = gstValue.toFixed(2);
    form.amount = (subtotal + gstValue).toFixed(2);
};

const submit = () => {
    form.post('/bilties');
};

const detailError = (index, field) => form.errors[`details.${index}.${field}`];
</script>

<style scoped>
.bilty-form {
    font-size: 0.85rem;
}
.bilty-form label {
    font-size: 0.85rem;
    white-space: nowrap;
}
.bilty-form .form-control-sm, .bilty-form .form-select-sm {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}
.table-grid th, .table-grid td {
    border-color: #dee2e6;
    font-size: 0.85rem;
}
</style>

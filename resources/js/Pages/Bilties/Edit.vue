<template>
    <AdminLayout title="Edit Bilty">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-warning">
                    <!-- Title Bar -->
                    <div class="card-header">
                        <h3 class="card-title">Booking Bilty (Edit)</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- Top Section: Core Details -->
                        <div class="card-body pt-3 pb-2 border-bottom">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Bilty No.</label>
                                    <input type="text" class="form-control form-control-sm border-secondary shadow-none" v-model="form.bilty_number">
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Date</label>
                                    <input type="date" class="form-control form-control-sm border-secondary shadow-none" v-model="form.date">
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
                                    <SearchableSelect
    :small="true"
    v-model="form.from_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Consignor</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.consignor_id"
    :options="parties"
    placeholder="Select Consignor"
/>
                                </div>
                            </div>

                            <div class="row align-items-center mb-2">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">To St.</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.to_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Consignee</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.consignee_id"
    :options="parties"
    placeholder="Select Consignee"
/>
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
                                            <th width="10%" class="text-center">Weight(Kg)</th>
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
                                            </td>
                                            <td>
                                                <SearchableSelect
    :small="true"
    v-model="detail.package_type_id"
    :options="package_types"
    placeholder="Select Packing"
/>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.no_of_packages">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.gross_weight" @input="calculateGridRow(index)">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.rate" @input="calculateGridRow(index)">
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
                                            <td colspan="8" class="bg-light p-1 text-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addRow">
                                                    <i class="fas fa-plus"></i> Add Row
                                                </button>
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
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none" v-model="form.gst"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Liabilities By</label></div>
                                        <div class="col-7">
                                            <select class="form-select form-select-sm border-secondary shadow-none" v-model="form.gst_liability_by">
                                                <option value="">Select</option>
                                                <option value="Consignor">Consignor</option>
                                                <option value="Consignee">Consignee</option>
                                                <option value="Transporter">Transporter</option>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Weight Actual</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.weight_actual"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Declared Value</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.declared_value"></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">E-WayBill No</label></div>
                                        <div class="col-7"><input type="text" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.eway_bill_no"></div>
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
                                    <div class="row mb-1 mt-3">
                                        <div class="col-12"><label class="mb-0 fw-bold small text-dark">Status</label></div>
                                        <div class="col-12">
                                            <select class="form-select form-select-sm border-secondary shadow-none bg-white" v-model="form.status">
                                                <option value="Pending">Pending</option>
                                                <option value="In Transit">In Transit</option>
                                                <option value="Delivered">Delivered</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Freight & Totals -->
                                <div class="col-md-4">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Freight Type</label></div>
                                        <div class="col-6">
                                            <select class="form-select form-select-sm border-secondary shadow-none bg-white" v-model="form.freight_type" @change="calculateTotalFreight">
                                                <option value="Fixed">Fixed Amount</option>
                                                <option value="Per Package">Per Package</option>
                                                <option value="Per KG">Per KG</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Freight Rate</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.freight_rate" @input="calculateTotalFreight"></div>
                                    </div>
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
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Less Adv.Rec</label></div>
                                        <div class="col-6"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none bg-white" v-model="form.less_advance_received" @input="calculateFinalTotal"></div>
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
                            <button type="submit" class="btn btn-warning px-5 fw-bold shadow-sm" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    bilty: Object,
    companies: Array,
    parties: Array,
    stations: Array,
    goods: Array,
    package_types: Array
});

// Map existing details to the array format
const initialDetails = (props.bilty.bilty_details && props.bilty.bilty_details.length > 0) 
    ? props.bilty.bilty_details.map(d => ({
        good_id: d.good_id || '',
        package_type_id: d.package_type_id || '',
        no_of_packages: d.no_of_packages || '',
        gross_weight: d.gross_weight || '',
        rate: d.rate || '',
        amount: (d.gross_weight * d.rate).toFixed(2) || 0
      }))
    : [{ good_id: '', package_type_id: '', no_of_packages: '', gross_weight: '', rate: '', amount: 0 }];

const form = useForm({
    company_id: props.bilty.company_id || '',
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
    eway_bill_no: props.bilty.eway_bill_no || '',
    weight_actual: props.bilty.weight_actual || '',
    declared_value: props.bilty.declared_value || '',
    gst: props.bilty.gst || '',
    gst_liability_by: props.bilty.gst_liability_by || '',
    
    // Totals
    freight_rate: props.bilty.freight_rate || '',
    freight_type: props.bilty.freight_type || 'Fixed',
    freight: props.bilty.freight || 0,
    bilty_charge: props.bilty.bilty_charge || 0,
    surcharge: props.bilty.surcharge || 0,
    handling_charge: props.bilty.handling_charge || 0,
    risk_dd_charge: props.bilty.risk_dd_charge || 0,
    cartage: props.bilty.cartage || 0,
    less_advance_received: props.bilty.less_advance_received || 0,
    amount: props.bilty.amount || 0,
    status: props.bilty.status || 'Pending'
});

const getConsignorGST = () => {
    if(!form.consignor_id) return '';
    const party = props.parties.find(p => p.id === form.consignor_id);
    return party ? party.gst_number : '';
};

const getConsigneeGST = () => {
    if(!form.consignee_id) return '';
    const party = props.parties.find(p => p.id === form.consignee_id);
    return party ? party.gst_number : '';
};

const addRow = () => {
    form.details.push({ good_id: '', package_type_id: '', no_of_packages: '', gross_weight: '', rate: '', amount: 0 });
};

const removeRow = (index) => {
    form.details.splice(index, 1);
    calculateTotalFreight();
};

const calculateGridRow = (index) => {
    const row = form.details[index];
    const weight = parseFloat(row.gross_weight) || 0;
    const rate = parseFloat(row.rate) || 0;
    
    row.amount = (weight > 0 ? weight * rate : rate).toFixed(2);
    calculateTotalFreight();
};

const calculateTotalFreight = () => {
    let totalWeight = 0;
    let totalPackages = 0;
    
    form.details.forEach(row => {
        totalWeight += parseFloat(row.gross_weight || 0);
        totalPackages += parseFloat(row.no_of_packages || 0);
    });
    
    form.weight_actual = totalWeight.toFixed(2);

    let frtRate = parseFloat(form.freight_rate || 0);
    if (form.freight_type === 'Per KG') {
        form.freight = (frtRate * totalWeight).toFixed(2);
    } else if (form.freight_type === 'Per Package') {
        form.freight = (frtRate * totalPackages).toFixed(2);
    } else {
        form.freight = frtRate.toFixed(2);
    }
    
    calculateFinalTotal();
};

const calculateFinalTotal = () => {
    const sum = 
        parseFloat(form.freight || 0) + 
        parseFloat(form.bilty_charge || 0) + 
        parseFloat(form.surcharge || 0) + 
        parseFloat(form.handling_charge || 0) + 
        parseFloat(form.risk_dd_charge || 0) + 
        parseFloat(form.cartage || 0) -
        parseFloat(form.less_advance_received || 0);

    form.amount = sum.toFixed(2);
};

const submit = () => {
    form.put(`/bilties/${props.bilty.id}`);
};
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

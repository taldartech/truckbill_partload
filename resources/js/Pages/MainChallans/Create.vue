<template>
    <AdminLayout title="Add Main Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary" style="background-color: #d1f2cf;">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">MAIN CHALLAN Add/New</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- HEADER ROW 1 -->
                        <div class="card-body pt-3 pb-2 border-bottom bg-light">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 110px;">Main Chal.No.</label>
                                    <input type="text" class="form-control form-control-sm shadow-none" v-model="form.main_challan_number">
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 60px;">Date</label>
                                    <input type="date" class="form-control form-control-sm shadow-none" v-model="form.date">
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 60px;">From <span class="text-danger">*</span></label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.from_station_id" :options="stations" placeholder="Select Station" />
                                    </div>
                                    <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{ form.errors.from_station_id }}</div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 60px;">To <span class="text-danger">*</span></label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.to_station_id" :options="stations" placeholder="Select Station" />
                                    </div>
                                    <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{ form.errors.to_station_id }}</div>
                                </div>
                            </div>
                            
                            <!-- HEADER ROW 2 -->
                            <div class="row align-items-center mb-2 mt-2">
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 110px;">Driver Name <span class="text-danger">*</span></label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.driver_id" :options="drivers" placeholder="Select Driver" />
                                    </div>
                                    <div v-if="form.errors.driver_id" class="text-danger small mt-1">{{ form.errors.driver_id }}</div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 90px;">Truck No. <span class="text-danger">*</span></label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.truck_id" :options="trucks" labelKey="truck_number" placeholder="Select Truck" />
                                    </div>
                                    <div v-if="form.errors.truck_id" class="text-danger small mt-1">{{ form.errors.truck_id }}</div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-primary text-nowrap" style="min-width: 90px;">Add Challan</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="fetch_challan_id" :options="challans" labelKey="challan_number" placeholder="Select Challan" />
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-primary text-nowrap shadow-sm w-100" @click="addChallan" :disabled="!fetch_challan_id">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- GRID SECTION -->
                        <div class="card-body p-0 border-bottom bg-white">
                            <div class="table-responsive" style="min-height: 250px;">
                                <table class="table table-bordered table-sm m-0 table-grid">
                                    <thead style="background-color: #aee4eb;">
                                        <tr>
                                            <th width="4%" class="text-center">Sr.No.</th>
                                            <th width="12%">Challan No.</th>
                                            <th width="15%">Station(To)</th>
                                            <th width="8%" class="text-center">Nugs</th>
                                            <th width="8%" class="text-center">Weight</th>
                                            <th width="10%" class="text-end">To Pay</th>
                                            <th width="10%" class="text-end">Paid/Adv</th>
                                            <th width="10%" class="text-end">Crossing</th>
                                            <th width="10%" class="text-end">Cartage</th>
                                            <th width="10%" class="text-end">Labour</th>
                                            <th width="3%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" :class="index % 2 === 0 ? 'bg-light' : 'bg-white'">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td class="align-middle">{{ detail.challan_number || '--' }}</td>
                                            <td class="align-middle">{{ detail.to_station_name || '--' }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.nugs" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.weight" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.to_pay" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.paid_amount" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.crossing" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.cartage" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.labour" readonly>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeRow(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.details.length === 0">
                                            <td colspan="11" class="bg-white p-3 text-center text-muted">
                                                Please select a challan from above to add it to the Main Challan.
                                                <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- FOOTER TOTALS -->
                        <div class="card-body p-0 border-top" style="background-color: #8ce175; padding-top: 10px !important; padding-bottom: 10px !important;">
                            <div class="row m-0 p-2">
                                <!-- Left side Totals -->
                                <div class="col-md-8 border-end border-dark">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">Total Weight</label>
                                                <span class="fw-bold text-primary">{{ totalWeight }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <label class="mb-0 fw-bold small text-dark">Total Nugs</label>
                                                <span class="fw-bold text-primary">{{ totalNugs }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">To Pay Amount</label>
                                                <span class="fw-bold text-primary">{{ toPayAmount }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <label class="mb-0 fw-bold small text-dark">Paid Amt/Adv.Rec.</label>
                                                <span class="fw-bold text-primary">{{ paidAmount }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">Crossing Freight</label>
                                                <span class="fw-bold text-primary">{{ crossingFreight }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">Cartage/Dalali/PF</label>
                                                <span class="fw-bold text-primary">{{ cartageAmount }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-nowrap">GST Tax</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.gst" @input="calculateTotal">
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="border-dark my-1">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <label class="mb-0 fw-bold small text-dark">Less :-</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="d-flex align-items-center mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-end me-2" style="width: 120px;">Labour Comm.</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.labour_charge" @input="calculateTotal">
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-end me-2" style="width: 120px;">Kat</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.kat" @input="calculateTotal">
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-end me-2" style="width: 120px;">Home Delivery</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.home_delivery_charge" @input="calculateTotal">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right side Financials -->
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Truck Freight</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.truck_freight" @input="calculateTotal">
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Cash Receive</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.cash_received" @input="calculateTotal">
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Cash Paid</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.cash_paid" @input="calculateTotal">
                                    </div>
                                    <hr class="border-dark my-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold text-dark">TOTAL</label>
                                        <span class="fw-bold fs-5" :class="form.total_amount < 0 ? 'text-danger' : 'text-dark'">{{ form.total_amount }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Total Balance Amt.</label>
                                        <select class="form-control form-control-sm w-50 p-1 bg-secondary text-white border-0" v-model="form.total_balance_type">
                                            <option value="Cr">Cr</option>
                                            <option value="Dr">Dr</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">To (Account)</label>
                                        <input type="text" class="form-control form-control-sm w-50 p-1" v-model="form.pay_by">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Remark Section -->
                            <div class="row m-0 p-2 border-top border-dark">
                                <div class="col-md-12 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold small text-dark text-nowrap">Remark :-</label>
                                    <input type="text" class="form-control form-control-sm shadow-none" v-model="form.remark">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-footer d-flex justify-content-center gap-3" style="background-color: #8ce175;">
                            <button type="submit" class="btn btn-sm btn-outline-dark px-4 fw-bold shadow-sm" style="background-color: #a7c5c9;" :disabled="form.processing">
                                Save
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark px-4 fw-bold shadow-sm" style="background-color: #a7c5c9;" onclick="window.print()">
                                Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark px-4 fw-bold shadow-sm" style="background-color: #a7c5c9;" @click="form.reset()">
                                Clear
                            </button>
                            <Link href="/main-challans" class="btn btn-sm btn-outline-dark px-4 fw-bold shadow-sm" style="background-color: #a7c5c9;">
                                Exit
                            </Link>
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
import { computed, ref, watch } from 'vue';

const fetch_challan_id = ref('');

const props = defineProps({
    companies: Array,
    stations: Array,
    drivers: Array,
    trucks: Array,
    challans: Array,
    next_main_challan_number: String
});

const form = useForm({
    company_id: '',
    main_challan_number: props.next_main_challan_number || '',
    date: new Date().toISOString().substr(0, 10),
    from_station_id: '',
    to_station_id: '',
    driver_id: '',
    truck_id: '',
    details: [],
    truck_freight: 0,
    labour_charge: 0,
    kat: 0,
    home_delivery_charge: 0,
    crossing_freight: 0,
    cartage_dalali_pf: 0,
    other_charge: 0,
    gst: 0,
    paid_amount: 0,
    to_pay_amount: 0,
    cash_received: 0,
    cash_paid: 0,
    total_amount: 0,
    total_weight: 0,
    total_packages: 0,
    total_balance_type: 'Cr',
    pay_by: '',
    remark: '',
});

const removeRow = (index) => {
    form.details.splice(index, 1);
    calculateTotal();
};

const addChallan = () => {
    if (!fetch_challan_id.value) return;
    
    // Check if already added
    const existing = form.details.find(d => d.challan_id === fetch_challan_id.value);
    if (existing) {
        alert('This challan is already added.');
        return;
    }
    
    const challan = props.challans.find(c => c.id === fetch_challan_id.value);
    if (challan) {
        const toStation = props.stations.find(s => s.id === challan.to_station_id);
        
        let toPay = challan.to_pay_amount || 0;
        let paid = challan.paid_amount || 0;
        
        // If the challan has no saved total_weight/packages, we can try to extract from its details if needed
        let totalNugs = challan.total_packages || 0;
        let totalWeight = challan.total_weight || 0;
        if(totalNugs === 0 && challan.details) {
            challan.details.forEach(detail => {
                if(detail.bilty && detail.bilty.bilty_details) {
                    detail.bilty.bilty_details.forEach(bd => {
                        totalNugs += parseFloat(bd.no_of_packages || 0);
                        totalWeight += parseFloat(bd.gross_weight || 0);
                    });
                }
            });
        }
        
        form.details.push({
            challan_id: challan.id,
            challan_number: challan.challan_number,
            to_station_name: toStation ? toStation.name : '--',
            nugs: totalNugs,
            weight: totalWeight,
            to_pay: toPay,
            paid_amount: paid,
            crossing: challan.crossing_freight || 0,
            cartage: challan.cartage_dalali_pf || 0,
            labour: challan.labour_charge || 0
        });
        
        fetch_challan_id.value = ''; // Reset select
        calculateTotal();
    }
};

const totalPackages = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.nugs) || 0), 0);
});

const totalWeight = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
});

const toPayAmount = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.to_pay) || 0), 0);
});

const paidAmount = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0);
});

const crossingFreight = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.crossing) || 0), 0);
});

const cartageAmount = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.cartage) || 0), 0);
});

const totalLabour = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.labour) || 0), 0);
});

watch([totalPackages, totalWeight, toPayAmount, paidAmount, crossingFreight, cartageAmount], () => {
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
    form.crossing_freight = crossingFreight.value;
    form.cartage_dalali_pf = cartageAmount.value;
});

const calculateTotal = () => {
    // Standard sum calculation
    const additions = parseFloat(form.truck_freight || 0) 
        + parseFloat(form.gst || 0)
        + parseFloat(form.cash_received || 0);
        
    const deductions = parseFloat(form.labour_charge || 0)
        + parseFloat(form.kat || 0)
        + parseFloat(form.home_delivery_charge || 0)
        + parseFloat(form.cash_paid || 0);
        
    form.total_amount = (additions - deductions).toFixed(2);
};

const submit = () => {
    // Ensure bound before submit
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
    form.crossing_freight = crossingFreight.value;
    form.cartage_dalali_pf = cartageAmount.value;
    form.total_amount = parseFloat(form.total_amount);
    
    form.post('/main-challans');
};
</script>

<style scoped>
.table-grid th, .table-grid td {
    border-color: #dee2e6;
    padding: 0.25rem;
}
.table-grid th {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}
</style>

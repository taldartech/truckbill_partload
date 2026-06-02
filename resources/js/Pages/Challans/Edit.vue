<template>
    <AdminLayout title="Edit Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary" style="background-color: #d1f2cf;">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">CHALLAN Edit</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- HEADER ROW 1 -->
                        <div class="card-body pt-3 pb-2 border-bottom bg-light">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 90px;">Challan No.</label>
                                    <input type="text" class="form-control form-control-sm shadow-none" v-model="form.challan_number">
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
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-primary text-nowrap" style="min-width: 90px;">Add Bilties For</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="fetch_station_id" :options="stations" placeholder="Station Wise" />
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-primary text-nowrap shadow-sm w-100" @click="fetchBiltiesByStation" :disabled="!fetch_station_id">
                                        <i class="fas fa-magic"></i> Add
                                    </button>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 90px;">Driver Name <span class="text-danger">*</span></label>
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
                            </div>
                        </div>

                        <!-- GRID SECTION -->
                        <div class="card-body p-0 border-bottom bg-white">
                            <div class="table-responsive" style="min-height: 250px;">
                                <table class="table table-bordered table-sm m-0 table-grid">
                                    <thead style="background-color: #aee4eb;">
                                        <tr>
                                            <th width="4%" class="text-center">Sr.No.</th>
                                            <th width="15%">GR/Bilty No.<span class="text-danger">*</span></th>
                                            <th width="15%">Station(To)</th>
                                            <th width="8%" class="text-center">Nugs</th>
                                            <th width="8%" class="text-center">Weight</th>
                                            <th width="15%">Goods Name</th>
                                            <th width="10%" class="text-end">Freight</th>
                                            <th width="10%" class="text-center">B.O.B.</th>
                                            <th width="10%" class="text-center">Booking</th>
                                            <th width="5%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" :class="index % 2 === 0 ? 'bg-light' : 'bg-white'">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td>
                                                <SearchableSelect :small="true" @change="fetchBiltyDetails(index)" v-model="detail.bilty_id" :options="bilties" labelKey="bilty_number" placeholder="Select Bilty" />
                                                <div v-if="form.errors['details.' + index + '.bilty_id']" class="text-danger small mt-1 text-start">{{ form.errors['details.' + index + '.bilty_id'] }}</div>
                                            </td>
                                            <td class="align-middle">{{ detail.to_station_name || '--' }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.nugs" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.weight" readonly>
                                            </td>
                                            <td class="align-middle">{{ detail.goods_name || '--' }}</td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-end bg-transparent" v-model="detail.freight" readonly>
                                            </td>
                                            <td class="align-middle text-center">{{ detail.bob || '--' }}</td>
                                            <td class="align-middle text-center">Booking</td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeRow(index)" v-if="form.details.length > 1">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="bg-white p-1 text-center border-top">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addRow">
                                                    <i class="fas fa-plus"></i> Add Bilty
                                                </button>
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
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">Crossing Freight</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.crossing_freight" @input="calculateTotal">
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-nowrap">Cartage/Dalali/PF</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.cartage_dalali_pf" @input="calculateTotal">
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
                                        <input type="number" step="0.01" class="form-control form-control-sm w-50 p-1" v-model="form.cash_payment" @input="calculateTotal">
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
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Delivery At</label>
                                        <input type="text" class="form-control form-control-sm w-50 p-1" v-model="form.delivery_at">
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
                            <Link href="/challans" class="btn btn-sm btn-outline-dark px-4 fw-bold shadow-sm" style="background-color: #a7c5c9;">
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
import { computed, ref, watch, onMounted } from 'vue';

const fetch_station_id = ref('');

const props = defineProps({
    challan: Object,
    companies: Array,
    stations: Array,
    drivers: Array,
    trucks: Array,
    bilties: Array
});

const form = useForm({
    company_id: props.challan.company_id || '',
    challan_number: props.challan.challan_number || '',
    date: props.challan.date || new Date().toISOString().substr(0, 10),
    from_station_id: props.challan.from_station_id || '',
    to_station_id: props.challan.to_station_id || '',
    driver_id: props.challan.driver_id || '',
    truck_id: props.challan.truck_id || '',
    details: [],
    truck_freight: props.challan.truck_freight || 0,
    labour_charge: props.challan.labour_charge || 0,
    kat: props.challan.kat || 0,
    home_delivery_charge: props.challan.home_delivery_charge || 0,
    crossing_freight: props.challan.crossing_freight || 0,
    cartage_dalali_pf: props.challan.cartage_dalali_pf || 0,
    other_charge: props.challan.other_charge || 0,
    gst: props.challan.gst || 0,
    paid_amount: props.challan.paid_amount || 0,
    to_pay_amount: props.challan.to_pay_amount || 0,
    cash_received: props.challan.cash_received || 0,
    cash_payment: props.challan.cash_payment || 0,
    total_amount: props.challan.total_amount || 0,
    total_weight: props.challan.total_weight || 0,
    total_packages: props.challan.total_packages || 0,
    total_balance_type: 'Cr',
    pay_by: props.challan.pay_by || '',
    delivery_at: props.challan.delivery_at || '',
    add_to_main_challan: props.challan.add_to_main_challan || false,
    remark: props.challan.remark || '',
    status: props.challan.status || 'Draft'
});

onMounted(() => {
    if (props.challan.details && props.challan.details.length > 0) {
        props.challan.details.forEach(detail => {
            if (detail.bilty) {
                let totalNugs = 0;
                let totalWeight = 0;
                let goodName = '--';
                
                if (detail.bilty.bilty_details && detail.bilty.bilty_details.length > 0) {
                    detail.bilty.bilty_details.forEach(bd => {
                        totalNugs += parseFloat(bd.no_of_packages || 0);
                        totalWeight += parseFloat(bd.gross_weight || 0);
                    });
                    goodName = detail.bilty.bilty_details[0].good ? detail.bilty.bilty_details[0].good.name : '--';
                }
                
                const toStation = props.stations.find(s => s.id === detail.bilty.to_station_id);

                form.details.push({
                    bilty_id: detail.bilty_id,
                    to_station_name: toStation ? toStation.name : '--',
                    nugs: totalNugs,
                    weight: totalWeight,
                    goods_name: goodName,
                    freight: detail.bilty.freight || 0,
                    bob: detail.bilty.payment_type || '--'
                });
            }
        });
    }
    if (form.details.length === 0) {
        addRow();
    }
    calculateTotal();
});

const addRow = () => {
    form.details.push({ bilty_id: '', to_station_name: '', nugs: 0, weight: 0, goods_name: '', freight: 0, bob: '' });
};

const removeRow = (index) => {
    if (form.details.length > 1) {
        form.details.splice(index, 1);
        calculateTotal();
    }
};

const fetchBiltyDetails = (index) => {
    const detail = form.details[index];
    if (detail.bilty_id) {
        const bilty = props.bilties.find(b => b.id === detail.bilty_id);
        if (bilty) {
            let totalNugs = 0;
            let totalWeight = 0;
            let goodName = '--';
            
            if (bilty.bilty_details && bilty.bilty_details.length > 0) {
                bilty.bilty_details.forEach(bd => {
                    totalNugs += parseFloat(bd.no_of_packages || 0);
                    totalWeight += parseFloat(bd.gross_weight || 0);
                });
                goodName = bilty.bilty_details[0].good ? bilty.bilty_details[0].good.name : '--';
            }
            
            const toStation = props.stations.find(s => s.id === bilty.to_station_id);

            detail.to_station_name = toStation ? toStation.name : '--';
            detail.nugs = totalNugs;
            detail.weight = totalWeight;
            detail.goods_name = goodName;
            detail.freight = bilty.freight || 0;
            detail.bob = bilty.payment_type || '--';
            
            calculateTotal();
        }
    } else {
        detail.to_station_name = '';
        detail.nugs = 0;
        detail.weight = 0;
        detail.goods_name = '';
        detail.freight = 0;
        detail.bob = '';
        calculateTotal();
    }
};

const fetchBiltiesByStation = () => {
    if (!fetch_station_id.value) return;
    
    // Clear out empty rows
    form.details = form.details.filter(d => d.bilty_id !== '');
    
    // Find all bilties for this destination station that are not already in the grid
    const existingBiltyIds = form.details.map(d => d.bilty_id);
    const biltiesToAdd = props.bilties.filter(b => 
        b.to_station_id === fetch_station_id.value && 
        !existingBiltyIds.includes(b.id)
    );
    
    if (biltiesToAdd.length === 0) {
        alert('No pending bilties found for this station, or they are already added.');
        if (form.details.length === 0) addRow(); // Ensure at least one row
        return;
    }
    
    // Add each bilty to the grid
    biltiesToAdd.forEach(bilty => {
        let totalNugs = 0;
        let totalWeight = 0;
        let goodName = '--';
        
        if (bilty.bilty_details && bilty.bilty_details.length > 0) {
            bilty.bilty_details.forEach(bd => {
                totalNugs += parseFloat(bd.no_of_packages || 0);
                totalWeight += parseFloat(bd.gross_weight || 0);
            });
            goodName = bilty.bilty_details[0].good ? bilty.bilty_details[0].good.name : '--';
        }
        
        const toStation = props.stations.find(s => s.id === bilty.to_station_id);
        
        form.details.push({
            bilty_id: bilty.id,
            to_station_name: toStation ? toStation.name : '--',
            nugs: totalNugs,
            weight: totalWeight,
            goods_name: goodName,
            freight: bilty.freight || 0,
            bob: bilty.payment_type || '--'
        });
    });
    
    calculateTotal();
};

const totalPackages = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.nugs) || 0), 0);
});

const totalWeight = computed(() => {
    return form.details.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
});

const toPayAmount = computed(() => {
    return form.details.reduce((sum, item) => {
        if (item.bob === 'To Pay') return sum + (parseFloat(item.freight) || 0);
        return sum;
    }, 0);
});

const paidAmount = computed(() => {
    return form.details.reduce((sum, item) => {
        if (item.bob === 'Paid' || item.bob === 'TBB') return sum + (parseFloat(item.freight) || 0);
        return sum;
    }, 0);
});

watch([totalPackages, totalWeight, toPayAmount, paidAmount], () => {
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
});

const calculateTotal = () => {
    // Standard sum calculation
    const additions = parseFloat(form.truck_freight || 0) 
        + parseFloat(form.crossing_freight || 0)
        + parseFloat(form.cartage_dalali_pf || 0)
        + parseFloat(form.other_charge || 0)
        + parseFloat(form.gst || 0)
        + parseFloat(form.cash_received || 0);
        
    const deductions = parseFloat(form.labour_charge || 0)
        + parseFloat(form.kat || 0)
        + parseFloat(form.home_delivery_charge || 0)
        + parseFloat(form.cash_payment || 0);
        
    form.total_amount = (additions - deductions).toFixed(2);
};

const submit = () => {
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
    
    form.put('/challans/' + props.challan.id);
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
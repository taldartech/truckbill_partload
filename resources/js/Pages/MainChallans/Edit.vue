<template>
    <AdminLayout title="Edit Main Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card ops-form-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">MAIN CHALLAN Edit</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- HEADER ROW 1 -->
                        <div class="card-body pt-3 pb-2 border-bottom bg-light">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 110px;">Main Chal.No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.main_challan_number">
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 60px;">Date</label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date">
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
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-primary text-nowrap" style="min-width: 90px;">Add Challan</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect
                                            ref="fetchChallanSelect"
                                            :small="true"
                                            v-model="fetch_challan_id"
                                            :options="challans"
                                            labelKey="challan_number"
                                            placeholder="Select Challan"
                                            @change="onFetchChallanChange"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GRID SECTION -->
                        <div class="card-body p-0 border-bottom bg-white">
                            <div class="table-responsive" style="min-height: 250px;">
                                <table class="table table-bordered table-sm m-0 table-grid ops-form-table">
                                    <thead>
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
                                                <input type="number" class="form-control form-control-sm text-center" v-model="detail.nugs" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center" v-model="detail.weight" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.to_pay" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.paid_amount" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.crossing" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.cartage" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.labour" readonly>
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
                        <div class="card-body p-0 border-top ops-form-summary py-2">
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
                                                <span class="fw-bold text-primary">{{ totalPackages }}</span>
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
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-nowrap">GST Tax</label>
                                                <span class="fw-bold text-primary">{{ gstDisplay }}</span>
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
                                                <span class="fw-bold text-primary">{{ labourDisplay }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-end me-2" style="width: 120px;">Kat</label>
                                                <span class="fw-bold text-primary">{{ katDisplay }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-end me-2" style="width: 120px;">Home Delivery</label>
                                                <span class="fw-bold text-primary">{{ homeDeliveryDisplay }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right side Financials -->
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Truck Freight</label>
                                        <span class="fw-bold text-primary">{{ truckFreightDisplay }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Cash Receive</label>
                                        <span class="fw-bold text-primary">{{ cashReceivedDisplay }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Cash Paid</label>
                                        <span class="fw-bold text-primary">{{ cashPaidDisplay }}</span>
                                    </div>
                                    <hr class="border-dark my-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold text-dark">TOTAL</label>
                                        <span class="fw-bold fs-5" :class="form.total_amount < 0 ? 'text-danger' : 'text-dark'">{{ form.total_amount }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Total Balance Amt.</label>
                                        <span class="fw-bold text-primary">{{ form.total_balance_type }}</span>
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
                                    <input type="text" class="form-control form-control-sm" v-model="form.remark">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-footer ops-form-footer d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm" :disabled="form.processing">
                                Save
                            </button>
                            <a :href="`/main-challans/${main_challan.id}/print`" target="_blank" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm">
                                Print
                            </a>
                            <Link href="/main-challans" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm">
                                Exit
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <MainChallanConfirmModal
            :show="showConfirm"
            :form="form"
            :stations="stations"
            :trucks="trucks"
            :drivers="drivers"
            :totals="confirmTotals"
            @cancel="closeConfirm"
            @confirm="confirmSave"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MainChallanConfirmModal from '@/Components/FormConfirm/MainChallanConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch, onMounted } from 'vue';

const fetch_challan_id = ref('');
const fetchChallanSelect = ref(null);
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const props = defineProps({
    main_challan: Object,
    stations: Array,
    drivers: Array,
    trucks: Array,
    challans: Array,
});

const form = useForm({
    main_challan_number: props.main_challan.main_challan_number || '',
    date: props.main_challan.date || new Date().toISOString().substr(0, 10),
    from_station_id: props.main_challan.from_station_id || '',
    to_station_id: props.main_challan.to_station_id || '',
    driver_id: props.main_challan.driver_id || '',
    truck_id: props.main_challan.truck_id || '',
    details: [],
    truck_freight: props.main_challan.truck_freight || 0,
    labour_charge: props.main_challan.labour_charge || 0,
    kat: props.main_challan.kat || 0,
    home_delivery_charge: props.main_challan.home_delivery_charge || 0,
    crossing_freight: props.main_challan.crossing_freight || 0,
    cartage_dalali_pf: props.main_challan.cartage_dalali_pf || 0,
    other_charge: props.main_challan.other_charge || 0,
    gst: props.main_challan.gst || 0,
    paid_amount: props.main_challan.paid_amount || 0,
    to_pay_amount: props.main_challan.to_pay_amount || 0,
    cash_received: props.main_challan.cash_received || 0,
    cash_paid: props.main_challan.cash_paid || 0,
    total_amount: props.main_challan.total_amount || 0,
    total_weight: props.main_challan.total_weight || 0,
    total_packages: props.main_challan.total_packages || 0,
    total_balance_type: 'Cr',
    pay_by: props.main_challan.pay_by || '',
    remark: props.main_challan.remark || '',
});

onMounted(() => {
    if (props.main_challan.details && props.main_challan.details.length > 0) {
        props.main_challan.details.forEach((detail) => {
            if (detail.challan) {
                form.details.push(challanToDetailRow(detail.challan));
            }
        });
    }
    syncTotals();
});

const challanToDetailRow = (challan) => {
    const toStation = props.stations.find((s) => s.id === challan.to_station_id);

    let totalNugs = parseFloat(challan.total_packages || 0);
    let totalWeight = parseFloat(challan.total_weight || 0);

    if (challan.details?.length) {
        let nugsFromDetails = 0;
        let weightFromDetails = 0;

        challan.details.forEach((detail) => {
            if (detail.bilty?.bilty_details) {
                detail.bilty.bilty_details.forEach((bd) => {
                    nugsFromDetails += parseFloat(bd.no_of_packages || 0);
                    weightFromDetails += parseFloat(bd.gross_weight || 0);
                });
            }
            if (detail.inward_register_detail) {
                nugsFromDetails += parseFloat(detail.inward_register_detail.no_of_packages || 0);
                weightFromDetails += parseFloat(detail.inward_register_detail.weight || 0);
            }
        });

        if (nugsFromDetails > 0) {
            totalNugs = nugsFromDetails;
        }
        if (weightFromDetails > 0) {
            totalWeight = weightFromDetails;
        }
    }

    return {
        challan_id: challan.id,
        challan_number: challan.challan_number,
        to_station_name: toStation ? toStation.name : '--',
        nugs: totalNugs,
        weight: totalWeight,
        to_pay: parseFloat(challan.to_pay_amount || 0),
        paid_amount: parseFloat(challan.paid_amount || 0),
        crossing: parseFloat(challan.crossing_freight || 0),
        cartage: parseFloat(challan.cartage_dalali_pf || 0),
        labour: parseFloat(challan.labour_charge || 0),
        kat: parseFloat(challan.kat || 0),
        home_delivery: parseFloat(challan.home_delivery_charge || 0),
        truck_freight: parseFloat(challan.truck_freight || 0),
        gst: parseFloat(challan.gst || 0),
        cash_received: parseFloat(challan.cash_received || 0),
        cash_paid: parseFloat(challan.cash_payment || 0),
    };
};

const removeRow = (index) => {
    form.details.splice(index, 1);
    syncTotals();
};

const addChallan = () => {
    if (!fetch_challan_id.value) return false;

    const existing = form.details.find((d) => d.challan_id === fetch_challan_id.value);
    if (existing) {
        alert('This challan is already added.');
        fetch_challan_id.value = '';
        return false;
    }

    const challan = props.challans.find((c) => c.id === fetch_challan_id.value);
    if (challan) {
        form.details.push(challanToDetailRow(challan));
        syncTotals();
        return true;
    }
    return false;
};

const onFetchChallanChange = () => {
    if (!fetch_challan_id.value) return;

    const success = addChallan();
    if (success) {
        setTimeout(() => {
            if (confirm('Do you want to add another challan?')) {
                fetch_challan_id.value = '';
                setTimeout(() => {
                    const triggerInput = fetchChallanSelect.value?.$el?.querySelector('input.searchable-select__trigger');
                    if (triggerInput) {
                        triggerInput.click();
                    }
                }, 50);
            } else {
                fetch_challan_id.value = '';
            }
        }, 100);
    }
};

const sumDetailField = (field) =>
    form.details.reduce((sum, item) => sum + (parseFloat(item[field]) || 0), 0);

const totalPackages = computed(() => sumDetailField('nugs'));
const totalWeight = computed(() => sumDetailField('weight'));
const toPayAmount = computed(() => sumDetailField('to_pay'));
const paidAmount = computed(() => sumDetailField('paid_amount'));
const crossingFreight = computed(() => sumDetailField('crossing'));
const cartageAmount = computed(() => sumDetailField('cartage'));
const labourTotal = computed(() => sumDetailField('labour'));
const katTotal = computed(() => sumDetailField('kat'));
const homeDeliveryTotal = computed(() => sumDetailField('home_delivery'));
const truckFreightTotal = computed(() => sumDetailField('truck_freight'));
const gstTotal = computed(() => sumDetailField('gst'));
const cashReceivedTotal = computed(() => sumDetailField('cash_received'));
const cashPaidTotal = computed(() => sumDetailField('cash_paid'));

const confirmTotals = computed(() => ({
    totalPackages: totalPackages.value,
    totalWeight: totalWeight.value,
    toPayAmount: toPayAmount.value,
    crossingFreight: crossingFreight.value,
}));

const labourDisplay = computed(() => labourTotal.value.toFixed(2));
const katDisplay = computed(() => katTotal.value.toFixed(2));
const homeDeliveryDisplay = computed(() => homeDeliveryTotal.value.toFixed(2));
const truckFreightDisplay = computed(() => truckFreightTotal.value.toFixed(2));
const gstDisplay = computed(() => gstTotal.value.toFixed(2));
const cashReceivedDisplay = computed(() => cashReceivedTotal.value.toFixed(2));
const cashPaidDisplay = computed(() => cashPaidTotal.value.toFixed(2));

const syncTotals = () => {
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
    form.crossing_freight = crossingFreight.value;
    form.cartage_dalali_pf = cartageAmount.value;
    form.labour_charge = labourTotal.value;
    form.kat = katTotal.value;
    form.home_delivery_charge = homeDeliveryTotal.value;
    form.truck_freight = truckFreightTotal.value;
    form.gst = gstTotal.value;
    form.cash_received = cashReceivedTotal.value;
    form.cash_paid = cashPaidTotal.value;
    calculateTotal();
};

watch(
    [totalPackages, totalWeight, toPayAmount, paidAmount, crossingFreight, cartageAmount, labourTotal, katTotal, homeDeliveryTotal, truckFreightTotal, gstTotal, cashReceivedTotal, cashPaidTotal],
    syncTotals
);

const calculateTotal = () => {
    const total =
        toPayAmount.value
        + crossingFreight.value
        - labourTotal.value
        - katTotal.value
        - homeDeliveryTotal.value
        - truckFreightTotal.value
        + cashPaidTotal.value
        - cashReceivedTotal.value;

    form.total_amount = total.toFixed(2);
    form.total_balance_type = total < 0 ? 'Cr' : 'Dr';
};

const submit = () => {
    openConfirm();
};

const confirmSave = () => {
    confirmAndRun(() => {
        syncTotals();
        form.total_amount = parseFloat(form.total_amount);
        form.put('/main-challans/' + props.main_challan.id);
    });
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

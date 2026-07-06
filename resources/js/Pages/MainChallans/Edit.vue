<template>
    <AdminLayout title="Edit Main Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card ops-form-card">
                    <div class="card-header">
                        <h3 class="card-title">Main Challan</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- Top Section: Core Details -->
                        <div class="card-body pt-3 pb-2 border-bottom">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Main Chal.No.</label>
                                    <div class="w-100">
                                        <input type="text" class="form-control form-control-sm" v-model="form.main_challan_number">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Date</label>
                                    <div class="w-100">
                                        <input type="date" class="form-control form-control-sm" v-model="form.date">
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-center mb-2">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">From St.</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.from_station_id" :options="stations" placeholder="Select Station" />
                                    </div>
                                    <div v-if="form.errors.from_station_id" class="text-danger small ms-2">{{ form.errors.from_station_id }}</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">To St.</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.to_station_id" :options="stations" placeholder="Select Station" />
                                    </div>
                                    <div v-if="form.errors.to_station_id" class="text-danger small ms-2">{{ form.errors.to_station_id }}</div>
                                </div>
                            </div>

                            <div class="row align-items-center mb-2">
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Driver</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.driver_id" :options="drivers" placeholder="Select Driver" />
                                    </div>
                                    <div v-if="form.errors.driver_id" class="text-danger small ms-2">{{ form.errors.driver_id }}</div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 85px;">Truck No.</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect :small="true" v-model="form.truck_id" :options="trucks" labelKey="truck_number" placeholder="Select Truck" />
                                    </div>
                                    <div v-if="form.errors.truck_id" class="text-danger small ms-2">{{ form.errors.truck_id }}</div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-primary" style="min-width: 85px;">Add Challan</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect
                                            ref="fetchChallanSelect"
                                            :small="true"
                                            v-model="fetch_challan_id"
                                            :options="challans"
                                            labelKey="challan_number"
                                            placeholder="Select Challan"
                                            open-on-enter
                                            @change="onFetchChallanChange"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mid Section: Details Grid -->
                        <div class="card-body p-0 border-bottom">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm m-0 table-grid">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="4%" class="text-center">Sr.No.</th>
                                            <th width="12%">Challan No.</th>
                                            <th width="15%">Station(To)</th>
                                            <th width="8%" class="text-center">Nugs</th>
                                            <th width="8%" class="text-center">Weight</th>
                                            <th width="10%" class="text-center">To Pay</th>
                                            <th width="10%" class="text-center">Paid/Adv</th>
                                            <th width="10%" class="text-center">Crossing</th>
                                            <th width="10%" class="text-center">Cartage</th>
                                            <th width="10%" class="text-center">Labour</th>
                                            <th width="3%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" class="bg-white">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td class="align-middle fw-semibold">{{ detail.challan_number || '--' }}</td>
                                            <td class="align-middle">{{ detail.to_station_name || '--' }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center bg-light" v-model="detail.nugs" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.weight" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.to_pay" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.paid_amount" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.crossing" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.cartage" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.labour" readonly>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 bg-transparent" @click="removeRow(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.details.length === 0">
                                            <td colspan="11" class="bg-light p-2 text-center text-muted">
                                                Select a challan above to add it to this Main Challan.
                                                <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Section: Totals -->
                        <div class="card-body pt-3 pb-2">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Total Weight</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ totalWeight }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Total Nugs</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ totalPackages }}</span></div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">To Pay Amount</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ toPayAmount }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Paid Amt/Adv.Rec.</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ paidAmount }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Crossing Freight</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ crossingFreight }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Cartage/Dalali/PF</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ cartageAmount }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">GST Tax</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ gstDisplay }}</span></div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Labour Commission</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ labourDisplay }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Kat</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ katDisplay }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Home Delivery</label></div>
                                        <div class="col-7"><span class="small fw-bold text-primary">{{ homeDeliveryDisplay }}</span></div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-5 text-end"><label class="mb-0 fw-bold small text-dark">Remark</label></div>
                                        <div class="col-7"><input type="text" class="form-control form-control-sm bg-white" v-model="form.remark"></div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Truck Freight</label></div>
                                        <div class="col-6"><span class="small fw-bold text-primary">{{ truckFreightDisplay }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Cash Receive</label></div>
                                        <div class="col-6"><span class="small fw-bold text-primary">{{ cashReceivedDisplay }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Cash Paid</label></div>
                                        <div class="col-6"><span class="small fw-bold text-primary">{{ cashPaidDisplay }}</span></div>
                                    </div>

                                    <hr class="my-2 border-secondary">

                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold">TOTAL</label></div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm bg-white fw-bold" :class="form.total_amount < 0 ? 'text-danger' : ''" :value="form.total_amount" readonly>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">Total Balance Amt.</label></div>
                                        <div class="col-6"><span class="small fw-bold text-primary">{{ form.total_balance_type }}</span></div>
                                    </div>
                                    <div class="row align-items-center mb-1">
                                        <div class="col-6 text-end"><label class="mb-0 fw-bold small text-dark">To (Account)</label></div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm bg-white" v-model="form.pay_by">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-footer bg-light d-flex justify-content-center gap-2 border-top-0 pt-3 pb-3">
                            <Link href="/main-challans" class="btn btn-outline-secondary px-4"><i class="fas fa-times me-1"></i> Exit</Link>
                            <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update
                            </button>
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
            @confirm-print="confirmSaveAndPrint"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MainChallanConfirmModal from '@/Components/FormConfirm/MainChallanConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch, onMounted } from 'vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

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

const getFetchChallanTrigger = () =>
    fetchChallanSelect.value?.$el?.querySelector('input.searchable-select__trigger');

const onFetchChallanChange = () => {
    if (!fetch_challan_id.value) return;

    const success = addChallan();
    if (success) {
        setTimeout(async () => {
            const yes = await askKeyboardConfirm('Do you want to add another challan?', {
                from: getFetchChallanTrigger(),
            });
            if (yes) {
                fetch_challan_id.value = '';
                setTimeout(() => {
                    const triggerInput = getFetchChallanTrigger();
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

const mainChallanPrintUrl = `/main-challans/${props.main_challan.id}/print`;

const submitMainChallan = (printAfterSave = false) => {
    confirmAndRun(() => {
        syncTotals();
        form.total_amount = parseFloat(form.total_amount);
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .put('/main-challans/' + props.main_challan.id, withPrintAfterSave(printAfterSave, mainChallanPrintUrl));
    });
};

const confirmSave = () => submitMainChallan(false);
const confirmSaveAndPrint = () => submitMainChallan(true);
</script>

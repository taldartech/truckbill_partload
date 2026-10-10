<template>
    <AdminLayout title="Add Main Challan">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Route & Vehicle -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Route &amp; Vehicle</h2>
                                <p class="bilty-section__desc">Stations, truck and driver</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Main Challan No.</span>
                                    <input ref="mainChallanNumberInput" type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.main_challan_number }" v-model="form.main_challan_number">
                                    <div v-if="form.errors.main_challan_number" class="text-danger small mt-1">{{ form.errors.main_challan_number }}</div>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date</span>
                                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.date }" v-model="form.date">
                                    <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">From Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.from_station_id"
                                    :options="stationOptions"
                                    master="station"
                                    placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                                <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{ form.errors.from_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">To Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.to_station_id"
                                    :options="stationOptions"
                                    master="station"
                                    placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                                <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{ form.errors.to_station_id }}</div>
                            </div>
                            
                            <div class="bilty-field">
                                <label class="bilty-field__label">Truck No.<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.truck_id"
                                    :options="truckOptions"
                                    master="truck"
                                    placeholder="Select Truck"
                                    @created="(record) => appendMasterOption(truckOptions, record)"
                                />
                                <div v-if="form.errors.truck_id" class="text-danger small mt-1">{{ form.errors.truck_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Driver<span class="bilty-field__req">*</span></label>
                                <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.driver_name }" v-model="form.driver_name" placeholder="Enter Driver Name">
                                <div v-if="form.errors.driver_name" class="text-danger small mt-1">{{ form.errors.driver_name }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Linked Challans -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Linked Challans</h2>
                                <p class="bilty-section__desc">Challans included in this main challan</p>
                            </div>
                        </div>
                        <div class="bilty-field bilty-section__head-right">
                            <div class="bilty-field">
                                <span class="bilty-id__label">Add Challan</span>
                                <SearchableSelect ref="fetchChallanSelect" :small="true" v-model="fetch_challan_id"
                                    :options="challans" labelKey="challan_number" placeholder="Select Challan"
                                    open-on-enter @change="onFetchChallanChange" />
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid" style="min-width: 700px;">
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
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.weight" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.to_pay" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.paid_amount" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.crossing" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.cartage" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control form-control-sm text-center bg-light" v-model="detail.labour" readonly>
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
                </section>

                <!-- 3. Charges & Total -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">3</div>
                            <div>
                                <h2 class="bilty-section__title">Charges &amp; Total</h2>
                                <p class="bilty-section__desc">Summary, charges and balance</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="bilty-charge-list">
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Total Weight</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalWeight" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Total Nugs</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalPackages" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">To Pay Amount</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="toPayAmount" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Paid Amt / Adv. Rec.</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="paidAmount" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Crossing Freight</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="crossingFreight" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cartage / Dalali / PF</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="cartageAmount" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">GST Tax</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="gstDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Remark</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.remark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="bilty-total-panel">
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Labour Commission</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="labourDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Kat</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="katDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Home Delivery</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="homeDeliveryDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Truck Freight</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="truckFreightDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cash Receive</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="cashReceivedDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cash Paid</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="cashPaidDisplay" readonly>
                                    </div>
                                    <div class="bilty-total">
                                        <span class="bilty-total__label">TOTAL</span>
                                        <input type="text" class="form-control form-control-sm bilty-total__input" :class="form.total_amount < 0 ? 'text-danger' : ''" :value="form.total_amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/main-challans" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times m-0 me-md-1"></i><span class="d-none d-sm-inline"> Cancel</span>
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save m-0 me-md-1"></i><span class="d-none d-sm-inline"> Save</span>
                    </button>
                </div>
            </form>
        </div>

        <MainChallanConfirmModal
            :show="showConfirm"
            :form="form"
            :stations="stationOptions"
            :trucks="truckOptions"
            :totals="confirmTotals"
            @cancel="closeConfirm"
            @confirm="confirmSave"
            @confirm-print="confirmSaveAndPrint"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import MainChallanConfirmModal from '@/Components/FormConfirm/MainChallanConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const fetch_challan_id = ref('');
const fetchChallanSelect = ref(null);
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const props = defineProps({
    stations: Array,
    trucks: Array,
    challans: Array,
    next_main_challan_number: String
});

const stationOptions = ref([...(props.stations || [])]);
const truckOptions = ref((props.trucks || []).map(t => ({ ...t, name: t.name || t.truck_number })));
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.trucks, (v) => { truckOptions.value = (v || []).map(t => ({ ...t, name: t.name || t.truck_number })); });


const form = useForm({
    main_challan_number: props.next_main_challan_number || '',
    date: new Date().toISOString().substr(0, 10),
    from_station_id: '',
    to_station_id: '',
    driver_name: '',
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
    remark: '',
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
                        triggerInput.focus();
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
};

const submit = () => {
    openConfirm();
};

const submitMainChallan = (printAfterSave = false) => {
    confirmAndRun(() => {
        syncTotals();
        form.total_amount = parseFloat(form.total_amount);
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .post('/main-challans', withPrintAfterSave(printAfterSave));
    });
};

const confirmSave = () => submitMainChallan(false);
const confirmSaveAndPrint = () => submitMainChallan(true);

const mainChallanNumberInput = ref(null);

onMounted(async () => {
    await nextTick();
    mainChallanNumberInput.value?.focus();
});
</script>

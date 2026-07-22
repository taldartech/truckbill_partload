<template>
    <AdminLayout title="Add Challan">
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
                                    <span class="bilty-id__label">Challan Number</span>
                                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.challan_number }" v-model="form.challan_number">
                                    <div v-if="form.errors.challan_number" class="text-danger small mt-1">{{ form.errors.challan_number }}</div>
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
                        <div class="bilty-form-grid bilty-form-grid--5">
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
                                <label class="bilty-field__label">Add For</label>
                                <SearchableSelect
                                    ref="fetchStationSelect"
                                    :small="true"
                                    v-model="fetch_station_id"
                                    :options="destinationStationOptions"
                                    placeholder="Select Station"
                                    open-on-enter
                                    @change="onFetchStationChange"
                                />
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

                <!-- 2. Loading Items -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Loading Items</h2>
                                <p class="bilty-section__desc">Bilties and inward entries on this challan</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%" class="text-center">Sr.No.</th>
                                        <th width="15%">GR/Bilty No.<span class="text-danger">*</span></th>
                                        <th width="14%">Station(To)</th>
                                        <th width="8%" class="text-center">Nugs</th>
                                        <th width="8%" class="text-center">Weight</th>
                                        <th width="14%">Goods Name</th>
                                        <th width="10%" class="text-center">Freight</th>
                                        <th width="10%" class="text-center">B.O.B.</th>
                                        <th width="10%" class="text-center">Type</th>
                                        <th width="6%" class="text-center"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(detail, index) in form.details"
                                        :key="index"
                                        :data-detail-index="index"
                                        :data-detail-row-type="detail.row_type"
                                        class="bg-white"
                                    >
                                        <td class="text-center align-middle">{{ index + 1 }}</td>
                                        <td>
                                            <template v-if="detail.row_type === 'bilty'">
                                                <SearchableSelect
                                                    v-if="!detail.bilty_id"
                                                    :small="true"
                                                    :open-on-focus="false"
                                                    @change="fetchBiltyDetails(index)"
                                                    v-model="detail.bilty_id"
                                                    :options="bilties"
                                                    labelKey="bilty_number"
                                                    placeholder="Select Bilty"
                                                />
                                                <span v-else class="align-middle fw-semibold">{{ detail.bilty_number || biltyLabel(detail.bilty_id) }}</span>
                                            </template>
                                            <template v-else>
                                                <SearchableSelect
                                                    v-if="!detail.inward_register_detail_id"
                                                    :small="true"
                                                    :open-on-focus="false"
                                                    @change="fetchInwardDetails(index)"
                                                    v-model="detail.inward_register_detail_id"
                                                    :options="inwardDetailOptions"
                                                    labelKey="gr_no"
                                                    placeholder="Select G.R."
                                                />
                                                <span v-else class="align-middle fw-semibold">{{ detail.gr_no || inwardLabel(detail.inward_register_detail_id) }}</span>
                                            </template>
                                        </td>
                                        <td class="align-middle">{{ detail.to_station_name || '--' }}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-center bg-light" v-model="detail.nugs" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.weight" readonly>
                                        </td>
                                        <td class="align-middle">{{ detail.goods_name || '--' }}</td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.freight" readonly>
                                        </td>
                                        <td class="align-middle text-center">{{ detail.bob || '--' }}</td>
                                        <td class="align-middle text-center">{{ detail.row_type === 'bilty' ? 'Booking' : 'Crossing' }}</td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 bg-transparent" @click="removeRow(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="10" class="bg-light p-2 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 me-1" data-dynamic-add data-dynamic-add-for="bilty" data-dynamic-add-message="Do you want to add another bilty?" @click="addBiltyRow">
                                                <i class="fas fa-plus"></i> Add Bilty
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-dynamic-add data-dynamic-add-for="inward" data-dynamic-add-message="Do you want to add another inward entry?" @click="addInwardRow">
                                                <i class="fas fa-plus"></i> Add Inward
                                            </button>
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
                                <p class="bilty-section__desc">Summary, charges, agents and balance</p>
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
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="toPayAmountDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Paid Amt / Adv. Rec.</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="paidAmountDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Crossing Freight</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="crossingFreightDisplay" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cartage / Dalali / PF</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="cartageDisplay" readonly>
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
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.labour_charge" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Kat</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.kat" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Home Delivery</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.home_delivery_charge" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Truck Freight</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.truck_freight" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cash Receive</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.cash_received" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Cash Paid</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.cash_payment" @input="calculateTotal">
                                    </div>
                                    <div class="bilty-total">
                                        <span class="bilty-total__label">TOTAL</span>
                                        <input type="text" class="form-control form-control-sm bilty-total__input" :class="form.total_amount < 0 ? 'text-danger' : ''" :value="form.total_amount" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Total Balance Amt.</label>
                                        <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="form.total_balance_type" readonly>
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">To (Account)</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.pay_by_agent_id"
                                            :options="agentOptions"
                                            master="agent"
                                            placeholder="Select Agent"
                                            @created="(record) => appendMasterOption(agentOptions, record)"
                                        />
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Delivery At</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.delivery_at_agent_id"
                                            :options="agentOptions"
                                            master="agent"
                                            placeholder="Select Agent"
                                            @created="(record) => appendMasterOption(agentOptions, record)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/challans" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>

        <ChallanConfirmModal
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
import ChallanConfirmModal from '@/Components/FormConfirm/ChallanConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const fetch_station_id = ref('');
const fetchStationSelect = ref(null);
let skipAddMorePrompt = false;
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const ALL_STATIONS_VALUE = 'all';

const props = defineProps({
    stations: Array,
    trucks: Array,
    bilties: Array,
    inward_details: Array,
    agents: Array,
    next_challan_number: String,
});

const stationOptions = ref([...(props.stations || [])]);
const truckOptions = ref((props.trucks || []).map(t => ({ ...t, name: t.name || t.truck_number })));
const agentOptions = ref([...(props.agents || [])]);
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.trucks, (v) => { truckOptions.value = (v || []).map(t => ({ ...t, name: t.name || t.truck_number })); });
watch(() => props.agents, (v) => { agentOptions.value = [...(v || [])]; });


const destinationStationOptions = computed(() => [
    { id: ALL_STATIONS_VALUE, name: 'ALL' },
    ...stationOptions.value,
]);

const inwardDetailOptions = computed(() =>
    (props.inward_details || []).map((detail) => ({
        ...detail,
        gr_no: detail.gr_no || `GR-${detail.id}`,
    }))
);

const emptyBiltyRow = () => ({
    row_type: 'bilty',
    bilty_id: '',
    bilty_number: '',
    inward_register_detail_id: '',
    gr_no: '',
    to_station_name: '',
    nugs: 0,
    weight: 0,
    goods_name: '',
    freight: 0,
    cartage: 0,
    gst_amount: 0,
    bob: '',
});

const emptyInwardRow = () => ({
    row_type: 'inward',
    bilty_id: '',
    bilty_number: '',
    inward_register_detail_id: '',
    gr_no: '',
    to_station_name: '',
    nugs: 0,
    weight: 0,
    goods_name: '',
    freight: 0,
    cartage: 0,
    gst_amount: 0,
    bob: '',
});

const biltyGstAmount = (bilty) => {
    const addonTotal = (bilty.addon_charges || []).reduce(
        (sum, charge) => sum + parseFloat(charge.amount || 0),
        0
    );

    const subtotal =
        parseFloat(bilty.freight || 0) +
        parseFloat(bilty.bilty_charge || 0) +
        parseFloat(bilty.surcharge || 0) +
        parseFloat(bilty.handling_charge || 0) +
        parseFloat(bilty.risk_dd_charge || 0) +
        parseFloat(bilty.cartage || 0) +
        addonTotal -
        parseFloat(bilty.less_advance_received || 0);

    const gstRate = bilty.gst_liability_by === 'Exempted' ? 0 : parseFloat(bilty.gst || 0);

    return subtotal * (gstRate / 100);
};

const form = useForm({
    challan_number: props.next_challan_number || '',
    date: new Date().toISOString().substr(0, 10),
    from_station_id: '',
    to_station_id: '',
    driver_name: '',
    truck_id: '',
    details: [emptyBiltyRow()],
    truck_freight: 0,
    labour_charge: 0,
    kat: 0,
    home_delivery_charge: 0,
    crossing_freight: 0,
    booking_freight: 0,
    cartage_dalali_pf: 0,
    other_charge: 0,
    gst: 0,
    paid_amount: 0,
    to_pay_amount: 0,
    cash_received: 0,
    cash_payment: 0,
    total_amount: 0,
    total_weight: 0,
    total_packages: 0,
    total_balance_type: 'Cr',
    pay_by_agent_id: '',
    delivery_at_agent_id: '',
    add_to_main_challan: false,
    remark: '',
    status: 'Created',
});

const addBiltyRow = () => {
    form.details.push(emptyBiltyRow());
};

const addInwardRow = () => {
    form.details.push(emptyInwardRow());
};

const removeRow = (index) => {
    form.details.splice(index, 1);
    if (form.details.length === 0) {
        form.details.push(emptyBiltyRow());
    }
    syncFreightTotals();
};

const biltyLabel = (id) => props.bilties.find((b) => b.id === id)?.bilty_number ?? '--';
const inwardLabel = (id) => inwardDetailOptions.value.find((d) => d.id === id)?.gr_no ?? '--';

const getDetailGrid = () => document.querySelector('.table-grid');

const getDetailRowElement = (index) =>
    document.querySelector(`tr[data-detail-index="${index}"]`);

const getRowFocusAnchor = (index) => {
    const row = getDetailRowElement(index);
    if (!row) return null;
    return row.querySelector('input.searchable-select__trigger') || row.querySelector('input') || row;
};

const focusDetailRowSelect = (index) => {
    nextTick(() => {
        getDetailRowElement(index)?.querySelector('input.searchable-select__trigger')?.focus();
    });
};

const promptAddBiltyThenInward = (focusFrom = null) => {
    setTimeout(async () => {
        const skipContainer = getDetailGrid();
        const from = focusFrom ?? getFetchStationTrigger() ?? document.activeElement;

        const yesBilty = await askKeyboardConfirm('Do you want to add another bilty?', {
            from,
            skipContainer,
        });
        if (yesBilty) {
            addBiltyRow();
            focusDetailRowSelect(form.details.length - 1);
        }

        const inwardFrom = getRowFocusAnchor(form.details.length - 1) ?? from;
        const yesInward = await askKeyboardConfirm('Do you want to add another inward entry?', {
            from: inwardFrom,
            skipContainer,
        });
        if (yesInward) {
            addInwardRow();
            focusDetailRowSelect(form.details.length - 1);
        }
    }, 100);
};

const populateBiltyRow = (detail, bilty) => {
    let totalNugs = 0;
    let totalWeight = 0;
    let goodName = '--';

    if (bilty.bilty_details && bilty.bilty_details.length > 0) {
        bilty.bilty_details.forEach((bd) => {
            totalNugs += parseFloat(bd.no_of_packages || 0);
            totalWeight += parseFloat(bd.gross_weight || 0);
        });
        goodName = bilty.bilty_details[0].good ? bilty.bilty_details[0].good.name : '--';
    }

    const toStation = stationOptions.value.find((s) => s.id === bilty.to_station_id);

    detail.row_type = 'bilty';
    detail.bilty_id = bilty.id;
    detail.bilty_number = bilty.bilty_number;
    detail.inward_register_detail_id = '';
    detail.to_station_name = toStation ? toStation.name : '--';
    detail.nugs = totalNugs;
    detail.weight = totalWeight;
    detail.goods_name = goodName;
    detail.freight = bilty.freight || 0;
    detail.cartage = parseFloat(bilty.cartage || 0);
    detail.gst_amount = biltyGstAmount(bilty);
    detail.bob = bilty.payment_type || '--';
};

const populateInwardRow = (detail, inward) => {
    detail.row_type = 'inward';
    detail.bilty_id = '';
    detail.inward_register_detail_id = inward.id;
    detail.gr_no = inward.gr_no || `GR-${inward.id}`;
    detail.to_station_name = inward.to_station?.name || stationOptions.value.find((s) => s.id === inward.to_station_id)?.name || '--';
    detail.nugs = inward.no_of_packages || 0;
    detail.weight = inward.weight || 0;
    detail.goods_name = inward.good?.name || '--';
    detail.freight = inward.freight || 0;
    detail.bob = inward.basis_of_booking || '--';
};

const fetchBiltyDetails = (index) => {
    const detail = form.details[index];
    if (detail.bilty_id) {
        const bilty = props.bilties.find((b) => b.id === detail.bilty_id);
        if (bilty) {
            populateBiltyRow(detail, bilty);
            syncFreightTotals();
            if (!skipAddMorePrompt) {
                promptAddBiltyThenInward(getRowFocusAnchor(index));
            }
        }
    } else {
        Object.assign(detail, emptyBiltyRow());
        syncFreightTotals();
    }
};

const fetchInwardDetails = (index) => {
    const detail = form.details[index];
    if (detail.inward_register_detail_id) {
        const inward = inwardDetailOptions.value.find((d) => d.id === detail.inward_register_detail_id);
        if (inward) {
            populateInwardRow(detail, inward);
            syncFreightTotals();
            if (!skipAddMorePrompt) {
                promptAddBiltyThenInward(getRowFocusAnchor(index));
            }
        }
    } else {
        Object.assign(detail, emptyInwardRow());
        syncFreightTotals();
    }
};

const fetchByStation = () => {
    if (!fetch_station_id.value) return false;

    const stationId = fetch_station_id.value;
    const isAll = stationId === ALL_STATIONS_VALUE;

    const existingBiltyIds = form.details
        .filter((d) => d.row_type === 'bilty' && d.bilty_id)
        .map((d) => d.bilty_id);

    const existingInwardIds = form.details
        .filter((d) => d.row_type === 'inward' && d.inward_register_detail_id)
        .map((d) => d.inward_register_detail_id);

    const matchingBilties = isAll
        ? props.bilties
        : props.bilties.filter((b) => b.to_station_id === stationId);

    const matchingInward = isAll
        ? inwardDetailOptions.value
        : inwardDetailOptions.value.filter((d) => d.to_station_id === stationId);

    if (matchingBilties.length === 0 && matchingInward.length === 0) {
        alert(isAll
            ? 'No pending bilties or inward entries found.'
            : 'No pending bilties or inward entries found for this station.');
        fetch_station_id.value = '';
        return false;
    }

    const biltiesToAdd = matchingBilties.filter((b) => !existingBiltyIds.includes(b.id));
    const inwardToAdd = matchingInward.filter((d) => !existingInwardIds.includes(d.id));

    if (biltiesToAdd.length === 0 && inwardToAdd.length === 0) {
        alert('All pending entries for this selection are already in the grid.');
        fetch_station_id.value = '';
        if (form.details.length === 0) addBiltyRow();
        return false;
    }

    if (form.details.length === 1 && !form.details[0].bilty_id && !form.details[0].inward_register_detail_id) {
        form.details.splice(0, 1);
    }

    skipAddMorePrompt = true;

    biltiesToAdd.forEach((bilty) => {
        const row = emptyBiltyRow();
        populateBiltyRow(row, bilty);
        form.details.push(row);
    });

    inwardToAdd.forEach((inward) => {
        const row = emptyInwardRow();
        populateInwardRow(row, inward);
        form.details.push(row);
    });

    skipAddMorePrompt = false;
    syncFreightTotals();
    return true;
};

const getFetchStationTrigger = () =>
    fetchStationSelect.value?.$el?.querySelector('input.searchable-select__trigger');

const onFetchStationChange = () => {
    if (!fetch_station_id.value) return;

    const success = fetchByStation();
    if (success) {
        fetch_station_id.value = '';

        nextTick(() => {
            fetchStationSelect.value?.focusTrigger?.();
        });

        promptAddBiltyThenInward(getFetchStationTrigger());
    }
};

const totalPackages = computed(() =>
    form.details.reduce((sum, item) => sum + (parseFloat(item.nugs) || 0), 0)
);

const totalWeight = computed(() =>
    form.details.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0)
);

const bookingFreightTotal = computed(() =>
    form.details
        .filter((item) => item.row_type === 'bilty')
        .reduce((sum, item) => sum + (parseFloat(item.freight) || 0), 0)
);

const toPayAmount = computed(() =>
    form.details.reduce((sum, item) => {
        if (item.row_type === 'bilty' && item.bob === 'To Pay') {
            return sum + (parseFloat(item.freight) || 0);
        }
        return sum;
    }, 0)
);

const crossingFreightTotal = computed(() =>
    form.details
        .filter((item) => item.row_type === 'inward')
        .reduce((sum, item) => sum + (parseFloat(item.freight) || 0), 0)
);

const paidAmount = computed(() =>
    form.details.reduce((sum, item) => {
        if (item.row_type === 'bilty' && (item.bob === 'Paid' || item.bob === 'TBB')) {
            return sum + (parseFloat(item.freight) || 0);
        }
        return sum;
    }, 0)
);

const cartageTotal = computed(() =>
    form.details
        .filter((item) => item.row_type === 'bilty')
        .reduce((sum, item) => sum + (parseFloat(item.cartage) || 0), 0)
);

const gstTotal = computed(() =>
    form.details
        .filter((item) => item.row_type === 'bilty')
        .reduce((sum, item) => sum + (parseFloat(item.gst_amount) || 0), 0)
);

const toPayAmountDisplay = computed(() => toPayAmount.value.toFixed(2));
const crossingFreightDisplay = computed(() => crossingFreightTotal.value.toFixed(2));
const paidAmountDisplay = computed(() => paidAmount.value.toFixed(2));
const cartageDisplay = computed(() => cartageTotal.value.toFixed(2));
const gstDisplay = computed(() => gstTotal.value.toFixed(2));

const confirmTotals = computed(() => ({
    totalPackages: totalPackages.value,
    totalWeight: totalWeight.value,
    toPayAmount: toPayAmount.value,
    crossingFreight: crossingFreightTotal.value,
}));

const syncFreightTotals = () => {
    form.booking_freight = bookingFreightTotal.value;
    form.to_pay_amount = toPayAmount.value;
    form.crossing_freight = crossingFreightTotal.value;
    form.cartage_dalali_pf = cartageTotal.value;
    form.gst = gstTotal.value;
    calculateTotal();
};

watch([totalPackages, totalWeight, toPayAmount, paidAmount, bookingFreightTotal, crossingFreightTotal, cartageTotal, gstTotal], () => {
    form.total_packages = totalPackages.value;
    form.total_weight = totalWeight.value;
    form.to_pay_amount = toPayAmount.value;
    form.paid_amount = paidAmount.value;
    form.booking_freight = bookingFreightTotal.value;
    form.crossing_freight = crossingFreightTotal.value;
    form.cartage_dalali_pf = cartageTotal.value;
    form.gst = gstTotal.value;
    calculateTotal();
});

const calculateTotal = () => {
    const total =
        toPayAmount.value
        + crossingFreightTotal.value
        - parseFloat(form.labour_charge || 0)
        - parseFloat(form.kat || 0)
        - parseFloat(form.home_delivery_charge || 0)
        - parseFloat(form.truck_freight || 0)
        + parseFloat(form.cash_payment || 0)
        - parseFloat(form.cash_received || 0);

    form.total_amount = total.toFixed(2);
    form.total_balance_type = total < 0 ? 'Cr' : 'Dr';
};

const submit = () => {
    openConfirm();
};

const challanPayload = (printAfterSave) => (data) => withPrintFlag({
    ...data,
    details: data.details
        .filter((row) => row.bilty_id || row.inward_register_detail_id)
        .map((row) => ({
            row_type: row.row_type,
            bilty_id: row.row_type === 'bilty' ? row.bilty_id : null,
            inward_register_detail_id: row.row_type === 'inward' ? row.inward_register_detail_id : null,
        })),
}, printAfterSave);

const submitChallan = (printAfterSave = false) => {
    confirmAndRun(() => {
        form.transform(challanPayload(printAfterSave))
            .post('/challans', withPrintAfterSave(printAfterSave));
    });
};

const confirmSave = () => submitChallan(false);
const confirmSaveAndPrint = () => submitChallan(true);
</script>

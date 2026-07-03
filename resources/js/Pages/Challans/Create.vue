<template>
    <AdminLayout title="Add Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card ops-form-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">CHALLAN Add/New</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <!-- HEADER ROW 1 -->
                        <div class="card-body pt-3 pb-2 border-bottom bg-light">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-3 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark text-nowrap" style="min-width: 90px;">Challan No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.challan_number">
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
                                    <label class="me-2 mb-0 fw-bold text-primary text-nowrap" style="min-width: 90px;">Add For</label>
                                    <div class="flex-grow-1">
                                        <SearchableSelect
                                            ref="fetchStationSelect"
                                            :small="true"
                                            v-model="fetch_station_id"
                                            :options="destinationStationOptions"
                                            placeholder="Station Wise"
                                            @change="onFetchStationChange"
                                        />
                                    </div>
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
                                <table class="table table-bordered table-sm m-0 table-grid ops-form-table">
                                    <thead>
                                        <tr>
                                            <th width="4%" class="text-center">Sr.No.</th>
                                            <th width="15%">GR/Bilty No.<span class="text-danger">*</span></th>
                                            <th width="15%">Station(To)</th>
                                            <th width="8%" class="text-center">Nugs</th>
                                            <th width="8%" class="text-center">Weight</th>
                                            <th width="15%">Goods Name</th>
                                            <th width="10%" class="text-end">Freight</th>
                                            <th width="10%" class="text-center">B.O.B.</th>
                                            <th width="10%" class="text-center">Type</th>
                                            <th width="5%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" :class="index % 2 === 0 ? 'bg-light' : 'bg-white'">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td>
                                                <SearchableSelect
                                                    v-if="detail.row_type === 'bilty'"
                                                    :small="true"
                                                    @change="fetchBiltyDetails(index)"
                                                    v-model="detail.bilty_id"
                                                    :options="bilties"
                                                    labelKey="bilty_number"
                                                    placeholder="Select Bilty"
                                                />
                                                <SearchableSelect
                                                    v-else
                                                    :small="true"
                                                    @change="fetchInwardDetails(index)"
                                                    v-model="detail.inward_register_detail_id"
                                                    :options="inwardDetailOptions"
                                                    labelKey="gr_no"
                                                    placeholder="Select G.R."
                                                />
                                            </td>
                                            <td class="align-middle">{{ detail.to_station_name || '--' }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center" v-model="detail.nugs" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center" v-model="detail.weight" readonly>
                                            </td>
                                            <td class="align-middle">{{ detail.goods_name || '--' }}</td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" v-model="detail.freight" readonly>
                                            </td>
                                            <td class="align-middle text-center">{{ detail.bob || '--' }}</td>
                                            <td class="align-middle text-center">{{ detail.row_type === 'bilty' ? 'Booking' : 'Crossing' }}</td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeRow(index)" v-if="form.details.length > 1">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="bg-white p-1 text-center border-top">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 me-1" @click="addBiltyRow">
                                                    <i class="fas fa-plus"></i> Add Bilty
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info py-0" @click="addInwardRow">
                                                    <i class="fas fa-plus"></i> Add Inward
                                                </button>
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
                                                <span class="fw-bold text-primary">{{ toPayAmountDisplay }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <label class="mb-0 fw-bold small text-dark">Paid Amt/Adv.Rec.</label>
                                                <span class="fw-bold text-primary">{{ paidAmountDisplay }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark">Crossing Freight</label>
                                                <span class="fw-bold text-primary">{{ crossingFreightDisplay }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-nowrap">Cartage/Dalali/PF</label>
                                                <span class="fw-bold text-primary">{{ cartageDisplay }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <label class="mb-0 fw-bold small text-dark text-nowrap">GST Tax</label>
                                                <span class="fw-bold text-primary">{{ gstDisplay }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="border-dark my-1">
                                </div>

                                <!-- Right side Financials -->
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-1 justify-content-between">
                                        <label class="mb-0 fw-bold small text-dark me-2" style="width: 120px;">Labour Comm.</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.labour_charge" @input="calculateTotal">
                                    </div>
                                    <div class="d-flex align-items-center mb-1 justify-content-between">
                                        <label class="mb-0 fw-bold small text-dark me-2" style="width: 120px;">Kat</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.kat" @input="calculateTotal">
                                    </div>
                                    <div class="d-flex align-items-center mb-1 justify-content-between">
                                        <label class="mb-0 fw-bold small text-dark me-2" style="width: 120px;">Home Delivery</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm w-25 p-1" v-model="form.home_delivery_charge" @input="calculateTotal">
                                    </div>
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
                                        <span class="fw-bold text-primary">{{ form.total_balance_type }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">To (Account)</label>
                                        <div class="w-50">
                                            <SearchableSelect
                                                :small="true"
                                                v-model="form.pay_by_agent_id"
                                                :options="agents"
                                                placeholder="Select Agent"
                                            />
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="mb-0 fw-bold small text-dark">Delivery At</label>
                                        <div class="w-50">
                                            <SearchableSelect
                                                :small="true"
                                                v-model="form.delivery_at_agent_id"
                                                :options="agents"
                                                placeholder="Select Agent"
                                            />
                                        </div>
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
                            <button type="button" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm" onclick="window.print()">
                                Print
                            </button>
                            <button type="button" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm" @click="form.reset()">
                                Clear
                            </button>
                            <Link href="/challans" class="btn btn-sm btn-form-action px-4 fw-bold shadow-sm">
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

const fetch_station_id = ref('');
const fetchStationSelect = ref(null);

const ALL_STATIONS_VALUE = 'all';

const props = defineProps({
    stations: Array,
    drivers: Array,
    trucks: Array,
    bilties: Array,
    inward_details: Array,
    agents: Array,
    next_challan_number: String,
});

const destinationStationOptions = computed(() => [
    { id: ALL_STATIONS_VALUE, name: 'ALL' },
    ...props.stations,
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
    inward_register_detail_id: '',
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
    inward_register_detail_id: '',
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
        parseFloat(bilty.other_charge || 0) +
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
    driver_id: '',
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
    if (form.details.length > 1) {
        form.details.splice(index, 1);
        syncFreightTotals();
    }
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

    const toStation = props.stations.find((s) => s.id === bilty.to_station_id);

    detail.row_type = 'bilty';
    detail.bilty_id = bilty.id;
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
    detail.to_station_name = inward.to_station?.name || props.stations.find((s) => s.id === inward.to_station_id)?.name || '--';
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

    syncFreightTotals();
    return true;
};

const onFetchStationChange = () => {
    if (!fetch_station_id.value) return;

    const success = fetchByStation();
    if (success) {
        setTimeout(() => {
            if (confirm('Do you want to add bilties/inward for another station?')) {
                fetch_station_id.value = '';
                setTimeout(() => {
                    const triggerInput = fetchStationSelect.value?.$el?.querySelector('input.searchable-select__trigger');
                    if (triggerInput) {
                        triggerInput.click();
                    }
                }, 50);
            } else {
                fetch_station_id.value = '';
            }
        }, 100);
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
    form.transform((data) => ({
        ...data,
        details: data.details
            .filter((row) => row.bilty_id || row.inward_register_detail_id)
            .map((row) => ({
                row_type: row.row_type,
                bilty_id: row.row_type === 'bilty' ? row.bilty_id : null,
                inward_register_detail_id: row.row_type === 'inward' ? row.inward_register_detail_id : null,
            })),
    })).post('/challans');
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
<template>
    <AdminLayout title="Edit Inward Register">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Register & Route -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Register &amp; Route</h2>
                                <p class="bilty-section__desc">Inward register and transport details</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Inward No.</span>
                                    <input type="text" class="form-control form-control-sm" v-model="form.inward_register_no">
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date<span class="bilty-field__req">*</span></span>
                                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.date }" v-model="form.date">
                                    <div v-if="form.errors.date" class="text-danger small mt-1">{{ form.errors.date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">From Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.from_station_id" :options="stationOptions" master="station" placeholder="Select Station" @created="(record) => appendMasterOption(stationOptions, record)" />
                                <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{ form.errors.from_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">To Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect :small="true" v-model="form.to_station_id" :options="stationOptions" master="station" placeholder="Select Station" @created="(record) => appendMasterOption(stationOptions, record)" />
                                <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{ form.errors.to_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Challan No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.challan_number">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Challan Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.challan_date">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Truck No.</label>
                                <SearchableSelect :small="true" v-model="form.truck_id" :options="truckOptions" master="truck" placeholder="Select Truck" @created="(record) => appendMasterOption(truckOptions, record)" />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Driver</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.driver_name" placeholder="Enter Driver Name">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Add Entry -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Add Entry</h2>
                                <p class="bilty-section__desc">Goods receipt, route, parties and freight</p>
                            </div>
                        </div>
                        <div v-if="editingRowIndex !== null" class="bilty-section__head-right">
                            <span class="badge bg-warning text-dark">Editing row {{ editingRowIndex + 1 }}</span>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <p class="small text-uppercase fw-bold text-muted mb-2">Goods Receipt</p>
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">G.R. No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="entry.gr_no" placeholder="Enter G.R. number">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="entry.date">
                            </div>
                        </div>

                        <p class="small text-uppercase fw-bold text-muted mb-2 mt-3">Route &amp; Parties</p>
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">From Station</label>
                                <SearchableSelect :small="true" v-model="entry.from_station_id" :options="stationOptions" master="station" placeholder="Select Station" @created="(record) => appendMasterOption(stationOptions, record)" />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">To Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="entry.to_station_id"
                                    :options="stationOptions"
                                    master="station"
                                    placeholder="Select Station"
                                    :disabled="entry.status === 'challaned'"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                                <span v-if="entry.to_station_id" class="badge mt-1" :class="dispositionBadgeClass(rowDisposition(entry))">
                                    {{ formatDisposition(rowDisposition(entry)) }}
                                </span>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Consignor</label>
                                <SearchableSelect :small="true" v-model="entry.consignor_id" :options="partyOptions" master="party" placeholder="Select Consignor" @created="(record) => appendMasterOption(partyOptions, record)" />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Consignee</label>
                                <SearchableSelect :small="true" v-model="entry.consignee_id" :options="partyOptions" master="party" placeholder="Select Consignee" @created="(record) => appendMasterOption(partyOptions, record)" />
                            </div>
                        </div>

                        <p class="small text-uppercase fw-bold text-muted mb-2 mt-3">Package &amp; Freight</p>
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Goods Name</label>
                                <SearchableSelect :small="true" v-model="entry.good_id" :options="goodOptions" master="good" placeholder="Select Goods" @created="(record) => appendMasterOption(goodOptions, record)" />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">No. of Pkg.</label>
                                <input type="number" class="form-control form-control-sm" v-model="entry.no_of_packages" placeholder="0">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Weight (Kg)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" v-model="entry.weight" placeholder="0.00">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Basis of Booking</label>
                                <select class="form-select form-select-sm" v-model="entry.basis_of_booking">
                                    <option value="">Select basis</option>
                                    <option value="To Pay">To Pay</option>
                                    <option value="Paid">Paid</option>
                                    <option value="TBB">TBB</option>
                                    <option value="FOC">FOC</option>
                                </select>
                            </div>
                        </div>
                        <div class="bilty-form-grid bilty-form-grid--4 mt-0">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Freight</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" v-model="entry.freight" placeholder="0.00">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Home Delivery Charge</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" v-model="entry.home_delivery_charge" placeholder="0.00">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Private Marka</label>
                                <input type="text" class="form-control form-control-sm" v-model="entry.private_marka" placeholder="Enter marka">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Agent</label>
                                <SearchableSelect :small="true" v-model="entry.agent_id" :options="agentOptions" master="agent" placeholder="Select Agent" @created="(record) => appendMasterOption(agentOptions, record)" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 mt-2 border-top">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" @click="clearEntry">
                                <i class="fas fa-eraser me-1"></i> Clear Entry
                            </button>
                            <button type="button" class="btn btn-warning btn-sm px-3" @click="confirmEntry">
                                <i class="fas fa-plus me-1"></i>
                                {{ editingRowIndex !== null ? 'Update Entry' : 'Add to List' }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 3. Entries -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">3</div>
                            <div>
                                <h2 class="bilty-section__title">Entries</h2>
                                <p class="bilty-section__desc">{{ form.details.length }} entry(ies) added</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right" @click.stop>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="loadSelectedEntry" :disabled="selectedRowIndex === null">
                                    <i class="fas fa-edit me-1"></i> Edit Selected
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteSelectedRow" :disabled="selectedRowIndex === null">
                                    <i class="fas fa-trash me-1"></i> Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush" @click="deselectRow">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid entries-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" style="width: 5%;">Sr.</th>
                                        <th>G.R. No.</th>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Route</th>
                                        <th>Status</th>
                                        <th>Consignor</th>
                                        <th>Consignee</th>
                                        <th>Goods</th>
                                        <th class="text-center">Pkg.</th>
                                        <th class="text-center">Weight</th>
                                        <th class="text-center" style="width: 5%;"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(detail, index) in form.details"
                                        :key="index"
                                        :class="{ 'entry-row-selected': selectedRowIndex === index }"
                                        @click.stop="selectRow(index)"
                                    >
                                        <td class="text-center align-middle">{{ index + 1 }}</td>
                                        <td class="align-middle">{{ detail.gr_no || '—' }}</td>
                                        <td class="align-middle">{{ detail.date || '—' }}</td>
                                        <td class="align-middle">{{ stationName(detail.from_station_id) }}</td>
                                        <td class="align-middle">{{ stationName(detail.to_station_id) }}</td>
                                        <td class="align-middle">
                                            <span class="badge" :class="dispositionBadgeClass(rowDisposition(detail))">
                                                {{ formatDisposition(rowDisposition(detail)) }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge" :class="detail.status === 'challaned' ? 'bg-warning text-dark' : 'bg-secondary'">
                                                {{ detail.status === 'challaned' ? 'Challaned' : 'Pending' }}
                                            </span>
                                        </td>
                                        <td class="align-middle">{{ partyName(detail.consignor_id) }}</td>
                                        <td class="align-middle">{{ partyName(detail.consignee_id) }}</td>
                                        <td class="align-middle">{{ goodName(detail.good_id) }}</td>
                                        <td class="text-center align-middle">{{ detail.no_of_packages || 0 }}</td>
                                        <td class="text-center align-middle">{{ detail.weight || 0 }}</td>
                                        <td class="text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-link text-danger p-0 bg-transparent"
                                                @click.stop="deleteRow(index)"
                                                :disabled="detail.status === 'challaned'"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.details.length === 0">
                                        <td colspan="13" class="text-center py-4 text-muted">
                                            No entries added yet. Fill the form above and click Add to List.
                                            <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bilty-section__body pt-3">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Total Packages</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalPackages" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Total Delivery Freight</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalDeliveryFreight" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Total Crossing Freight</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalCrossingFreight" readonly>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/inward-registers" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-warning btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>

        <InwardRegisterConfirmModal
            :show="showConfirm"
            :form="form"
            :stations="stationOptions"
            :trucks="truckOptions"
            :totals="confirmTotals"
            @cancel="closeConfirm"
            @confirm="confirmSave"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import InwardRegisterConfirmModal from '@/Components/FormConfirm/InwardRegisterConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch } from 'vue';

const page = usePage();
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const props = defineProps({
    register: Object,
    stations: Array,
    trucks: Array,
    goods: Array,
    parties: Array,
    agents: Array,
});

const stationOptions = ref([...(props.stations || [])]);
const truckOptions = ref((props.trucks || []).map(t => ({ ...t, name: t.name || t.truck_number })));
const partyOptions = ref([...(props.parties || [])]);
const goodOptions = ref([...(props.goods || [])]);
const agentOptions = ref([...(props.agents || [])]);
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.trucks, (v) => { truckOptions.value = (v || []).map(t => ({ ...t, name: t.name || t.truck_number })); });
watch(() => props.parties, (v) => { partyOptions.value = [...(v || [])]; });
watch(() => props.goods, (v) => { goodOptions.value = [...(v || [])]; });
watch(() => props.agents, (v) => { agentOptions.value = [...(v || [])]; });


const selectedRowIndex = ref(null);
const editingRowIndex = ref(null);

const emptyEntry = () => ({
    gr_no: '',
    date: new Date().toISOString().substr(0, 10),
    from_station_id: '',
    to_station_id: '',
    consignor_id: '',
    consignee_id: '',
    good_id: '',
    no_of_packages: '',
    weight: '',
    basis_of_booking: '',
    freight: '',
    home_delivery_charge: '',
    private_marka: '',
    agent_id: '',
    disposition: '',
    status: 'pending',
});

const mapDetail = (detail) => ({
    id: detail.id ?? null,
    gr_no: detail.gr_no || '',
    date: detail.date ? String(detail.date).substr(0, 10) : '',
    from_station_id: detail.from_station_id || '',
    to_station_id: detail.to_station_id || '',
    consignor_id: detail.consignor_id || '',
    consignee_id: detail.consignee_id || '',
    good_id: detail.good_id || '',
    no_of_packages: detail.no_of_packages ?? '',
    weight: detail.weight ?? '',
    basis_of_booking: detail.basis_of_booking || '',
    freight: detail.freight ?? '',
    home_delivery_charge: detail.home_delivery_charge ?? '',
    private_marka: detail.private_marka || '',
    agent_id: detail.agent_id || '',
    disposition: detail.disposition || '',
    status: detail.status || 'pending',
});

const entry = ref(emptyEntry());

const initialDetails = (props.register.details || []).map(mapDetail);

const form = useForm({
    inward_register_no: props.register.inward_register_no || '',
    date: props.register.date ? String(props.register.date).substr(0, 10) : new Date().toISOString().substr(0, 10),
    from_station_id: props.register.from_station_id || '',
    to_station_id: props.register.to_station_id || '',
    challan_number: props.register.challan_number || '',
    challan_date: props.register.challan_date ? String(props.register.challan_date).substr(0, 10) : '',
    driver_name: props.register.driver_name || '',
    truck_id: props.register.truck_id || '',
    total_packages: props.register.total_packages || 0,
    total_delivery_freight: props.register.total_delivery_freight || 0,
    total_crossing_freight: props.register.total_crossing_freight || 0,
    details: initialDetails,
});

const branchStationId = computed(() => page.props.auth.active_branch?.station_id ?? null);

const rowDisposition = (detail) => {
    if (detail.disposition) return detail.disposition;
    if (!detail.to_station_id || !branchStationId.value) return null;
    return String(detail.to_station_id) === String(branchStationId.value) ? 'delivery' : 'forward';
};

const formatDisposition = (disposition) => {
    if (disposition === 'delivery') return 'Delivery';
    if (disposition === 'forward') return 'Forward';
    return '—';
};

const dispositionBadgeClass = (disposition) => {
    if (disposition === 'delivery') return 'bg-success';
    if (disposition === 'forward') return 'bg-info text-dark';
    return 'bg-secondary';
};

const stationName = (id) => stationOptions.value.find((s) => s.id === id)?.name || '—';
const partyName = (id) => partyOptions.value.find((p) => p.id === id)?.name || '—';
const goodName = (id) => goodOptions.value.find((g) => g.id === id)?.name || '—';

const totalPackages = computed(() => form.details.reduce((sum, row) => sum + (parseFloat(row.no_of_packages) || 0), 0));
const totalDeliveryFreight = computed(() => form.details
    .filter((row) => rowDisposition(row) === 'delivery')
    .reduce((sum, row) => sum + (parseFloat(row.freight) || 0), 0)
    .toFixed(2));
const totalCrossingFreight = computed(() => form.details
    .filter((row) => rowDisposition(row) === 'forward')
    .reduce((sum, row) => sum + (parseFloat(row.freight) || 0), 0)
    .toFixed(2));

const confirmTotals = computed(() => ({
    totalPackages: totalPackages.value,
    totalDeliveryFreight: totalDeliveryFreight.value,
    totalCrossingFreight: totalCrossingFreight.value,
}));

const clearEntry = () => {
    entry.value = emptyEntry();
    editingRowIndex.value = null;
};

const selectRow = (index) => {
    selectedRowIndex.value = selectedRowIndex.value === index ? null : index;
};

const deselectRow = () => {
    selectedRowIndex.value = null;
};

const loadSelectedEntry = () => {
    if (selectedRowIndex.value === null) {
        alert('Please select a row to edit.');
        return;
    }

    const selected = form.details[selectedRowIndex.value];
    if (selected.status === 'challaned') {
        alert('Challaned entries cannot be edited.');
        return;
    }

    entry.value = { ...selected };
    editingRowIndex.value = selectedRowIndex.value;
};

const confirmEntry = () => {
    if (!entry.value.gr_no && !entry.value.good_id) {
        alert('Please enter G.R. No. or select Goods Name.');
        return;
    }

    if (!entry.value.to_station_id) {
        alert('Please select To Station for this entry.');
        return;
    }

    const row = { ...entry.value };

    if (editingRowIndex.value !== null) {
        form.details[editingRowIndex.value] = row;
        editingRowIndex.value = null;
    } else {
        form.details.push(row);
    }

    clearEntry();
};

const deleteRow = (index) => {
    if (form.details[index]?.status === 'challaned') {
        alert('Challaned entries cannot be deleted.');
        return;
    }

    form.details.splice(index, 1);
    if (selectedRowIndex.value === index) {
        selectedRowIndex.value = null;
    } else if (selectedRowIndex.value !== null && selectedRowIndex.value > index) {
        selectedRowIndex.value -= 1;
    }
};

const deleteSelectedRow = () => {
    if (selectedRowIndex.value === null) return;
    deleteRow(selectedRowIndex.value);
};

const submit = () => {
    openConfirm();
};

const confirmSave = () => {
    confirmAndRun(() => {
        form.total_packages = totalPackages.value;
        form.total_delivery_freight = totalDeliveryFreight.value;
        form.total_crossing_freight = totalCrossingFreight.value;
        form.put(`/inward-registers/${props.register.id}`);
    });
};
</script>

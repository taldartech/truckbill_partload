<template>
    <AdminLayout title="Add Inward Register">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Create Inward Register</h3>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <h5 class="border-bottom pb-2 mb-3 text-primary">Register Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Inward No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.inward_register_no">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date">
                                    <div v-if="form.errors.date" class="text-danger small mt-1">{{ form.errors.date }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">From Station <span class="text-danger">*</span></label>
                                    <SearchableSelect :small="true" v-model="form.from_station_id" :options="stations" placeholder="Select Station" />
                                    <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{ form.errors.from_station_id }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">To Station <span class="text-danger">*</span></label>
                                    <SearchableSelect :small="true" v-model="form.to_station_id" :options="stations" placeholder="Select Station" />
                                    <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{ form.errors.to_station_id }}</div>
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mb-3 text-primary">Transport Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Challan No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.challan_number">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Challan Date</label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.challan_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Driver</label>
                                    <SearchableSelect :small="true" v-model="form.driver_id" :options="drivers" placeholder="Select Driver" />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Truck No.</label>
                                    <SearchableSelect :small="true" v-model="form.truck_id" :options="trucks" labelKey="truck_number" placeholder="Select Truck" />
                                </div>
                            </div>

                            <div class="card entry-form-card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-plus-circle me-2 text-primary"></i>Add Entry
                                    </h6>
                                    <span v-if="editingRowIndex !== null" class="badge bg-warning text-dark">
                                        Editing row {{ editingRowIndex + 1 }}
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    <p class="entry-form-section-title">Goods Receipt</p>
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label">G.R. No.</label>
                                            <input type="text" class="form-control" v-model="entry.gr_no" placeholder="Enter G.R. number">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" class="form-control" v-model="entry.date">
                                        </div>
                                    </div>

                                    <p class="entry-form-section-title">Route &amp; Parties</p>
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label">From Station</label>
                                            <SearchableSelect v-model="entry.from_station_id" :options="stations" placeholder="Select Station" />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">To Station</label>
                                            <SearchableSelect v-model="entry.to_station_id" :options="stations" placeholder="Select Station" />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Consignor</label>
                                            <SearchableSelect v-model="entry.consignor_id" :options="parties" placeholder="Select Consignor" />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Consignee</label>
                                            <SearchableSelect v-model="entry.consignee_id" :options="parties" placeholder="Select Consignee" />
                                        </div>
                                    </div>

                                    <p class="entry-form-section-title">Package &amp; Freight</p>
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Goods Name</label>
                                            <SearchableSelect v-model="entry.good_id" :options="goods" placeholder="Select Goods" />
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">No. of Pkg.</label>
                                            <input type="number" class="form-control" v-model="entry.no_of_packages" placeholder="0">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Weight (Kg)</label>
                                            <input type="number" step="0.01" class="form-control" v-model="entry.weight" placeholder="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Basis of Booking</label>
                                            <select class="form-select" v-model="entry.basis_of_booking">
                                                <option value="">Select basis</option>
                                                <option value="To Pay">To Pay</option>
                                                <option value="Paid">Paid</option>
                                                <option value="TBB">TBB</option>
                                                <option value="FOC">FOC</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-md-3">
                                            <label class="form-label">Freight</label>
                                            <input type="number" step="0.01" class="form-control" v-model="entry.freight" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Home Delivery Charge</label>
                                            <input type="number" step="0.01" class="form-control" v-model="entry.home_delivery_charge" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Private Marka</label>
                                            <input type="text" class="form-control" v-model="entry.private_marka" placeholder="Enter marka">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Transport Name</label>
                                            <SearchableSelect v-model="entry.transporter_id" :options="transporters" placeholder="Select Transporter" />
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-4 mt-2 border-top">
                                        <button type="button" class="btn btn-outline-secondary px-4" @click="clearEntry">
                                            <i class="fas fa-eraser me-1"></i> Clear Entry
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" @click="confirmEntry">
                                            <i class="fas fa-plus me-1"></i>
                                            {{ editingRowIndex !== null ? 'Update Entry' : 'Add to List' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 text-primary">Entries</h5>
                                <div class="d-flex gap-2" @click.stop>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="loadSelectedEntry" :disabled="selectedRowIndex === null">
                                        <i class="fas fa-edit me-1"></i> Edit Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteSelectedRow" :disabled="selectedRowIndex === null">
                                        <i class="fas fa-trash me-1"></i> Delete Selected
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive border rounded" @click="deselectRow">
                                <table class="table table-sm table-striped mb-0 entries-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 5%;">Sr.</th>
                                            <th>G.R. No.</th>
                                            <th>Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Consignor</th>
                                            <th>Consignee</th>
                                            <th>Goods</th>
                                            <th class="text-center">Pkg.</th>
                                            <th class="text-center">Weight</th>
                                            <th class="text-center" style="width: 5%;"></th>
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
                                            <td class="align-middle">{{ partyName(detail.consignor_id) }}</td>
                                            <td class="align-middle">{{ partyName(detail.consignee_id) }}</td>
                                            <td class="align-middle">{{ goodName(detail.good_id) }}</td>
                                            <td class="text-center align-middle">{{ detail.no_of_packages || 0 }}</td>
                                            <td class="text-center align-middle">{{ detail.weight || 0 }}</td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click.stop="deleteRow(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.details.length === 0">
                                            <td colspan="11" class="text-center py-4 text-muted">
                                                No entries added yet. Fill the form above and click Add to List.
                                                <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4 offset-md-8">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Total Packages</span>
                                        <strong>{{ totalPackages }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Total Delivery Freight</span>
                                        <strong>{{ totalDeliveryFreight }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Crossing Freight</span>
                                        <strong>{{ totalCrossingFreight }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/inward-registers" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save
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
import { computed, ref } from 'vue';

const props = defineProps({
    stations: Array,
    drivers: Array,
    trucks: Array,
    goods: Array,
    parties: Array,
    transporters: Array,
    next_inward_register_no: String,
});

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
    transporter_id: '',
});

const entry = ref(emptyEntry());

const form = useForm({
    inward_register_no: props.next_inward_register_no || '',
    date: new Date().toISOString().substr(0, 10),
    from_station_id: '',
    to_station_id: '',
    challan_number: '',
    challan_date: new Date().toISOString().substr(0, 10),
    driver_id: '',
    truck_id: '',
    total_packages: 0,
    total_delivery_freight: 0,
    total_crossing_freight: 0,
    details: [],
});

const stationName = (id) => props.stations.find((s) => s.id === id)?.name || '—';
const partyName = (id) => props.parties.find((p) => p.id === id)?.name || '—';
const goodName = (id) => props.goods.find((g) => g.id === id)?.name || '—';

const totalPackages = computed(() => form.details.reduce((sum, row) => sum + (parseFloat(row.no_of_packages) || 0), 0));
const totalDeliveryFreight = computed(() => form.details.reduce((sum, row) => sum + (parseFloat(row.home_delivery_charge) || 0), 0).toFixed(2));
const totalCrossingFreight = computed(() => form.details.reduce((sum, row) => sum + (parseFloat(row.freight) || 0), 0).toFixed(2));

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

    entry.value = { ...form.details[selectedRowIndex.value] };
    editingRowIndex.value = selectedRowIndex.value;
};

const confirmEntry = () => {
    if (!entry.value.gr_no && !entry.value.good_id) {
        alert('Please enter G.R. No. or select Goods Name.');
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
    form.total_packages = totalPackages.value;
    form.total_delivery_freight = totalDeliveryFreight.value;
    form.total_crossing_freight = totalCrossingFreight.value;
    form.post('/inward-registers');
};
</script>

<style scoped>
.entry-form-card {
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.entry-form-card .card-header {
    background-color: #f8f9fa;
}

.entry-form-section-title {
    color: #6c757d;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.entry-form-card .form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.entry-row-selected > td {
    background-color: #cfe2ff !important;
    border-top: 2px solid #0d6efd;
    border-bottom: 2px solid #0d6efd;
}

.entry-row-selected > td:first-child {
    border-left: 4px solid #0d6efd;
}

.entry-row-selected > td:last-child {
    border-right: 2px solid #0d6efd;
}

.entries-table tbody tr {
    cursor: pointer;
}

.entries-table tbody tr:not(.entry-row-selected):hover > td {
    background-color: #f1f3f5;
}
</style>

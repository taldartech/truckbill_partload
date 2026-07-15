<template>
    <AdminLayout title="Add Loading Report">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card ops-form-card">
                    <div class="card-header">
                        <h3 class="card-title">Create Loading Report</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <div class="card-body pt-3 pb-2 border-bottom">
                            <div class="row align-items-start mb-2">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Date<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" v-model="form.date">
                                    </div>
                                    <div v-if="form.errors.date" class="text-danger small mt-1 ps-5 ms-3">{{ form.errors.date }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Truck No.<span class="text-danger">*</span></label>
                                        <div class="flex-grow-1">
                                            <SearchableSelect
                                                :small="true"
                                                v-model="form.truck_id"
                                                :options="truckOptions"
                                                master="truck"
                                                placeholder="Select Truck"
                                                @created="(record) => appendMasterOption(truckOptions, record)"
                                            />
                                        </div>
                                    </div>
                                    <div v-if="form.errors.truck_id" class="text-danger small mt-1 ps-5 ms-3">{{ form.errors.truck_id }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Driver<span class="text-danger">*</span></label>
                                        <div class="flex-grow-1">
                                            <input type="text" class="form-control form-control-sm" v-model="form.driver_name" placeholder="Enter Driver Name" />
                                        </div>
                                    </div>
                                    <div v-if="form.errors.driver_name" class="text-danger small mt-1 ps-5 ms-3">{{ form.errors.driver_name }}</div>
                                </div>
                            </div>

                            <div class="row align-items-start mb-2">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">From St.<span class="text-danger">*</span></label>
                                        <div class="flex-grow-1">
                                            <SearchableSelect
                                                :small="true"
                                                v-model="form.from_station_id"
                                                :options="stationOptions"
                                                master="station"
                                                placeholder="Select Station"
                                                @created="(record) => appendMasterOption(stationOptions, record)"
                                            />
                                        </div>
                                    </div>
                                    <div v-if="form.errors.from_station_id" class="text-danger small mt-1 ps-5 ms-3">{{ form.errors.from_station_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">To St.<span class="text-danger">*</span></label>
                                        <div class="flex-grow-1">
                                            <SearchableSelect
                                                :small="true"
                                                v-model="form.to_station_id"
                                                :options="stationOptions"
                                                master="station"
                                                placeholder="Select Station"
                                                @created="(record) => appendMasterOption(stationOptions, record)"
                                            />
                                        </div>
                                    </div>
                                    <div v-if="form.errors.to_station_id" class="text-danger small mt-1 ps-5 ms-3">{{ form.errors.to_station_id }}</div>
                                </div>
                            </div>

                            <!-- Fetch Bilties by Destination Station -->
                            <div class="row align-items-center mt-3 mb-1">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-primary" style="min-width: 90px;">Add Bilties For:</label>
                                    <SearchableSelect
                                        ref="fetchStationSelect"
                                        :small="true"
                                        v-model="fetch_station_id"
                                        :options="destinationStationOptions"
                                        placeholder="Select Destination Station"
                                        open-on-enter
                                        @change="onFetchStationChange"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Grid Section: Loaded Bilties -->
                        <div class="card-body p-0 border-bottom">
                            <div class="table-responsive" style="min-height: 200px;">
                                <table class="table table-bordered table-sm m-0 table-grid" data-dynamic-add-message="Do you want to add another bilty?">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%" class="text-center">Sr.No.</th>
                                            <th width="20%">Bilty No.<span class="text-danger">*</span></th>
                                            <th width="15%">Consignee</th>
                                            <th width="20%">Goods</th>
                                            <th width="15%" class="text-center">Packages</th>
                                            <th width="15%" class="text-center">Weight(Kg)</th>
                                            <th width="10%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" class="bg-white">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td>
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
                                                <div v-if="form.errors['details.' + index + '.bilty_id']" class="text-danger small mt-1 text-start">{{ form.errors['details.' + index + '.bilty_id'] }}</div>
                                            </td>
                                            <td class="align-middle">
                                                {{ detail.consignee_name || '--' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ detail.goods_name || '--' }}
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center" v-model="detail.no_of_packages" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-center" v-model="detail.gross_weight" readonly>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 bg-transparent" @click="removeRow(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="bg-light p-1 text-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-dynamic-add data-dynamic-add-message="Do you want to add another bilty?" @click="addRow">
                                                    <i class="fas fa-plus"></i> Add Bilty
                                                </button>
                                                <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Section -->
                        <div class="card-body pt-3 pb-2">
                            <div class="row align-items-center mb-1">
                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark">Total Packages</label></div>
                                <div class="col-md-2"><input type="number" class="form-control form-control-sm" :value="totalPackages" readonly></div>

                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark">Total Weight</label></div>
                                <div class="col-md-2"><input type="number" step="0.01" class="form-control form-control-sm" :value="totalWeight" readonly></div>
                            </div>
                            <div class="row align-items-start mt-3">
                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark mt-1">Remarks</label></div>
                                <div class="col-md-10"><textarea class="form-control form-control-sm" rows="2" v-model="form.remark"></textarea></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-footer bg-light d-flex justify-content-center gap-2 border-top-0 pt-3 pb-3">
                            <Link href="/loading-reports" class="btn btn-outline-secondary px-4"><i class="fas fa-times me-1"></i> Exit</Link>
                            <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <LoadingReportConfirmModal
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
import LoadingReportConfirmModal from '@/Components/FormConfirm/LoadingReportConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const fetch_station_id = ref('');
const fetchStationSelect = ref(null);
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();

const ALL_STATIONS_VALUE = 'all';

const props = defineProps({
    stations: Array,
    trucks: Array,
    bilties: Array
});

const stationOptions = ref([...(props.stations || [])]);
const truckOptions = ref((props.trucks || []).map(t => ({ ...t, name: t.name || t.truck_number })));
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.trucks, (v) => { truckOptions.value = (v || []).map(t => ({ ...t, name: t.name || t.truck_number })); });


const destinationStationOptions = computed(() => [
    { id: ALL_STATIONS_VALUE, name: 'ALL' },
    ...stationOptions.value,
]);

const form = useForm({
    from_station_id: '',
    to_station_id: '',
    truck_id: '',
    driver_name: '',
    date: new Date().toISOString().substr(0, 10),
    remark: '',

    // Details Grid
    details: [
        { bilty_id: '', bilty_number: '', good_id: '', package_type_id: '', consignee_id: '', consignee_name: '', goods_name: '', no_of_packages: '', gross_weight: '' }
    ]
});

const fetchBiltiesByStation = () => {
    if (!fetch_station_id.value) return false;

    const matchingBilties = fetch_station_id.value === ALL_STATIONS_VALUE
        ? props.bilties
        : props.bilties.filter(b => b.to_station_id === fetch_station_id.value);

    if (matchingBilties.length === 0) {
        alert(fetch_station_id.value === ALL_STATIONS_VALUE
            ? 'No pending bilties found.'
            : 'No pending bilties found headed to this station.');
        fetch_station_id.value = '';
        return false;
    }

    if (form.details.length === 1 && !form.details[0].bilty_id) {
        form.details.splice(0, 1);
    }

    let addedCount = 0;
    matchingBilties.forEach(bilty => {
        if (form.details.some(d => d.bilty_id === bilty.id)) return;

        form.details.push({ bilty_id: bilty.id, bilty_number: bilty.bilty_number, good_id: '', package_type_id: '', consignee_id: '', consignee_name: '', goods_name: '', no_of_packages: '', gross_weight: '' });
        fetchBiltyDetails(form.details.length - 1);
        addedCount++;
    });

    if (form.details.length === 0) {
        addRow();
    }

    if (addedCount > 0) {
        return true;
    } else {
        alert(fetch_station_id.value === ALL_STATIONS_VALUE
            ? 'All pending bilties are already added to the grid.'
            : 'All bilties for this station are already added to the grid.');
        fetch_station_id.value = '';
        return false;
    }
};

const biltyLabel = (id) => props.bilties.find((b) => b.id === id)?.bilty_number ?? '--';

const getFetchStationTrigger = () =>
    fetchStationSelect.value?.$el?.querySelector('input.searchable-select__trigger');

const refocusFetchStationSelect = () => {
    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }

    nextTick(() => {
        fetchStationSelect.value?.focusOpen?.();
    });
};

const onFetchStationChange = () => {
    if (!fetch_station_id.value) return;

    const success = fetchBiltiesByStation();
    if (success) {
        nextTick(() => {
            fetchStationSelect.value?.focusTrigger?.();
        });

        setTimeout(async () => {
            const yes = await askKeyboardConfirm('Do you want to add bilties for another station?', {
                from: getFetchStationTrigger(),
            });
            if (yes) {
                fetch_station_id.value = '';
                refocusFetchStationSelect();
            } else {
                fetch_station_id.value = '';
            }
        }, 100);
    }
};

const addRow = () => {
    form.details.push({ bilty_id: '', good_id: '', package_type_id: '', consignee_id: '', consignee_name: '', goods_name: '', no_of_packages: '', gross_weight: '' });
};

const removeRow = (index) => {
    form.details.splice(index, 1);
};

const fetchBiltyDetails = (index) => {
    const row = form.details[index];
    const selectedBilty = props.bilties.find(b => b.id === row.bilty_id);
    if (selectedBilty) {
        row.bilty_number = selectedBilty.bilty_number;
        row.consignee_id = selectedBilty.consignee_id;
        row.consignee_name = selectedBilty.consignee ? selectedBilty.consignee.name : 'Unknown';

        // Use the first detail for simplicity, or aggregate
        if(selectedBilty.bilty_details && selectedBilty.bilty_details.length > 0) {
            const bDetail = selectedBilty.bilty_details[0];
            row.good_id = bDetail.good_id;
            row.package_type_id = bDetail.package_type_id;
            // You might need a relation lookup for the actual good name, but let's assume we can fetch it or just display ID for now.
            // For a robust system, we would pass Goods to this view as well. Let's just use raw weight/packages.
            row.goods_name = 'Good ID: ' + bDetail.good_id;

            // Calculate total packages and weight from this bilty
            let sumPkg = 0;
            let sumWt = 0;
            selectedBilty.bilty_details.forEach(d => {
                sumPkg += parseFloat(d.no_of_packages || 0);
                sumWt += parseFloat(d.gross_weight || 0);
            });
            row.no_of_packages = sumPkg;
            row.gross_weight = sumWt.toFixed(2);
        }
    } else {
        row.consignee_id = '';
        row.consignee_name = '';
        row.good_id = '';
        row.package_type_id = '';
        row.goods_name = '';
        row.no_of_packages = '';
        row.gross_weight = '';
    }
};

const totalPackages = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.no_of_packages || 0), 0);
});

const totalWeight = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.gross_weight || 0), 0).toFixed(2);
});

const confirmTotals = computed(() => ({
    totalPackages: totalPackages.value,
    totalWeight: totalWeight.value,
}));

const submit = () => {
    openConfirm();
};

const submitLoadingReport = (printAfterSave = false) => {
    confirmAndRun(() => {
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .post('/loading-reports', withPrintAfterSave(printAfterSave));
    });
};

const confirmSave = () => submitLoadingReport(false);
const confirmSaveAndPrint = () => submitLoadingReport(true);
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

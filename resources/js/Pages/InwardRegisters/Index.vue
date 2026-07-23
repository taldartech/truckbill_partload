<template>
    <AdminLayout title="Inward Register">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Inward Register Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create inward_registers')"
                            href="/inward-registers/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="inward-registers-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="inward_registers_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="inward_registers_search"
                            placeholder="Reg no, challan, driver"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>

                    <div class="filter-period-col">
                        <IndexDateRangePicker
                            v-model:from-date="filters.from_date"
                            v-model:to-date="filters.to_date"
                            label="Register Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">From Station</label>
                        <SearchableSelect
                            v-model="filters.from_station_id"
                            :options="stationOptions"
                            placeholder="All stations"
                            :can-create="false"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Truck</label>
                        <SearchableSelect
                            v-model="filters.truck_id"
                            :options="truckOptions"
                            placeholder="All trucks"
                            :can-create="false"
                            :disabled="searching"
                        />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Reg No.</th>
                                    <th>Date</th>
                                    <th>From → To</th>
                                    <th>Challan No.</th>
                                    <th>Truck No.</th>
                                    <th>Total Pkg.</th>
                                    <th>Delivery Freight</th>
                                    <th>Crossing Freight</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="register in registers.data" :key="register.id">
                                    <td>{{ register.id }}</td>
                                    <td>{{ register.inward_register_no || '—' }}</td>
                                    <td>{{ register.date }}</td>
                                    <td>
                                        <span class="text-bold">{{ register.from_station?.name }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ register.to_station?.name }}</span>
                                    </td>
                                    <td>{{ register.challan_number || '—' }} ({{ register.challan_date }})</td>
                                    <td>{{ register.truck?.truck_number }}</td>
                                    <td>{{ register.total_packages ?? register.details?.length ?? 0 }}</td>
                                    <td>{{ register.total_delivery_freight ?? '0.00' }}</td>
                                    <td>{{ register.total_crossing_freight ?? '0.00' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/inward-registers/${register.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteRegister(register.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="registers.data.length === 0">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No inward registers found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="registers" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import IndexDateRangePicker from '@/Components/Index/IndexDateRangePicker.vue';
import { thisMonthDateRange } from '@/utils/dateRangePresets';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    registers: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    trucks: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);
const deleteForm = useForm({});

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    from_station_id: props.filters.from_station_id ?? '',
    truck_id: props.filters.truck_id ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.from_station_id = next.from_station_id ?? '';
        filters.truck_id = next.truck_id ?? '';
    },
    { deep: true },
);

const stationOptions = computed(() => props.stations);
const truckOptions = computed(() => props.trucks);

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        from_station_id: filters.from_station_id || undefined,
        truck_id: filters.truck_id || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/inward-registers', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            searching.value = false;
        },
    });
};

const resetFilters = () => {
    const range = thisMonthDateRange();
    filters.search = '';
    filters.from_date = range.from_date;
    filters.to_date = range.to_date;
    filters.from_station_id = '';
    filters.truck_id = '';
    applyFilters();
};

const deleteRegister = (id) => {
    if (confirm('Are you sure you want to delete this register?')) {
        deleteForm.delete(`/inward-registers/${id}`);
    }
};
</script>

<style scoped>
:deep(.app-index-collapsible-filters__inner) {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: flex-end !important;
    gap: 10px !important;
}

:deep(.app-index-filters-fields) {
    display: contents !important;
}

:deep(.app-index-collapsible-filters__actions) {
    display: flex !important;
    flex-wrap: nowrap !important;
    margin-top: 0 !important;
    margin-left: auto !important;
    align-self: flex-end !important;
    flex: 0 0 auto !important;
    gap: 0.5rem;
}

.filter-search-col,
.filter-period-col,
.filter-picker-col {
    min-width: 0 !important;
}

.filter-search-col {
    flex: 1 1 200px !important;
    width: auto !important;
    max-width: 240px !important;
}

.filter-period-col {
    flex: 0 1 180px !important;
    width: 180px !important;
    max-width: 180px !important;
}

.filter-picker-col {
    flex: 1 1 170px !important;
    width: auto !important;
    max-width: 210px !important;
}

.filter-search-col :deep(.app-index-search),
.filter-period-col :deep(.dashboard-date-range),
.filter-picker-col :deep(.searchable-select) {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

.filter-search-col :deep(.app-index-search .input-group-text),
.filter-search-col :deep(.app-index-search .form-control),
.filter-period-col :deep(.dashboard-date-range__trigger),
.filter-picker-col :deep(.searchable-select__trigger),
:deep(.app-index-collapsible-filters__btn) {
    height: 36px !important;
    min-height: 36px !important;
}

.filter-search-col :deep(.app-index-search .input-group-text) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    line-height: 1 !important;
}

.filter-picker-col :deep(.searchable-select__trigger) {
    width: 100% !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
}

.filter-search-col > .form-label,
.filter-picker-col > .form-label,
.filter-period-col :deep(.dashboard-date-range__label) {
    margin-bottom: 0.35rem;
    white-space: nowrap;
}

@media (max-width: 991.98px) {
    :deep(.app-index-collapsible-filters__inner) {
        flex-wrap: wrap !important;
    }

    .filter-search-col,
    .filter-period-col,
    .filter-picker-col {
        flex: 1 1 calc(50% - 10px) !important;
        width: auto !important;
        max-width: none !important;
    }

    :deep(.app-index-collapsible-filters__actions) {
        margin-left: 0 !important;
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

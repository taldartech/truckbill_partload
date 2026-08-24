<template>
    <AdminLayout title="Main Challans">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Main Challan Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create challans')"
                            href="/main-challans/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="main-challans-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="main_challans_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="main_challans_search"
                            placeholder="Main challan #, truck, driver"
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
                            label="Main Challan Date"
                            panel-align="start"
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
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Vehicle &amp; Driver</th>
                                    <th>Amount</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="challan in main_challans.data" :key="challan.id">
                                    <td>
                                        <Link :href="`/main-challans/${challan.id}`">{{ challan.main_challan_number || 'N/A' }}</Link>
                                    </td>
                                    <td>{{ challan.date || 'N/A' }}</td>
                                    <td>
                                        <span class="text-bold">{{ challan.from_station?.name }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ challan.to_station?.name }}</span>
                                    </td>
                                    <td>
                                        <div class="text-bold">
                                            <i class="fas fa-truck me-1 text-muted small"></i>
                                            {{ challan.truck?.truck_number }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-user me-1 text-muted small"></i>
                                            {{ challan.driver_name || 'N/A' }}
                                        </div>
                                    </td>
                                    <td>{{ challan.total_amount || '0.00' }}</td>
                                    <td class="text-end">
                                        <a
                                            :href="`/main-challans/${challan.id}/print`"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-info me-2"
                                            title="Print Main Challan"
                                        >
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/main-challans/${challan.id}/edit`" class="btn btn-sm btn-info text-white me-2" v-if="can('edit challans')">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteItem(challan.id)" v-if="can('delete challans')" >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="main_challans.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No main challans found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="main_challans" />
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
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';
const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    main_challans: {
        type: Object,
        required: true,
    },
    trucks: {
        type: Array,
        default: () => [],
    },
    stations: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    truck_id: props.filters.truck_id ?? '',
    from_station_id: props.filters.from_station_id ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.truck_id = next.truck_id ?? '';
        filters.from_station_id = next.from_station_id ?? '';
    },
    { deep: true },
);

const truckOptions = computed(() => props.trucks);
const stationOptions = computed(() => props.stations);

const form = useForm({});

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        truck_id: filters.truck_id || undefined,
        from_station_id: filters.from_station_id || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;

    router.get('/main-challans', buildQuery(), {
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
    filters.truck_id = '';
    filters.from_station_id = '';
    applyFilters();
};

const deleteItem = async (id) => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this record?');
    if (yes) form.delete(`/main-challans/${id}`);
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

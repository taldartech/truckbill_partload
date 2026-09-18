<template>
    <AdminLayout title="Challan">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Challan Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create challans')"
                            href="/challans/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus ms-1 ms-sm-0" aria-hidden="true"></span>
                            <span class="d-none d-sm-block">Create</span>
                        </Link>
                        <button type="button" class="btn btn-sm btn-success d-block d-sm-none"
                            :title="showFilters ? 'Hide Filters' : 'Show Filters'" @click="showFilters = !showFilters">
                            <i class="fas fa-filter p-1"></i>
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters v-if="showFilters"
                    panel-id="challans-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="challans_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="challans_search"
                            placeholder="Challan #, truck, driver"
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
                            label="Challan Date"
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
                        <label class="form-label">Status</label>
                        <SearchableSelect
                            v-model="filters.status"
                            :options="statusOptions"
                            placeholder="All statuses"
                            :can-create="false"
                            :disabled="searching"
                        />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="d-sm-none">
                        <div
                            v-for="challan in challans.data"
                            :key="challan.id"
                            class="px-3 py-2 border-bottom"
                        >
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2 text-truncate">
                                    <Link
                                        :href="`/challans/${challan.id}`"
                                        class="fw-bold text-decoration-none"
                                    >
                                        {{ challan.challan_number || 'N/A' }}
                                    </Link>
                                    <span class="text-muted small text-nowrap">
                                        {{ challan.date || 'N/A' }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-2">
                                    <span
                                        class="badge"
                                        :class="{
                                            'bg-warning text-dark': challan.status === 'Created',
                                            'bg-info text-white': challan.status === 'In Progress',
                                            'bg-success': challan.status === 'Completed',
                                            'bg-danger': challan.status === 'Cancelled',
                                        }"
                                    >
                                        {{ challan.status }}
                                    </span>
                                    <a
                                        :href="`/challans/${challan.id}/print`"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-info py-0 px-2"
                                        title="Print Challan"
                                    >
                                        <i class="fas fa-print small"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="small text-muted mt-1 d-flex align-items-center justify-content-between text-truncate">
                                <div class="text-truncate">
                                    <span class="text-dark fw-medium">{{ challan.from_station?.name || 'N/A' }}</span>
                                    <i class="fas fa-arrow-right mx-1 text-muted" style="font-size: 0.75rem;"></i>
                                    <span class="text-dark fw-medium">{{ challan.to_station?.name || 'N/A' }}</span>
                                </div>
                                <span v-if="challan.truck?.truck_number" class="badge bg-light text-secondary border ms-2 text-nowrap">
                                    {{ challan.truck?.truck_number }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="challans.data.length === 0"
                            class="text-center text-muted py-4 small"
                        >
                            No challans found for selected filters.
                        </div>
                    </div>

                    <div class="table-responsive d-none d-sm-block">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Vehicle &amp; Driver</th>
                                    <th>Freight</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="challan in challans.data" :key="challan.id">
                                    <td>
                                        <Link :href="`/challans/${challan.id}`">{{ challan.challan_number || 'N/A' }}</Link>
                                    </td>
                                    <td class="text-nowrap ">{{ challan.date || 'N/A' }}</td>
                                    <td class="text-nowrap ">
                                        <span class="text-bold">{{ challan.from_station?.name }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ challan.to_station?.name }}</span>
                                    </td>
                                    <td class="text-nowrap ">
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
                                    <td>
                                        <span
                                            class="badge"
                                            :class="{
                                                'bg-warning': challan.status === 'Created',
                                                'bg-info': challan.status === 'In Progress',
                                                'bg-success': challan.status === 'Completed',
                                                'bg-danger': challan.status === 'Cancelled',
                                            }"
                                        >{{ challan.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                        <a
                                            :href="`/challans/${challan.id}/print`"
                                            target="_blank"
                                            class="btn btn-outline-info btn-sm "
                                            title="Print Challan"
                                        >
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/challans/${challan.id}/edit`" class="btn btn-edit btn-sm " v-if="can('challans edit')">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteItem(challan)" v-if="can('challans delete')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="challans.data.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No challans found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="challans" />
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
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';
import { useResponsiveIndexFilters } from '@/composables/useResponsiveIndexFilters';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    challans: {
        type: Object,
        required: true,
    },
    trucks: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { showFilters } = useResponsiveIndexFilters();

const defaultRange = thisMonthDateRange();
const searching = ref(false);

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    truck_id: props.filters.truck_id ?? '',
    status: props.filters.status ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.truck_id = next.truck_id ?? '';
        filters.status = next.status ?? '';
    },
    { deep: true },
);

const truckOptions = computed(() => props.trucks);
const statusOptions = computed(() => props.statuses);

const form = useForm({});

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        truck_id: filters.truck_id || undefined,
        status: filters.status || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;

    router.get('/challans', buildQuery(), {
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
    filters.status = '';
    applyFilters();
};

const deleteItem = async (challan) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this challan?

Challan    : ${challan.challan_number}
Date       : ${challan.date}
Truck      : ${challan.truck?.truck_number}

From       : ${challan.from_station?.name}
To         : ${challan.to_station?.name}`
    );

    if (!remark) return;

    router.delete(`/challans/${challan.id}`, {
        data: {
            delete_remark: remark,
        },
    });
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

@media (max-width: 500.98px) {
    .app-index-collapsible-filters__inner > div:not(.app-index-collapsible-filters__actions),
    .app-report-filters-panel__inner > div:not(.app-report-filters-panel__actions),
    .app-index-filters-fields > div {
        flex: 0 0 100% !important;
        width: 100% !important;
    }
}
</style>

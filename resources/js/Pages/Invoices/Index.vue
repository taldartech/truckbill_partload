<template>
    <AdminLayout title="Invoice">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Invoice Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create invoices')"
                            href="/invoices/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="invoices-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="invoices_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="invoices_search"
                            placeholder="Invoice number"
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
                            label="Invoice Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Party</label>
                        <SearchableSelect
                            v-model="filters.party_id"
                            :options="partyOptions"
                            placeholder="All parties"
                            :can-create="false"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label" for="invoices_status">Status</label>
                        <select
                            id="invoices_status"
                            class="form-select"
                            v-model="filters.status"
                            :disabled="searching"
                        >
                            <option value="">All statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Partial">Partial</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th>Party Name</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in invoices.data" :key="invoice.id">
                                    <td>
                                        <Link :href="`/invoices/${invoice.id}`" class="fw-semibold text-decoration-none">
                                            {{ invoice.invoice_no }}
                                        </Link>
                                    </td>
                                    <td>{{ invoice.date }}</td>
                                    <td>{{ invoice.party?.name }}</td>
                                    <td class="fw-bold">₹ {{ formatAmount(invoice.net_amount) }}</td>
                                    <td>
                                        <span class="badge" :class="statusBadgeClass(invoice.status)">
                                            {{ displayStatus(invoice.status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a
                                            :href="`/invoices/${invoice.id}/print`"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-info me-2"
                                            title="Print Invoice"
                                        >
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link
                                            v-if="can('edit invoices') && invoice.can_edit"
                                            :href="`/invoices/${invoice.id}/edit`"
                                            class="btn btn-sm btn-info text-white me-2"
                                            title="Edit Invoice"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            v-if="can('delete invoices') && invoice.can_delete"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            title="Delete Invoice"
                                            @click="deleteInvoice(invoice)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No invoices found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="invoices" />
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

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    invoices: { type: Object, required: true },
    parties: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);
const deleteForm = useForm({});

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    party_id: props.filters.party_id ?? '',
    status: props.filters.status ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.party_id = next.party_id ?? '';
        filters.status = next.status ?? '';
    },
    { deep: true },
);

const partyOptions = computed(() => props.parties || []);

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        party_id: filters.party_id || undefined,
        status: filters.status || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/invoices', buildQuery(), {
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
    filters.party_id = '';
    filters.status = '';
    applyFilters();
};

const deleteInvoice = async (invoice) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this invoice?

Invoice No : ${invoice.invoice_no}
Date       : ${invoice.date}
Party      : ${invoice.party?.name || '—'}
Amount     : ₹ ${formatAmount(invoice.net_amount)}
Status     : ${invoice.status || 'Pending'}`
    );

    if (!remark) {
        return;
    }

    router.delete(`/invoices/${invoice.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
const formatAmount = (value) => {
    const amount = parseFloat(value || 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
};

const displayStatus = (status) => status || 'Pending';

const statusBadgeClass = (status) => {
    const normalized = displayStatus(status);
    if (normalized === 'Paid') return 'bg-success';
    if (normalized === 'Partial') return 'bg-warning text-dark';
    return 'bg-danger';
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
.filter-picker-col :deep(.searchable-select),
.filter-picker-col :deep(.form-select) {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

.filter-search-col :deep(.app-index-search .input-group-text),
.filter-search-col :deep(.app-index-search .form-control),
.filter-period-col :deep(.dashboard-date-range__trigger),
.filter-picker-col :deep(.searchable-select__trigger),
.filter-picker-col :deep(.form-select),
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

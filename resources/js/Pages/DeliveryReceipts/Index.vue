<template>
    <AdminLayout title="Delivery Receipt">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Delivery Receipt Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create delivery_receipts')"
                            href="/delivery-receipts/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="delivery-receipts-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="delivery_receipts_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="delivery_receipts_search"
                            placeholder="Receipt no, GR/bilty"
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
                            label="Receipt Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Consignee</label>
                        <SearchableSelect
                            v-model="filters.consignee_id"
                            :options="partyOptions"
                            placeholder="All consignees"
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
                                    <th>Receipt No.</th>
                                    <th>Date</th>
                                    <th>GR/Bilty No.</th>
                                    <th>Consignee</th>
                                    <th>Mode of Payment</th>
                                    <th>Final Amount</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="receipt in receipts.data" :key="receipt.id">
                                    <td>
                                        <Link :href="`/delivery-receipts/${receipt.id}`" class="fw-semibold text-decoration-none">
                                            {{ receipt.delivery_receipt_no || '—' }}
                                        </Link>
                                    </td>
                                    <td class="text-nowrap">{{ receipt.date }}</td>
                                    <td>{{ receipt.gr_no_bilty_no }}</td>
                                    <td>{{ receipt.consignee?.name }}</td>
                                    <td>{{ receipt.mode_of_payment }}</td>
                                    <td class="fw-bold">₹ {{ receipt.final_amount }}</td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                        <a
                                            :href="`/delivery-receipts/${receipt.id}/print`"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-info me-2"
                                            title="Print Receipt"
                                        >
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/delivery-receipts/${receipt.id}/edit`" class="btn btn-sm btn-info text-white me-2" v-if="can('edit delivery_receipts')">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteReceipt(receipt)" v-if="can('delete delivery_receipts')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="receipts.data.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No delivery receipts found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="receipts" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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
    receipts: { type: Object, required: true },
    parties: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    consignee_id: props.filters.consignee_id ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.consignee_id = next.consignee_id ?? '';
    },
    { deep: true },
);

const partyOptions = computed(() => props.parties);

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        consignee_id: filters.consignee_id || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/delivery-receipts', buildQuery(), {
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
    filters.consignee_id = '';
    applyFilters();
};

const deleteReceipt = async (receipt) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this delivery receipt?

Receipt No : ${receipt.delivery_receipt_no}
Date       : ${receipt.date}
GR/Bilty   : ${receipt.gr_no_bilty_no}
Consignee  : ${receipt.consignee?.name || '—'}
Amount     : ₹ ${receipt.final_amount}`
    );

    if (!remark) return;

    router.delete(`/delivery-receipts/${receipt.id}`, {
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

<template>
    <AdminLayout title="Accounting Vouchers">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Accounting Vouchers</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create accounting_vouchers')"
                            href="/accounting/vouchers/create"
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
                    panel-id="accounting-vouchers-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col col-12 col-sm-auto">
                        <label class="form-label" for="av_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="av_search"
                            placeholder="Voucher no, narration"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>

                    <div class="filter-period-col">
                        <IndexDateRangePicker
                            v-model:from-date="filters.start_date"
                            v-model:to-date="filters.end_date"
                            label="Voucher Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label" for="av_type">Type</label>
                        <select
                            id="av_type"
                            class="form-select"
                            v-model="filters.voucher_type"
                            :disabled="searching"
                        >
                            <option value="">All types</option>
                            <option v-for="t in voucherTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher No.</th>
                                    <th>Type</th>
                                    <th>Narration</th>
                                    <th>Status</th>
                                    <th class="text-end" v-if="can('edit accounting_vouchers')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="voucher in vouchers.data" :key="voucher.id">
                                    <td>{{ voucher.date }}</td>
                                    <td>
                                        <Link :href="`/accounting/vouchers/${voucher.id}`" class="fw-semibold text-decoration-none text-brand-primary">
                                            {{ voucher.voucher_number || '—' }}
                                        </Link>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ voucherTypeLabel(voucher.voucher_type) }}</span>
                                    </td>
                                    <td>{{ voucher.narration || '—' }}</td>
                                    <td>
                                        <span
                                            class="badge"
                                            :class="{
                                                'bg-success': voucher.status === 'posted',
                                                'bg-warning text-dark': voucher.status === 'cancelled',
                                                'bg-secondary': voucher.status !== 'posted' && voucher.status !== 'cancelled',
                                            }"
                                        >{{ voucher.status }}</span>
                                    </td>
                                    <td class="text-end" v-if="can('edit accounting_vouchers')">
                                        <template v-if="voucher.status === 'posted'">
                                            <button type="button" class="btn btn-sm btn-warning me-2" @click="cancelVoucher(voucher)">
                                                Cancel
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" @click="reverseVoucher(voucher)">
                                                Reverse
                                            </button>
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                </tr>
                                <tr v-if="vouchers.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No vouchers found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="vouchers" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { useResponsiveIndexFilters } from '@/composables/useResponsiveIndexFilters';

const { showFilters } = useResponsiveIndexFilters();
import IndexDateRangePicker from '@/Components/Index/IndexDateRangePicker.vue';
import { thisMonthDateRange } from '@/utils/dateRangePresets';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const props = defineProps({
    vouchers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    voucherTypes: { type: Array, default: () => [] },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);

const filters = reactive({
    search: props.filters.search ?? '',
    start_date: props.filters.start_date ?? defaultRange.from_date,
    end_date: props.filters.end_date ?? defaultRange.to_date,
    voucher_type: props.filters.voucher_type ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.start_date = next.start_date ?? defaultRange.from_date;
        filters.end_date = next.end_date ?? defaultRange.to_date;
        filters.voucher_type = next.voucher_type ?? '';
    },
    { deep: true },
);

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        voucher_type: filters.voucher_type || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/accounting/vouchers', buildQuery(), {
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
    filters.start_date = range.from_date;
    filters.end_date = range.to_date;
    filters.voucher_type = '';
    applyFilters();
};

const voucherTypeLabel = (value) => {
    const match = props.voucherTypes.find((t) => t.value === value);
    return match?.label || value;
};

const cancelVoucher = async (voucher) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Cancel this voucher? Entries are retained but excluded from reports.

Voucher No : ${voucher.voucher_number}
Date       : ${voucher.date}
Type       : ${voucherTypeLabel(voucher.voucher_type)}`
    );
    if (!remark) return;
    router.post(`/accounting/vouchers/${voucher.id}/cancel`, {}, { preserveScroll: true });
};

const reverseVoucher = async (voucher) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Post a reversing voucher for this entry?

Voucher No : ${voucher.voucher_number}
Date       : ${voucher.date}`
    );
    if (!remark) return;
    router.post(`/accounting/vouchers/${voucher.id}/reverse`, {}, { preserveScroll: true });
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
.filter-picker-col :deep(.form-select) {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

.filter-search-col :deep(.app-index-search .input-group-text),
.filter-search-col :deep(.app-index-search .form-control),
.filter-period-col :deep(.dashboard-date-range__trigger),
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

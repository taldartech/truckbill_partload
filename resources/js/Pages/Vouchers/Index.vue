<template>
    <AdminLayout title="Voucher">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Voucher Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create vouchers')"
                            href="/vouchers/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="vouchers-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="vouchers_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="vouchers_search"
                            placeholder="Voucher no, account"
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
                            label="Voucher Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label" for="vouchers_type">Type</label>
                        <select
                            id="vouchers_type"
                            class="form-select"
                            v-model="filters.voucher_type"
                            :disabled="searching"
                        >
                            <option value="">All types</option>
                            <option value="Payment">Payment</option>
                            <option value="Receipt">Receipt</option>
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
                                    <th>Account</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th class="text-end" v-if="can('edit vouchers') || can('delete vouchers')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="voucher in vouchers.data" :key="voucher.id">
                                    <td>{{ voucher.date }}</td>
                                    <td>
                                        <Link :href="`/vouchers/${voucher.id}`" class="fw-semibold text-decoration-none">
                                            {{ voucher.voucher_number || '—' }}
                                        </Link>
                                    </td>
                                    <td>
                                        <span
                                            class="badge"
                                            :class="{
                                                'bg-success': voucher.voucher_type === 'Receipt',
                                                'bg-danger': voucher.voucher_type === 'Payment',
                                            }"
                                        >{{ voucher.voucher_type }}</span>
                                    </td>
                                    <td>{{ entityLabel(voucher) }}</td>
                                    <td>{{ modeLabel(voucher) }}</td>
                                    <td>₹ {{ voucher.amount }}</td>
                                    <td class="text-end"  v-if="can('edit vouchers') || can('delete vouchers')">
                                        <Link :href="`/vouchers/${voucher.id}/edit`" class="btn btn-sm btn-info text-white me-2" v-if="can('edit vouchers')">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteVoucher(voucher.id)" v-if="can('delete vouchers')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="vouchers.data.length === 0">
                                    <td colspan="8" class="text-center text-muted py-4">
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import IndexDateRangePicker from '@/Components/Index/IndexDateRangePicker.vue';
import { thisMonthDateRange } from '@/utils/dateRangePresets';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    vouchers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);
const deleteForm = useForm({});

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    voucher_type: props.filters.voucher_type ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.voucher_type = next.voucher_type ?? '';
    },
    { deep: true },
);

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        voucher_type: filters.voucher_type || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/vouchers', buildQuery(), {
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
    filters.voucher_type = '';
    applyFilters();
};

const deleteVoucher = (id) => {
    if (confirm('Are you sure you want to delete this voucher?')) {
        deleteForm.delete(`/vouchers/${id}`);
    }
};

const entityLabel = (voucher) => {
    const name = voucher.accountable?.name;
    if (!name) return '—';
    const type = (voucher.accountable_type || '').split('\\').pop();
    return type ? `${name} (${type})` : name;
};

const modeLabel = (voucher) => {
    if (voucher.mode === 'bank') {
        return voucher.bank_account?.name ? `Bank · ${voucher.bank_account.name}` : 'Bank';
    }
    return 'Cash';
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

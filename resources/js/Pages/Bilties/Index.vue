<template>
    <AdminLayout title="Bilties">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Bilty Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link v-if="can('create bilties')" href="/bilties/create"
                            class="btn btn-sm app-index-header-btn">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus"
                                aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters panel-id="bilties-filters-panel" :loading="searching" @search="applyFilters"
                    @reset="resetFilters">
                    <div class="filter-search-col">
                        <label class="form-label" for="bilties_search">Search</label>
                        <AppIndexSearchInput v-model="filters.search" input-id="bilties_search"
                            placeholder="Bilty number" max-width="100%" size="md" :disabled="searching"
                            @keydown.enter="applyFilters" />
                    </div>

                    <div class="filter-period-col">
                        <IndexDateRangePicker v-model:from-date="filters.from_date" v-model:to-date="filters.to_date"
                            label="Bilty Date" panel-align="start" :disabled="searching" />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Consignor</label>
                        <SearchableSelect v-model="filters.consignor_id" :options="partyOptions"
                            placeholder="All consignors" :can-create="false" :disabled="searching" />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Consignee</label>
                        <SearchableSelect v-model="filters.consignee_id" :options="partyOptions"
                            placeholder="All consignees" :can-create="false" :disabled="searching" />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>From - To</th>
                                    <th>Consignor - Consignee</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bilty in bilties.data" :key="bilty.id">
                                    <td>
                                        <Link :href="`/bilties/${bilty.id}`" class="fw-semibold text-decoration-none">
                                            {{ bilty.bilty_number || 'N/A' }}
                                        </Link>
                                    </td>
                                    <td class="text-nowrap">{{ bilty.date || 'N/A' }}</td>
                                    <td class="text-nowrap">
                                        <span class="text-bold">{{ bilty.from_station?.name }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ bilty.to_station?.name }}</span>
                                    </td>
                                    <td>
                                        <div>S: {{ bilty.consignor?.name }}</div>
                                        <div class="small text-muted text-italic">R: {{ bilty.consignee?.name }}</div>
                                    </td>
                                    <td>{{ bilty.amount || '0.00' }}</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-warning': bilty.status === 'Pending',
                                            'bg-info': bilty.status === 'In Transit',
                                            'bg-success': bilty.status === 'Delivered',
                                            'bg-danger': bilty.status === 'Cancelled',
                                        }">{{ bilty.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="bilty-table-actions">
                                            <BiltyPrintButton :bilty-id="bilty.id"
                                                button-class="btn btn-sm btn-outline-info" title="Print Bilty">
                                                <i class="fas fa-print"></i>
                                            </BiltyPrintButton>

                                            <Link v-if="can('edit bilties')" :href="`/bilties/${bilty.id}/edit`"
                                                class="btn btn-sm btn-info text-white">
                                                <i class="fas fa-edit"></i>
                                            </Link>

                                            <button v-if="can('delete bilties')" type="button"
                                                class="btn btn-sm btn-danger" @click="deleteItem(bilty.id)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="bilties.data.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No bilties found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="bilties" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import BiltyPrintButton from '@/Components/BiltyPrintButton.vue';
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
    bilties: {
        type: Object,
        required: true,
    },
    parties: {
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
    consignor_id: props.filters.consignor_id ?? '',
    consignee_id: props.filters.consignee_id ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.consignor_id = next.consignor_id ?? '';
        filters.consignee_id = next.consignee_id ?? '';
    },
    { deep: true },
);

const partyOptions = computed(() => props.parties);

const form = useForm({});

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        consignor_id: filters.consignor_id || undefined,
        consignee_id: filters.consignee_id || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;

    router.get('/bilties', buildQuery(), {
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
    filters.consignor_id = '';
    filters.consignee_id = '';
    applyFilters();
};

const deleteItem = async (id,bilty_number) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete Bilty ${bilty_number}?`
    );
    if (!remark) {
        return;
    }
    router.delete(`/bilties/${id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

<style scoped>
/* Keep all filter fields + Search/Reset on one row */
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

.filter-search-col,
.filter-period-col,
.filter-picker-col {
    flex: 1 1 210px !important;
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

.filter-search-col>.form-label,
.filter-picker-col>.form-label,
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
        width: calc(50% - 10px) !important;
        max-width: none !important;
    }

    :deep(.app-index-collapsible-filters__actions) {
        margin-left: 0 !important;
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 767.98px) {
    .bilty-charge-row {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }

    .bilty-charge-row__label {
        padding-top: 0;
    }

    .bilty-addon-row,
    .bilty-addon-row--head {
        grid-template-columns: 1fr;
    }

    .bilty-addon-row--head {
        display: none;
    }

    .bilty-addon-row__action {
        justify-content: flex-start;
    }

    .bilty-eway-row {
        grid-template-columns: minmax(0, 1fr) 2.5rem;
        gap: 0.65rem;
    }

    .bilty-eway-row__field:first-child {
        grid-column: 1 / -1;
    }

    .bilty-eway-row--head {
        display: none;
    }

    .bilty-eway-row__action {
        justify-content: flex-start;
        padding-top: 0;
    }

    .bilty-eway-remove {
        width: 2.5rem;
        height: 42px;
    }

    .bilty-table-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        justify-content: flex-end;
    }

    .bilty-table-actions .btn {
        padding: 0.25rem 0.4rem;
        min-width: 2.5rem;
    }
}

.bilty-table-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: nowrap;
    gap: 0.5rem;
}

.bilty-table-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 767.98px) {
    .bilty-charge-row {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }

    .bilty-charge-row__label {
        padding-top: 0;
    }

    .bilty-addon-row,
    .bilty-addon-row--head {
        grid-template-columns: 1fr;
    }

    .bilty-addon-row--head,
    .bilty-eway-row--head {
        display: none;
    }

    .bilty-addon-row__action,
    .bilty-eway-row__action {
        justify-content: flex-start;
    }

    .bilty-eway-row {
        grid-template-columns: 1fr 2.2rem;
    }

    .bilty-eway-row__field:first-child {
        grid-column: 1 / -1;
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

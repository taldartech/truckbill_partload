<template>
    <AdminLayout title="Financial Years">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Financial Years</h3>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Period</th>
                                    <th>Vouchers</th>
                                    <th>Status</th>
                                    <th>Closed By</th>
                                    <th class="text-end" v-if="can('delete accounting_settings')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="year in financialYears" :key="year.id">
                                    <td>
                                        <span class="fw-bold text-brand-primary">{{ year.name }}</span>
                                        <span v-if="year.is_current" class="badge bg-info ms-2">Current</span>
                                    </td>
                                    <td>{{ year.starts_on }} to {{ year.ends_on }}</td>
                                    <td>{{ year.vouchers_count }}</td>
                                    <td>
                                        <span class="badge" :class="year.is_closed ? 'bg-secondary' : 'bg-success'">
                                            {{ year.is_closed ? 'Closed' : 'Open' }}
                                        </span>
                                    </td>
                                    <td>
                                        <template v-if="year.is_closed">
                                            {{ year.closed_by?.name || '—' }}
                                            <div v-if="year.closed_at" class="small text-muted">{{ formatDateTime(year.closed_at) }}</div>
                                        </template>
                                        <template v-else>—</template>
                                    </td>
                                    <td class="text-end" v-if="can('delete accounting_settings')">
                                        <button
                                            v-if="!year.is_closed"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="closeYear(year)"
                                        >
                                            <i class="fas fa-lock me-1"></i> Close
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            @click="reopenYear(year)"
                                        >
                                            <i class="fas fa-lock-open me-1"></i> Reopen
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="financialYears.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No financial years found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

defineProps({
    financialYears: { type: Array, default: () => [] },
});

const formatDateTime = (value) => new Date(value).toLocaleString('en-IN');

const closeYear = async (year) => {
    const confirmed = await askKeyboardConfirm(
        `Close ${year.name}?\n\nNo vouchers can be posted, reversed or changed in this year, and documents dated in it cannot be edited until it is reopened.`
    );
    if (!confirmed) return;

    router.post(`/accounting/financial-years/${year.id}/close`, {}, { preserveScroll: true });
};

const reopenYear = async (year) => {
    const confirmed = await askKeyboardConfirm(`Reopen ${year.name}?`);
    if (!confirmed) return;

    router.post(`/accounting/financial-years/${year.id}/reopen`, {}, { preserveScroll: true });
};
</script>

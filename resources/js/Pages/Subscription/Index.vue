<template>
    <AdminLayout title="Subscriptions">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Subscriptions</h4>
                <p class="text-muted mb-0">Manage plans, renewals, and add-ons across your companies.</p>
            </div>
            <Link href="/subscription/history" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-receipt me-1"></i> Payment history
            </Link>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Active plans</div>
                        <div class="fs-3 fw-bold text-success">{{ summary.active }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Needs attention</div>
                        <div class="fs-3 fw-bold text-danger">{{ summary.expired }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total companies</div>
                        <div class="fs-3 fw-bold">{{ summary.total }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Company subscriptions</h3>
            </div>
            <div class="card-body p-0">
                <div class="d-sm-none">
                    <div
                        v-for="company in companies"
                        :key="company.id"
                        class="px-3 py-2 border-bottom"
                    >
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="fw-bold text-truncate">{{ company.name }}</div>
                            <div class="d-flex align-items-center gap-1 ms-2">
                                <StatusBadge :status="company.subscription?.display_status ?? 'none'" />
                                <Link
                                    :href="`/subscription/manage?company_id=${company.id}`"
                                    class="btn btn-sm btn-outline-primary py-0 px-2"
                                    title="Manage Subscription"
                                >
                                    <i class="fas fa-cog small"></i>
                                </Link>
                            </div>
                        </div>
                        <div class="small text-muted mt-1" v-if="company.subscription?.ends_on">
                            Valid until: <span class="text-dark">{{ formatDate(company.subscription?.ends_on) }}</span>
                        </div>
                    </div>

                    <div
                        v-if="companies.length === 0"
                        class="text-center text-muted py-4 small"
                    >
                        No companies found.
                    </div>
                </div>

                <div class="table-responsive d-none d-sm-block">
                    <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Valid until</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="company in companies" :key="company.id">
                            <td>{{ company.name }}</td>
                            <td>
                                <StatusBadge :status="company.subscription?.display_status ?? 'none'" />
                            </td>
                            <td>{{ formatDate(company.subscription?.ends_on) }}</td>
                            <td class="text-end">
                                <Link
                                    :href="`/subscription/manage?company_id=${company.id}`"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    Manage <i class="fas fa-arrow-right ms-1"></i>
                                </Link>
                            </td>
                        </tr>
                            <tr v-if="companies.length === 0">
                                <td colspan="4" class="text-center py-4 text-muted">No companies found.</td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/Subscription/StatusBadge.vue';

defineProps({
    companies: {
        type: Array,
        required: true,
    },
    summary: {
        type: Object,
        required: true,
    },
});

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

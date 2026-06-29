<template>
    <AdminLayout :title="company.name">
        <div v-if="!catalogAvailable" class="alert alert-warning">
            Unable to load catalog pricing from admin. Plan details below reflect your subscription record only.
        </div>

        <Link href="/subscription" class="btn btn-link mb-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> All companies
        </Link>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">{{ company.name }}</h4>
                <p class="text-muted mb-0">Current plan details, usage, and purchase history.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <StatusBadge :status="subscription?.display_status ?? 'none'" />
                <Link
                    :href="`/subscription/checkout?company_id=${company.id}&purchase_type=renewal`"
                    class="btn btn-primary btn-sm"
                >
                    <i class="fas fa-sync-alt me-1"></i> Renew
                </Link>
                <Link
                    v-if="!isExpired"
                    :href="`/subscription/checkout?company_id=${company.id}&purchase_type=addon`"
                    class="btn btn-outline-secondary btn-sm"
                >
                    <i class="fas fa-plus-circle me-1"></i> Add-ons
                </Link>
                <button
                    v-else
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    disabled
                    title="Add-ons require an active subscription"
                >
                    <i class="fas fa-plus-circle me-1"></i> Add-ons
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <div class="small opacity-75">Current plan</div>
                        <h5 class="mb-1">{{ productName }}</h5>
                        <div class="small opacity-75">
                            {{ formatDate(plan.starts_on) }} – {{ formatDate(plan.ends_on) }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6" v-for="item in planItems" :key="item.label">
                                <div class="text-muted small">{{ item.label }}</div>
                                <div class="fw-semibold">{{ item.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Live usage</h5>
                    </div>
                    <div class="card-body">
                        <UsageBar v-for="metric in usageMetrics" :key="metric.label" :metric="metric" />
                    </div>
                </div>
            </div>

            <div v-if="upcoming" class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upcoming renewal</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Paid renewal queued to activate automatically when the current plan ends
                            <span v-if="plan.ends_on">({{ formatDate(plan.ends_on) }})</span>.
                            The new term will run for one year from that date.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Branches</div>
                                <div class="fw-semibold">{{ upcoming.branches }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Add-on users</div>
                                <div class="fw-semibold">{{ upcoming.addon_users }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Purchase history</h5>
                        <Link
                            :href="`/subscription/history?company_id=${company.id}`"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            View all
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in payments" :key="payment.id">
                                    <td>{{ formatDate(payment.created_at) }}</td>
                                    <td class="text-capitalize">{{ payment.type.replace(/_/g, ' ') }}</td>
                                    <td class="text-end fw-semibold">₹{{ formatMoney(payment.total_amount) }}</td>
                                </tr>
                                <tr v-if="payments.length === 0">
                                    <td colspan="3" class="text-center py-4 text-muted">No completed payments yet.</td>
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
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/Subscription/StatusBadge.vue';
import UsageBar from '@/Components/Subscription/UsageBar.vue';

const props = defineProps({
    company: Object,
    subscription: Object,
    isExpired: Boolean,
    catalogAvailable: Boolean,
    productName: String,
    plan: Object,
    usageMetrics: Array,
    payments: Array,
    upcoming: Object,
});

const planItems = computed(() => [
    { label: 'Included branches', value: props.plan.included_branches },
    { label: 'Add-on branches', value: props.plan.addon_branches },
    { label: 'Users per branch', value: props.plan.users_per_branch },
    { label: 'Add-on users', value: props.plan.addon_users },
]);

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatNumber(value) {
    return Number(value).toLocaleString();
}

function formatMoney(value) {
    return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

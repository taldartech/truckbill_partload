<template>
    <AdminLayout title="Payment history">
        <Link href="/subscription" class="btn btn-link mb-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> Subscriptions
        </Link>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Payment history</h4>
                <p class="text-muted mb-0">Review past subscription payments and their status.</p>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2">
                ₹{{ formatMoney(totalPaid) }} total
            </span>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">All payments</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in payments.data" :key="payment.id">
                            <td>{{ formatDate(payment.created_at) }}</td>
                            <td>{{ payment.company?.name ?? '—' }}</td>
                            <td class="text-capitalize">{{ payment.type.replace(/_/g, ' ') }}</td>
                            <td>
                                <StatusBadge :status="paymentStatus(payment.status)" />
                            </td>
                            <td class="text-end fw-semibold">₹{{ formatMoney(payment.total_amount) }}</td>
                        </tr>
                        <tr v-if="payments.data.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="payments.links?.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            v-for="link in payments.links"
                            :key="link.label"
                            class="page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link
                                v-if="link.url"
                                class="page-link"
                                :href="link.url"
                                v-html="link.label"
                            />
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/Subscription/StatusBadge.vue';

defineProps({
    payments: Object,
    companyId: Number,
    totalPaid: Number,
});

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatMoney(value) {
    return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paymentStatus(status) {
    if (['paid', 'success', 'completed'].includes(status)) {
        return 'active';
    }

    if (['failed', 'cancelled'].includes(status)) {
        return 'expired';
    }

    return 'none';
}
</script>

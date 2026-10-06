<template>
    <AdminLayout title="Accounting Health">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title m-0"><i class="fas fa-heartbeat me-2"></i>Accounting Health</h3>
                        <span v-if="report" class="badge fs-6" :class="report.total ? 'bg-danger' : 'bg-success'">
                            {{ report.total ? `${report.total} issue(s)` : 'All checks passed' }}
                        </span>
                    </div>
                    <div class="card-body small text-muted">
                        Compares every business document with the ledger. Fix the source document where possible;
                        an administrator can repost in bulk with <code>php artisan accounting:reconcile --company={{ report?.company_id }}</code>
                        (dry run first, then <code>--commit</code>).
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!report" class="alert alert-warning">Select a company to run the accounting checks.</div>

        <div v-else class="row">
            <div v-for="(check, key) in report.checks" :key="key" class="col-12 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fs-6 fw-bold">
                            <i class="fas me-2" :class="check.count ? 'fa-exclamation-triangle text-danger' : 'fa-check-circle text-success'"></i>
                            {{ check.label }}
                        </h4>
                        <span class="badge" :class="check.count ? 'bg-danger' : 'bg-success'">{{ check.count }}</span>
                    </div>
                    <div v-if="check.items.length" class="card-body p-0 table-responsive">
                        <table class="table table-sm table-bordered m-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Document</th>
                                    <th>Event</th>
                                    <th>Voucher</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in check.items" :key="index">
                                    <td>
                                        <template v-if="item.module">
                                            {{ item.module }}
                                            <Link v-if="item.url" :href="item.url" class="text-decoration-none">{{ item.number }}</Link>
                                            <span v-else>{{ item.number }}</span>
                                        </template>
                                        <span v-else>—</span>
                                    </td>
                                    <td>{{ item.event || '—' }}</td>
                                    <td>
                                        <Link v-if="item.voucher_id" :href="`/accounting/vouchers/${item.voucher_id}`" class="text-decoration-none">
                                            {{ item.voucher_number || `#${item.voucher_id}` }}
                                        </Link>
                                        <span v-else-if="item.voucher_numbers">{{ item.voucher_numbers.join(', ') }}</span>
                                        <span v-else>—</span>
                                    </td>
                                    <td>{{ item.detail || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="check.count > check.items.length" class="p-2 small text-muted">
                            Showing {{ check.items.length }} of {{ check.count }}. Run <code>php artisan accounting:audit</code> for the full list.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    report: { type: Object, default: null },
});
</script>

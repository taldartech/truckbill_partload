<template>
    <AdminLayout title="Receivables">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hand-holding-usd me-2"></i>Receivables</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.start_date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.end_date">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success btn-sm w-100">Refresh</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="m-0 fw-bold">Outstanding Receivables</h4>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Opening ₹</th>
                                    <th class="text-end">Debit ₹</th>
                                    <th class="text-end">Credit ₹</th>
                                    <th class="text-end">Outstanding ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${row.account_id}`" class="text-decoration-none fw-semibold">
                                            {{ row.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(row.opening).toFixed(2) }}</td>
                                    <td class="text-end text-danger">{{ Number(row.debit).toFixed(2) }}</td>
                                    <td class="text-end text-success">{{ Number(row.credit).toFixed(2) }}</td>
                                    <td class="text-end fw-bold">{{ Number(row.outstanding).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!rows?.length">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No outstanding receivables for the selected period.
                                    </td>
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
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ rows: Array, filters: Object });
const form = useForm({
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
});
function submit() {
    form.get('/accounting/reports/receivables', { preserveState: true });
}
</script>

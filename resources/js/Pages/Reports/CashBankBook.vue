<template>
    <AdminLayout title="Cash / Bank Book">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-wallet me-2"></i>Cash / Bank Book</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="filterReports" class="row">
                            <div class="col-md-4">
                                <label class="form-label">Account <span class="text-danger">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.account"
                                    :options="accounts"
                                    placeholder="-- Select account --"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input
                                    type="date"
                                    class="form-control form-control-sm"
                                    v-model="form.start_date"
                                    @change="onDateChange('start_date')"
                                >
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input
                                    type="date"
                                    class="form-control form-control-sm"
                                    v-model="form.end_date"
                                    @change="onDateChange('end_date')"
                                >
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-sm w-100" :disabled="!form.account">Get Book</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="account">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">{{ account }} - Book</h4>
                        <span class="badge bg-primary fs-6">Closing: ₹ {{ Math.abs(currentBalance).toFixed(2) }} {{ currentBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Ref No.</th>
                                    <th>Particulars</th>
                                    <th class="text-end">Debit (Dr) ₹</th>
                                    <th class="text-end">Credit (Cr) ₹</th>
                                    <th class="text-end">Balance ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in ledger" :key="index">
                                    <td>{{ entry.date }}</td>
                                    <td><span class="badge bg-secondary">{{ entry.type }}</span></td>
                                    <td>{{ entry.ref_no || '—' }}</td>
                                    <td>{{ entry.description || '—' }}</td>
                                    <td class="text-end text-danger">{{ entry.debit > 0 ? Number(entry.debit).toFixed(2) : '-' }}</td>
                                    <td class="text-end text-success">{{ entry.credit > 0 ? Number(entry.credit).toFixed(2) : '-' }}</td>
                                    <td class="text-end fw-bold">{{ Number(entry.balance).toFixed(2) }} {{ entry.balance >= 0 ? 'Dr' : 'Cr' }}</td>
                                </tr>
                                <tr v-if="ledger.length === 0">
                                    <td colspan="7" class="text-center py-5 text-muted">No transactions found.</td>
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { clampLedgerDateRange, thisMonthDateRange } from '@/utils/ledgerDateRange';

const props = defineProps({
    accounts: Array,
    account: String,
    ledger: Array,
    filters: Object,
});

const defaults = thisMonthDateRange();

const form = useForm({
    account: props.filters?.account || '',
    start_date: props.filters?.start_date || defaults.start_date,
    end_date: props.filters?.end_date || defaults.end_date,
});

const onDateChange = (field) => {
    clampLedgerDateRange(form, field);
};

const filterReports = () => {
    clampLedgerDateRange(form, 'end_date');
    form.get('/reports/cash-bank-book', { preserveState: true });
};

const currentBalance = computed(() => {
    return props.ledger?.length ? Number(props.ledger[0].balance || 0) : 0;
});
</script>

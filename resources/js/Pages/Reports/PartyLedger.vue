<template>
    <AdminLayout title="Party Ledger">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book me-2"></i>Party Ledger (Statement of Account)</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="filterReports" class="row">
                            <div class="col-md-4">
                                <label class="form-label">Select Party <span class="text-danger">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.party_id"
                                    :options="parties"
                                    placeholder="-- Select Party --"
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
                                <button type="submit" class="btn btn-success btn-sm w-100" :disabled="!form.party_id">Get Ledger</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="party">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">{{ party.name }} - Ledger</h4>
                        <div>
                            <span class="badge bg-primary fs-6">Net Balance: ₹ {{ Math.abs(currentBalance).toFixed(2) }} {{ currentBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Ref No.</th>
                                    <th>Particulars (Description)</th>
                                    <th class="text-end">Debit (Dr) ₹</th>
                                    <th class="text-end">Credit (Cr) ₹</th>
                                    <th class="text-end">Balance ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in ledger" :key="index">
                                    <td class="text-nowrap">{{ entry.date }}</td>
                                    <td>
                                        <span class="badge" :class="entry.type === 'Invoice' ? 'bg-primary' : 'bg-success'">{{ entry.type }}</span>
                                    </td>
                                    <td>{{ entry.ref_no || '—' }}</td>
                                    <td class="text-nowrap">{{ entry.description || '—' }}</td>
                                    <td class="text-end text-danger">{{ entry.debit > 0 ? Number(entry.debit).toFixed(2) : '-' }}</td>
                                    <td class="text-end text-success">{{ entry.credit > 0 ? Number(entry.credit).toFixed(2) : '-' }}</td>
                                    <td class="text-end fw-bold text-nowrap">{{ Number(entry.balance).toFixed(2) }} {{ entry.balance >= 0 ? 'Dr' : 'Cr' }}</td>
                                </tr>
                                <tr v-if="ledger.length === 0">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No transactions found for this party in the selected date range.
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { clampLedgerDateRange, thisMonthDateRange } from '@/utils/ledgerDateRange';

const props = defineProps({
    parties: Array,
    party: Object,
    ledger: Array,
    filters: Object,
});

const defaults = thisMonthDateRange();

const form = useForm({
    party_id: props.filters?.party_id || '',
    start_date: props.filters?.start_date || defaults.start_date,
    end_date: props.filters?.end_date || defaults.end_date,
});

const onDateChange = (field) => {
    clampLedgerDateRange(form, field);
};

const filterReports = () => {
    clampLedgerDateRange(form, 'end_date');
    form.get('/reports/party-ledger', {
        preserveState: true,
    });
};

const currentBalance = computed(() => {
    if (!props.ledger?.length) {
        return 0;
    }

    // Latest entry is first after server reverse.
    return Number(props.ledger[0].balance || 0);
});
</script>

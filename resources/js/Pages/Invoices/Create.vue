<template>
    <AdminLayout title="Create Invoice">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Invoice</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Invoice No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.invoice_no" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="form.date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Party (Bill To) <span class="text-danger">*</span></label>
                                    <SearchableSelect
    v-model="form.party_id"
    :options="parties"
    placeholder="Select Party"
/>
                                </div>
                            </div>
                            
                            <hr>
                            <h5 class="text-primary mb-3">Select Bilties to Invoice</h5>
                            <div style="max-height: 300px; overflow-y: auto;" class="border rounded p-2 mb-3 bg-light">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" @change="toggleAll"></th>
                                            <th>Bilty No</th>
                                            <th>Date</th>
                                            <th>From -> To</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="bilty in bilties" :key="bilty.id">
                                            <td><input type="checkbox" :value="bilty.id" v-model="form.bilty_ids"></td>
                                            <td>{{ bilty.bilty_number }}</td>
                                            <td>{{ bilty.date }}</td>
                                            <td>{{ bilty.from_station?.name }} -> {{ bilty.to_station?.name }}</td>
                                            <td>₹ {{ bilty.amount }}</td>
                                        </tr>
                                        <tr v-if="bilties.length === 0">
                                            <td colspan="5" class="text-center text-muted">No pending bilties found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" v-model="form.status">
                                        <option value="Unpaid">Unpaid</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Remark</label>
                                    <input type="text" class="form-control" v-model="form.remark">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/invoices" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing || form.bilty_ids.length === 0">Generate Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    parties: Array,
    bilties: Array,
    next_invoice_no: {
        type: String,
        default: '',
    },
});

const form = useForm({
    invoice_no: props.next_invoice_no || '',
    date: new Date().toISOString().substr(0, 10),
    party_id: '',
    status: 'Unpaid',
    remark: '',
    bilty_ids: []
});

const toggleAll = (e) => {
    if(e.target.checked) {
        form.bilty_ids = props.bilties.map(b => b.id);
    } else {
        form.bilty_ids = [];
    }
};

const submit = () => {
    form.post('/invoices');
};
</script>

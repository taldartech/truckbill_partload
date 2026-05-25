<template>
    <AdminLayout title="Edit Invoice">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card card-warning">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Edit Invoice #{{ invoice.invoice_no }}</h3>
                        <a :href="`/invoices/${invoice.id}/print`" target="_blank" class="btn btn-sm btn-dark">
                            <i class="fas fa-print me-1"></i> Print
                        </a>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Invoice No.</label>
                                    <input type="text" class="form-control" v-model="form.invoice_no" readonly>
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
                            <h5 class="text-warning mb-3">Invoiced Bilties</h5>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Bilty No</th>
                                            <th>Date</th>
                                            <th>Route</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="detail in invoice.details" :key="detail.id">
                                            <td>{{ detail.bilty?.bilty_number }}</td>
                                            <td>{{ detail.bilty?.date }}</td>
                                            <td>{{ detail.bilty?.from_station?.name }} -> {{ detail.bilty?.to_station?.name }}</td>
                                            <td class="text-end fw-bold">₹ {{ detail.amount }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total Amount:</td>
                                            <td class="text-end fw-bold text-success">₹ {{ invoice.net_amount }}</td>
                                        </tr>
                                    </tfoot>
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
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">Update Invoice</button>
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
    invoice: Object,
    parties: Array
});

const form = useForm({
    invoice_no: props.invoice.invoice_no,
    date: props.invoice.date,
    party_id: props.invoice.party_id,
    status: props.invoice.status,
    remark: props.invoice.remark || ''
});

const submit = () => {
    form.put(`/invoices/${props.invoice.id}`);
};
</script>

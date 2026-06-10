<template>
    <AdminLayout title="Add Voucher">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create Voucher</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="form.date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Voucher No.</label>
                                    <input type="text" class="form-control" v-model="form.voucher_number">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Voucher Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.voucher_type" required>
                                        <option value="Payment">Payment</option>
                                        <option value="Receipt">Receipt</option>
                                        <option value="Journal">Journal</option>
                                        <option value="Contra">Contra</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Head <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.account_head" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.debit_credit" required>
                                        <option value="Dr">Dr (Debit)</option>
                                        <option value="Cr">Cr (Credit)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.amount" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Remark</label>
                                <textarea class="form-control" rows="3" v-model="form.remark"></textarea>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/vouchers" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    voucher_number: '',
    voucher_type: 'Payment',
    account_head: '',
    debit_credit: 'Dr',
    amount: '',
    remark: ''
});

const submit = () => {
    form.post('/vouchers');
};
</script>

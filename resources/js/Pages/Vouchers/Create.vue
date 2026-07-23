<template>
    <AdminLayout title="Add Voucher">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Voucher Details -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Voucher Details</h2>
                                <p class="bilty-section__desc">Type, account and amount</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date</span>
                                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.date }" v-model="form.date" required>
                                    <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}</div>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Voucher No.</span>
                                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.voucher_number }" v-model="form.voucher_number">
                                    <div v-if="form.errors.voucher_number" class="text-danger small mt-1">{{ form.errors.voucher_number }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Voucher Type<span class="bilty-field__req">*</span></label>
                                <select class="form-select form-select-sm" :class="{ 'is-invalid': form.errors.voucher_type }" v-model="form.voucher_type" required>
                                    <option value="Payment">Payment</option>
                                    <option value="Receipt">Receipt</option>
                                    <option value="Journal">Journal</option>
                                    <option value="Contra">Contra</option>
                                </select>
                                <div v-if="form.errors.voucher_type" class="text-danger small mt-1">{{ form.errors.voucher_type }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Account Head<span class="bilty-field__req">*</span></label>
                                <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.account_head }" v-model="form.account_head" required>
                                <div v-if="form.errors.account_head" class="text-danger small mt-1">{{ form.errors.account_head }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Dr / Cr<span class="bilty-field__req">*</span></label>
                                <select class="form-select form-select-sm" :class="{ 'is-invalid': form.errors.debit_credit }" v-model="form.debit_credit" required>
                                    <option value="Dr">Dr (Debit)</option>
                                    <option value="Cr">Cr (Credit)</option>
                                </select>
                                <div v-if="form.errors.debit_credit" class="text-danger small mt-1">{{ form.errors.debit_credit }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Amount<span class="bilty-field__req">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.amount }" v-model="form.amount" required>
                                <div v-if="form.errors.amount" class="text-danger small mt-1">{{ form.errors.amount }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Remark -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">2</div>
                            <div>
                                <h2 class="bilty-section__title">Remark</h2>
                                <p class="bilty-section__desc">Optional notes for this voucher</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-field">
                            <label class="bilty-field__label">Remark</label>
                            <textarea class="form-control form-control-sm" rows="3" :class="{ 'is-invalid': form.errors.remark }" v-model="form.remark"></textarea>
                            <div v-if="form.errors.remark" class="text-danger small mt-1">{{ form.errors.remark }}</div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/vouchers" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
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

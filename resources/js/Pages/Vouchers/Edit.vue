<template>
    <AdminLayout title="Edit Voucher">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Voucher Details</h2>
                                <p class="bilty-section__desc">Receipt or payment against party, vendor or agent</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date</span>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Voucher No.<span class="bilty-field__req">*</span></span>
                                    <input type="text" class="form-control form-control-sm" v-model="form.voucher_number" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Voucher Type<span class="bilty-field__req">*</span></label>
                                <select class="form-select form-select-sm" v-model="form.voucher_type" required>
                                    <option value="Payment">Payment</option>
                                    <option value="Receipt">Receipt</option>
                                </select>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Account Type<span class="bilty-field__req">*</span></label>
                                <select class="form-select form-select-sm" v-model="form.accountable_type" required @change="form.accountable_id = ''">
                                    <option value="party">Party</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="agent">Agent</option>
                                </select>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Account<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    v-model="form.accountable_id"
                                    :options="entityOptions"
                                    placeholder="Select account"
                                />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Amount<span class="bilty-field__req">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.amount" required>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Mode<span class="bilty-field__req">*</span></label>
                                <select class="form-select form-select-sm" v-model="form.mode" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>
                            <div v-if="form.mode === 'bank'" class="bilty-field">
                                <label class="bilty-field__label">Bank Account<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    v-model="form.bank_account_id"
                                    :options="bankAccountOptions"
                                    placeholder="Select bank account"
                                />
                            </div>
                        </div>

                        <div v-if="outstanding !== null" class="alert alert-light border mt-3 mb-0 py-2">
                            Outstanding balance: <strong>₹ {{ formatMoney(outstanding) }}</strong>
                        </div>
                        <div v-if="exceedsOutstanding" class="alert alert-warning mt-2 mb-0 py-2">
                            Amount exceeds outstanding balance. You can still save; the difference will become advance.
                        </div>
                    </div>
                </section>

                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">2</div>
                            <div>
                                <h2 class="bilty-section__title">Remark</h2>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <textarea class="form-control form-control-sm" rows="3" v-model="form.remark"></textarea>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/vouchers" class="btn btn-outline-secondary btn-sm px-3">Cancel</Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">Update</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link, useHttp } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    voucher: Object,
    parties: Array,
    vendors: Array,
    agents: Array,
    bankAccounts: Array,
});

const http = useHttp({
    accountable_type: '',
    accountable_id: '',
});
const outstanding = ref(null);

const form = useForm({
    date: props.voucher.date || new Date().toISOString().substr(0, 10),
    voucher_number: props.voucher.voucher_number || '',
    voucher_type: props.voucher.voucher_type || 'Payment',
    accountable_type: props.voucher.accountable_type || 'party',
    accountable_id: props.voucher.accountable_id || '',
    amount: props.voucher.amount || '',
    mode: props.voucher.mode || 'cash',
    bank_account_id: props.voucher.bank_account_id || '',
    remark: props.voucher.remark || '',
});

const entityOptions = computed(() => {
    if (form.accountable_type === 'vendor') return props.vendors || [];
    if (form.accountable_type === 'agent') return props.agents || [];
    return props.parties || [];
});

const bankAccountOptions = computed(() => (props.bankAccounts || []).map((b) => ({
    id: b.id,
    name: b.bank_name ? `${b.name} (${b.bank_name})` : b.name,
})));

const exceedsOutstanding = computed(() => {
    const amount = parseFloat(form.amount || 0);
    if (!amount || outstanding.value === null) return false;
    return amount > Number(outstanding.value);
});

const formatMoney = (value) => Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const loadOutstanding = () => {
    outstanding.value = null;
    if (!form.accountable_type || !form.accountable_id) return;

    http.accountable_type = form.accountable_type;
    http.accountable_id = form.accountable_id;
    http.get('/vouchers/entity-balance', {
        onSuccess: (response) => {
            outstanding.value = response.outstanding ?? null;
        },
        onError: () => {
            outstanding.value = null;
        },
    });
};

watch(() => [form.accountable_type, form.accountable_id], loadOutstanding, { immediate: true });

const submit = () => {
    if (form.mode !== 'bank') {
        form.bank_account_id = '';
    }
    form.put(`/vouchers/${props.voucher.id}`);
};
</script>

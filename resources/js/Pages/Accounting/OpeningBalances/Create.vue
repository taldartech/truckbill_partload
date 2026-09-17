<template>
    <AdminLayout title="Opening Balances">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Opening Balances</h2>
                                <p class="bilty-section__desc">
                                    {{ financialYear ? `${financialYear.name} · as on year start` : 'Post opening balances for the financial year' }}
                                </p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">As on date<span class="bilty-field__req">*</span></span>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-field">
                            <label class="bilty-field__label">Narration</label>
                            <input type="text" class="form-control form-control-sm" v-model="form.narration">
                        </div>
                    </div>
                </section>

                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Balance Lines</h2>
                                <p class="bilty-section__desc mb-0">
                                    Debits must equal credits
                                    <span class="ms-2 fw-semibold" :class="balanced ? 'text-success' : 'text-danger'">
                                        ({{ balanced ? 'Balanced' : 'Unbalanced' }}
                                        · Dr {{ totalDebit.toFixed(2) }} / Cr {{ totalCredit.toFixed(2) }})
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="4%" class="text-center">S No.</th>
                                        <th>Account<span class="bilty-field__req">*</span></th>
                                        <th width="18%" class="text-center">Debit ₹</th>
                                        <th width="18%" class="text-center">Credit ₹</th>
                                        <th width="6%" class="text-center"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(entry, i) in form.entries" :key="i" class="bg-white">
                                        <td class="text-center align-middle">{{ i + 1 }}</td>
                                        <td>
                                            <AccountSelect v-model="entry.account_id" :accounts="accounts" />
                                        </td>
                                        <td>
                                            <input
                                                v-model="entry.debit"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="form-control form-control-sm text-center"
                                                @input="onDebitInput(entry)"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                v-model="entry.credit"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="form-control form-control-sm text-center"
                                                @input="onCreditInput(entry)"
                                            >
                                        </td>
                                        <td class="text-center align-middle">
                                            <button
                                                v-if="form.entries.length > 2"
                                                type="button"
                                                class="btn btn-sm btn-link text-danger p-0 bg-transparent"
                                                title="Remove row"
                                                @click="removeLine(i)"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button
                                                v-else
                                                type="button"
                                                class="btn btn-sm btn-link text-secondary p-0 bg-transparent"
                                                title="Reset row"
                                                @click="resetLine(i)"
                                            >
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light fw-bold">
                                        <td></td>
                                        <td class="align-middle">Total</td>
                                        <td class="text-center align-middle">{{ totalDebit.toFixed(2) }}</td>
                                        <td class="text-center align-middle">{{ totalCredit.toFixed(2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="text-center bg-light p-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addLine">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/accounting/accounts" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="!balanced || form.processing">
                        <i class="fas fa-save me-1"></i> Post Opening Voucher
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AccountSelect from '@/Components/Accounting/AccountSelect.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    financialYear: Object,
    accounts: Array,
});

const emptyLine = () => ({ account_id: '', debit: '', credit: '' });

const form = useForm({
    date: props.financialYear?.starts_on || new Date().toISOString().slice(0, 10),
    narration: 'Opening balances',
    entries: [emptyLine(), emptyLine()],
});

const totalDebit = computed(() => form.entries.reduce((s, e) => s + (parseFloat(e.debit) || 0), 0));
const totalCredit = computed(() => form.entries.reduce((s, e) => s + (parseFloat(e.credit) || 0), 0));
const balanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.005 && totalDebit.value > 0);

function onDebitInput(entry) {
    if (parseFloat(entry.debit) > 0) {
        entry.credit = '';
    }
}

function onCreditInput(entry) {
    if (parseFloat(entry.credit) > 0) {
        entry.debit = '';
    }
}

function addLine() {
    form.entries.push(emptyLine());
}

function removeLine(index) {
    if (form.entries.length > 2) {
        form.entries.splice(index, 1);
    }
}

function resetLine(index) {
    form.entries[index] = emptyLine();
}

function submit() {
    form.post('/accounting/opening-balances');
}
</script>

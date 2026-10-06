<template>
    <ShowSection title="Accounting" hint="Vouchers posted from this document" flush>
        <div v-if="!vouchers.length" class="p-3 small text-muted">
            No accounting entries are posted for this document.
        </div>
        <div v-else class="table-responsive">
            <table class="table table-bordered table-sm m-0 table-grid">
                <thead class="bg-light">
                    <tr>
                        <th>Voucher</th>
                        <th>Event</th>
                        <th>Account</th>
                        <th class="text-end">Debit ₹</th>
                        <th class="text-end">Credit ₹</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="voucher in vouchers" :key="voucher.id">
                        <tr
                            v-for="(entry, index) in voucher.entries"
                            :key="`${voucher.id}-${index}`"
                            class="bg-white"
                            :class="{ 'text-muted': voucher.state !== 'active' }"
                        >
                            <td v-if="index === 0" :rowspan="voucher.entries.length" class="align-top">
                                <Link
                                    :href="`/accounting/vouchers/${voucher.id}`"
                                    class="fw-semibold text-brand-primary text-decoration-none"
                                >{{ voucher.voucher_number }}</Link>
                                <div class="small">{{ voucher.date }} · {{ voucher.voucher_type }}</div>
                                <span class="badge" :class="stateClass(voucher.state)">{{ stateLabel(voucher.state) }}</span>
                            </td>
                            <td v-if="index === 0" :rowspan="voucher.entries.length" class="align-top small">
                                {{ voucher.event_label || '—' }}
                                <div v-if="voucher.created_by" class="text-muted">by {{ voucher.created_by }}</div>
                            </td>
                            <td>{{ entry.account || '—' }}</td>
                            <td class="text-end">{{ entry.debit > 0 ? entry.debit.toFixed(2) : '—' }}</td>
                            <td class="text-end">{{ entry.credit > 0 ? entry.credit.toFixed(2) : '—' }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </ShowSection>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ShowSection from '@/Components/Show/ShowSection.vue';

defineProps({
    vouchers: { type: Array, default: () => [] },
});

const labels = {
    active: 'Active',
    reversed: 'Reversed',
    reversal: 'Reversal',
    cancelled: 'Cancelled',
    unlinked: 'Superseded',
};

const stateLabel = (state) => labels[state] || state;
const stateClass = (state) => (state === 'active' ? 'bg-success' : 'bg-secondary');
</script>

<template>
    <AdminLayout :title="`Voucher ${voucher.voucher_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Accounting Voucher"
                        :identity="voucher.voucher_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/accounting/vouchers"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Vouchers"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <button
                                v-if="can('edit accounting_vouchers') && !isSystemGenerated && voucher.status === 'posted'"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Cancel Voucher"
                                @click="cancelVoucher"
                            >
                                <i class="fas fa-ban" aria-hidden="true"></i>
                                <span class="visually-hidden">Cancel</span>
                            </button>
                            <button
                                v-if="can('edit accounting_vouchers') && !isSystemGenerated && canReverse"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Reverse Voucher"
                                @click="reverseVoucher"
                            >
                                <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                                <span class="visually-hidden">Reverse</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div v-if="source" class="alert alert-light border small py-2 px-3 mt-2 mb-0">
                            <i class="fas fa-link me-1" aria-hidden="true"></i>
                            Posted automatically from
                            <strong>{{ source.module }}</strong>
                            <Link v-if="source.url" :href="source.url" class="fw-semibold text-brand-primary text-decoration-none">
                                {{ source.number }}
                            </Link>
                            <span v-else class="fw-semibold">{{ source.number }}</span>
                            <span v-if="source.deleted" class="badge bg-secondary ms-1">Deleted</span>
                            <span v-else-if="source.inactive" class="badge bg-secondary ms-1">Cancelled</span>
                            <span v-if="eventLabel"> · {{ eventLabel }}</span>.
                            Edit or delete the document to change this entry.
                        </div>
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span
                                class="badge app-show-status-row__badge"
                                :class="voucher.status === 'posted' ? 'bg-success' : 'bg-secondary'"
                            >
                                {{ voucher.status }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Entries">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Account</th>
                                        <th>Group</th>
                                        <th class="text-end">Debit ₹</th>
                                        <th class="text-end">Credit ₹</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="entry in voucher.entries" :key="entry.id" class="bg-white">
                                        <td>
                                            <Link
                                                :href="`/accounting/reports/ledger?account_id=${entry.account_id}`"
                                                class="text-decoration-none fw-semibold text-brand-primary"
                                            >
                                                {{ entry.account?.name }}
                                            </Link>
                                            <div v-if="entry.narration" class="small text-muted">{{ entry.narration }}</div>
                                        </td>
                                        <td>{{ entry.account?.account_group?.name || '—' }}</td>
                                        <td class="text-end text-danger">{{ Number(entry.debit) > 0 ? Number(entry.debit).toFixed(2) : '—' }}</td>
                                        <td class="text-end text-success">{{ Number(entry.credit) > 0 ? Number(entry.credit).toFixed(2) : '—' }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light fw-bold">
                                        <td colspan="2">Total</td>
                                        <td class="text-end">{{ totalDebit.toFixed(2) }}</td>
                                        <td class="text-end">{{ totalCredit.toFixed(2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Record Info">
                        <div class="app-show-meta-list">
                            <div
                                v-for="item in recordInfoItems"
                                :key="item.label"
                                class="app-show-meta-list__item"
                            >
                                <span class="app-show-meta-list__label">{{ item.label }}</span>
                                <span class="app-show-meta-list__value">{{ item.value }}</span>
                            </div>
                            <div class="app-show-meta-list__item">
                                <span class="app-show-meta-list__label">Reverses</span>
                                <span class="app-show-meta-list__value">
                                    <Link
                                        v-if="voucher.reversed_voucher"
                                        :href="`/accounting/vouchers/${voucher.reversed_voucher.id}`"
                                        class="text-brand-primary text-decoration-none"
                                    >{{ voucher.reversed_voucher.voucher_number }}</Link>
                                    <template v-else>—</template>
                                </span>
                            </div>
                            <div class="app-show-meta-list__item">
                                <span class="app-show-meta-list__label">Reversed in</span>
                                <span class="app-show-meta-list__value">
                                    <template v-if="voucher.reversals?.length">
                                        <Link
                                            v-for="reversal in voucher.reversals"
                                            :key="reversal.id"
                                            :href="`/accounting/vouchers/${reversal.id}`"
                                            class="text-brand-primary text-decoration-none me-1"
                                        >{{ reversal.voucher_number }}</Link>
                                    </template>
                                    <template v-else>—</template>
                                </span>
                            </div>
                        </div>
                    </ShowSection>
                </ShowSidebar>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowFieldGrid from '@/Components/Show/ShowFieldGrid.vue';
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { formatDateTime } from '@/utils/showFormatters';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const props = defineProps({
    voucher: { type: Object, required: true },
    source: { type: Object, default: null },
    eventLabel: { type: String, default: null },
    isSystemGenerated: { type: Boolean, default: false },
    canReverse: { type: Boolean, default: false },
});

const totalDebit = computed(() =>
    (props.voucher.entries || []).reduce((s, e) => s + (Number(e.debit) || 0), 0),
);
const totalCredit = computed(() =>
    (props.voucher.entries || []).reduce((s, e) => s + (Number(e.credit) || 0), 0),
);

const overviewFields = computed(() => [
    { label: 'Date', value: props.voucher.date || '—' },
    { label: 'Type', value: props.voucher.voucher_type || '—' },
    { label: 'Narration', value: props.voucher.narration || '—' },
    { label: 'Origin', value: props.source ? `${props.source.module} ${props.source.number}` : (props.eventLabel || 'Manual entry') },
]);

const recordInfoItems = computed(() => [
    { label: 'Posted by', value: props.voucher.created_by?.name || '—' },
    { label: 'Posted at', value: formatDateTime(props.voucher.created_at) },
    { label: 'Updated by', value: props.voucher.updated_by?.name || '—' },
    { label: 'Reversed by', value: props.voucher.reversed_by?.name || '—' },
    { label: 'Reversed at', value: props.voucher.reversed_at ? formatDateTime(props.voucher.reversed_at) : '—' },
    { label: 'Cancelled by', value: props.voucher.cancelled_by?.name || '—' },
    { label: 'Cancel reason', value: props.voucher.cancel_reason || '—' },
]);

const cancelVoucher = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Cancel this voucher?\n\nVoucher No : ${props.voucher.voucher_number}`
    );
    if (!remark) return;
    router.post(`/accounting/vouchers/${props.voucher.id}/cancel`, { reason: remark });
};

const reverseVoucher = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Post a reversing voucher?\n\nVoucher No : ${props.voucher.voucher_number}`
    );
    if (!remark) return;
    router.post(`/accounting/vouchers/${props.voucher.id}/reverse`);
};
</script>

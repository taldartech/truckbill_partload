<template>
    <AdminLayout :title="`Invoice ${invoice.invoice_no || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Invoice"
                        :identity="invoice.invoice_no || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/invoices"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Invoices"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit invoices')"
                                :href="`/invoices/${invoice.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Invoice"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                type="button"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Print Invoice"
                                @click="printInvoice"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </button>
                            <button
                                v-if="can('delete invoices')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Invoice"
                                @click="deleteInvoice"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="invoiceFields" :columns="3" />
                        <div v-if="invoice.status" class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="statusBadgeClass">
                                {{ invoice.status }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Bilties" :hint="`${detailRows.length} item(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Bilty No</th>
                                        <th>Date</th>
                                        <th>From Station</th>
                                        <th>To Station</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in detailRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.bilty_number }}</td>
                                        <td class="text-nowrap ">{{ formatDate(row.date) }}</td>
                                        <td>{{ row.from_station }}</td>
                                        <td>{{ row.to_station }}</td>
                                        <td class="text-end">₹{{ formatMoney(row.amount) }}</td>
                                    </tr>
                                    <tr v-if="detailRows.length === 0">
                                        <td colspan="6" class="text-center text-muted py-3">No bilties linked</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Amount Summary">
                        <dl class="app-show-charge-list">
                            <dt>Total Amount</dt>
                            <dd>₹{{ formatMoney(invoice.total_amount) }}</dd>
                            <dt>GST Amount</dt>
                            <dd>₹{{ formatMoney(invoice.gst_amount) }}</dd>
                        </dl>
                        <div class="app-show-total">
                            <span class="app-show-total__label">Net Amount</span>
                            <span class="app-show-total__value">₹{{ formatMoney(invoice.net_amount) }}</span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Record Info">
                        <div class="app-show-meta-list">
                            <div
                                v-for="item in recordInfoItems"
                                :key="item.label"
                                class="app-show-meta-list__item"
                            >
                                <span class="app-show-meta-list__label">{{ item.label }}</span>
                                <span class="app-show-meta-list__value">{{ item.value }}</span>
                                <span v-if="item.by" class="app-show-meta-list__by">by {{ item.by }}</span>
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
import { formatMoney, formatDate, formatDateTime } from '@/utils/showFormatters';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const statusBadgeClass = computed(() => {
    switch (props.invoice.status) {
        case 'Pending':
            return 'bg-warning';
        case 'Partial':
            return 'bg-info';
        case 'Paid':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
});

const invoiceFields = computed(() => ([
    { label: 'Date', value: formatDate(props.invoice.date) },
    { label: 'Party', value: props.invoice.party?.name },
    { label: 'Remark', value: props.invoice.remark },
]));

const detailRows = computed(() => {
    const details = props.invoice.details || [];

    return details.map((detail) => {
        const bilty = detail.bilty || {};

        return {
            id: detail.id,
            bilty_number: bilty.bilty_number || '—',
            date: bilty.date,
            from_station: bilty.from_station?.name || bilty.fromStation?.name || '—',
            to_station: bilty.to_station?.name || bilty.toStation?.name || '—',
            amount: detail.amount ?? bilty.amount,
        };
    });
});

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.invoice.created_at),
        by: props.invoice.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.invoice.updated_at),
        by: '',
    },
]));

const printInvoice = () => {
    window.open(`/invoices/${props.invoice.id}/print`, '_blank');
};

const deleteInvoice = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this invoice?

Invoice No : ${props.invoice.invoice_no}
Date       : ${props.invoice.date}
Party      : ${props.invoice.party?.name || '—'}
Amount     : ₹ ${formatMoney(props.invoice.net_amount)}
Status     : ${props.invoice.status || 'Pending'}`
    );

    if (!remark) return;

    router.delete(`/invoices/${props.invoice.id}`, {
        data: { delete_remark: remark },
    });
};
</script>

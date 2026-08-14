<template>
    <AdminLayout :title="`Delivery Receipt ${receipt.delivery_receipt_no || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Delivery Receipt"
                        :identity="receipt.delivery_receipt_no || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/delivery-receipts"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Delivery Receipts"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit delivery_receipts')"
                                :href="`/delivery-receipts/${receipt.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Delivery Receipt"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                type="button"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Print Delivery Receipt"
                                @click="printReceipt"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </button>
                            <button
                                v-if="can('delete delivery_receipts')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Delivery Receipt"
                                @click="deleteReceipt"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="receiptFields" :columns="3" />
                        <div v-if="receipt.mode_of_payment" class="app-show-status-row">
                            <span class="app-show-status-row__label">Mode:</span>
                            <span class="badge app-show-status-row__badge" :class="modeBadgeClass">
                                {{ receipt.mode_of_payment }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Parties">
                        <div class="row g-3">
                            <div :class="showPaymentDueFrom ? 'col-md-6' : 'col-12'">
                                <div class="app-show-party-card">
                                    <h3 class="app-show-party-card__title">Consignee</h3>
                                    <p class="app-show-party-card__name">{{ partyName(receipt.consignee) }}</p>
                                    <ShowFieldGrid :fields="partyFields(receipt.consignee)" :columns="2" />
                                </div>
                            </div>
                            <div v-if="showPaymentDueFrom" class="col-md-6">
                                <div class="app-show-party-card">
                                    <h3 class="app-show-party-card__title">Payment Due From</h3>
                                    <p class="app-show-party-card__name">{{ partyName(receipt.payment_due_from) }}</p>
                                    <ShowFieldGrid :fields="partyFields(receipt.payment_due_from)" :columns="2" />
                                </div>
                            </div>
                        </div>
                    </ShowSection>

                    <ShowSection title="GR / Shipment Details">
                        <ShowFieldGrid :fields="grFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="GST Details">
                        <ShowFieldGrid :fields="gstFields" :columns="3" />
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Charges & Total">
                        <dl class="app-show-charge-list">
                            <template v-for="(charge, index) in chargeRows" :key="`${charge.label}-${index}`">
                                <dt>{{ charge.label }}</dt>
                                <dd>{{ charge.display || `₹${formatMoney(charge.amount)}` }}</dd>
                            </template>
                        </dl>
                        <div class="app-show-total">
                            <span class="app-show-total__label">Final Amount</span>
                            <span class="app-show-total__value">₹{{ formatMoney(receipt.final_amount) }}</span>
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
import { formatMoney, formatDate, formatDateTime, formatQty } from '@/utils/showFormatters';

const props = defineProps({
    receipt: {
        type: Object,
        required: true,
    },
    gr_source: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const grSource = computed(() => props.gr_source || {});

const showPaymentDueFrom = computed(() => (
    props.receipt.mode_of_payment === 'Due' || Boolean(props.receipt.payment_due_from)
));

const modeBadgeClass = computed(() => {
    switch (props.receipt.mode_of_payment) {
        case 'Cash':
            return 'bg-success';
        case 'Bank':
            return 'bg-info';
        case 'Due':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
});

const partyName = (party) => party?.name || '—';

const partyFields = (party) => ([
    { label: 'Mobile', value: party?.mobile || '' },
    { label: 'GST', value: party?.gst_number || '' },
    { label: 'State', value: party?.state?.name || '' },
    { label: 'City', value: party?.city?.name || '' },
    { label: 'Address', value: party?.address || '' },
]);

const gstOnLabel = (value) => {
    if (value === 'delivery_charge') {
        return 'Delivery Charge';
    }

    if (value === 'total_charge') {
        return 'Total Charge';
    }

    return value || '';
};

const receiptFields = computed(() => ([
    { label: 'Date', value: formatDate(props.receipt.date) },
    { label: 'GR/Bilty No', value: props.receipt.gr_no_bilty_no, accent: true },
    { label: 'Mode of Payment', value: props.receipt.mode_of_payment },
    { label: 'Haste', value: props.receipt.haste },
    {
        label: 'Payment Due From',
        value: showPaymentDueFrom.value ? partyName(props.receipt.payment_due_from) : '',
    },
]));

const grFields = computed(() => ([
    { label: 'GR No', value: props.receipt.gr_no_bilty_no, accent: true },
    { label: 'GR Date', value: formatDate(grSource.value.gr_date) },
    { label: 'Received Date', value: formatDate(grSource.value.received_date) },
    { label: 'Consignor', value: grSource.value.consignor },
    { label: 'From Station', value: grSource.value.from_station },
    { label: 'To Station', value: grSource.value.to_station },
    { label: 'Goods', value: grSource.value.goods_name },
    { label: 'Private Marka', value: grSource.value.private_marka },
    { label: 'Packages', value: formatQty(grSource.value.packages) },
    { label: 'Weight', value: formatQty(grSource.value.weight) },
    { label: 'Basis of Booking', value: grSource.value.basis_of_booking },
    { label: 'Disposition', value: grSource.value.disposition },
    { label: 'GR Status', value: grSource.value.status },
    {
        label: 'GR Freight',
        value: grSource.value.freight === null || grSource.value.freight === undefined
            ? ''
            : `₹${formatMoney(grSource.value.freight)}`,
    },
    {
        label: 'Home Delivery Charge',
        value: grSource.value.home_delivery_charge === null || grSource.value.home_delivery_charge === undefined
            ? ''
            : `₹${formatMoney(grSource.value.home_delivery_charge)}`,
    },
]));

const gstFields = computed(() => ([
    { label: 'GST On', value: gstOnLabel(props.receipt.gst_on) },
    { label: 'GST Rate', value: props.receipt.gst_rate !== null && props.receipt.gst_rate !== undefined ? `${props.receipt.gst_rate}%` : '' },
    { label: 'GST Amount', value: `₹${formatMoney(props.receipt.gst)}` },
    { label: 'GST Pay By', value: props.receipt.gst_pay_by },
    { label: 'Less', value: `₹${formatMoney(props.receipt.less)}` },
    { label: 'Sub Total', value: `₹${formatMoney(props.receipt.total_amount)}` },
]));

const chargeRows = computed(() => {
    const rows = [
        { label: 'Freight', amount: props.receipt.freight },
        { label: 'Handling Charge', amount: props.receipt.handling_charge },
        { label: 'Delivery Charge', amount: props.receipt.delivery_charge },
        { label: 'Stationery Charge', amount: props.receipt.stationery_charge },
        { label: 'Demurrage', amount: props.receipt.demurrage },
        { label: 'Other Charge', amount: props.receipt.other_charge },
        { label: 'Carriage Charge', amount: props.receipt.carriage_charge },
        { label: 'Sub Total', amount: props.receipt.total_amount },
    ];

    if (Number(props.receipt.gst ?? 0) > 0 || props.receipt.gst_pay_by) {
        rows.push({
            label: props.receipt.gst_pay_by
                ? `GST (${props.receipt.gst_rate ?? 0}% · ${props.receipt.gst_pay_by})`
                : `GST (${props.receipt.gst_rate ?? 0}%)`,
            amount: props.receipt.gst,
        });
    }

    if (Number(props.receipt.less ?? 0) > 0) {
        rows.push({ label: 'Less', amount: props.receipt.less });
    }

    return rows;
});

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.receipt.created_at),
        by: props.receipt.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.receipt.updated_at),
        by: '',
    },
]));

const printReceipt = () => {
    window.open(`/delivery-receipts/${props.receipt.id}/print`, '_blank');
};

const deleteReceipt = () => {
    if (!window.confirm('Are you sure you want to delete this delivery receipt?')) {
        return;
    }

    router.delete(`/delivery-receipts/${props.receipt.id}`);
};
</script>

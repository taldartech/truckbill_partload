<template>
    <AdminLayout :title="`Bilty ${bilty.bilty_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Booking"
                        :identity="bilty.bilty_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/bilties"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Bilties"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit bilties')"
                                :href="`/bilties/${bilty.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Bilty"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <BiltyPrintButton
                                :bilty-id="bilty.id"
                                button-class="app-show-action-btn app-show-action-btn--print"
                                title="Print Bilty"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </BiltyPrintButton>
                            <button
                                v-if="can('delete bilties')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Bilty"
                                @click="deleteBilty"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="bookingFields" :columns="3" />
                        <div v-if="bilty.status" class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="statusBadgeClass">
                                {{ bilty.status }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Parties">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="app-show-party-card">
                                    <h3 class="app-show-party-card__title">Consignor</h3>
                                    <p class="app-show-party-card__name">{{ partyName(bilty.consignor) }}</p>
                                    <ShowFieldGrid :fields="partyFields(bilty.consignor)" :columns="2" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="app-show-party-card">
                                    <h3 class="app-show-party-card__title">Consignee</h3>
                                    <p class="app-show-party-card__name">{{ partyName(bilty.consignee) }}</p>
                                    <ShowFieldGrid :fields="partyFields(bilty.consignee)" :columns="2" />
                                </div>
                            </div>
                        </div>
                    </ShowSection>

                    <ShowSection title="Goods" :hint="`${detailRows.length} item(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Goods</th>
                                        <th>Packing</th>
                                        <th class="text-end">Pkg</th>
                                        <th class="text-end">Act Wt</th>
                                        <th class="text-end">Chg Wt</th>
                                        <th>Rate Type</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in detailRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.good_name }}</td>
                                        <td>{{ row.package_type_name }}</td>
                                        <td class="text-end">{{ formatQty(row.no_of_packages) }}</td>
                                        <td class="text-end">{{ formatQty(row.gross_weight) }}</td>
                                        <td class="text-end">{{ formatQty(row.charged_weight) }}</td>
                                        <td>{{ row.rate_type || '—' }}</td>
                                        <td class="text-end">{{ formatQty(row.rate) }}</td>
                                        <td class="text-end">₹{{ formatMoney(row.amount) }}</td>
                                    </tr>
                                    <tr v-if="detailRows.length === 0">
                                        <td colspan="9" class="text-center text-muted py-3">No goods items</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>

                    <ShowSection title="Shipment Details">
                        <ShowFieldGrid :fields="shipmentFields" :columns="3" />
                        <div v-if="ewayRows.length" class="mt-3">
                            <div class="app-show-table-wrap">
                                <table class="table table-sm table-bordered app-show-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>E-Way Bill No</th>
                                            <th>Valid Till</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(eway, index) in ewayRows" :key="eway.id || index">
                                            <td>{{ eway.eway_bill_no || '—' }}</td>
                                            <td>{{ formatDate(eway.eway_bill_valid_till) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
                            <span class="app-show-total__label">Total</span>
                            <span class="app-show-total__value">₹{{ formatMoney(bilty.amount) }}</span>
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
import BiltyPrintButton from '@/Components/BiltyPrintButton.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowFieldGrid from '@/Components/Show/ShowFieldGrid.vue';
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';
const props = defineProps({
    bilty: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const formatMoney = (value) => {
    const amount = Number(value ?? 0);

    return amount.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatQty = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return String(value);
    }

    return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/\.?0+$/, '');
};

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value).slice(0, 10);
    }

    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const partyName = (party) => party?.name || '—';

const partyFields = (party) => ([
    { label: 'Mobile', value: party?.mobile || '' },
    { label: 'GST', value: party?.gst_number || '' },
    { label: 'State', value: party?.state?.name || '' },
    { label: 'City', value: party?.city?.name || '' },
]);

const detailAmount = (detail) => {
    const rate = Number(detail.rate ?? 0);
    const weight = Number(detail.gross_weight ?? 0);
    const chargedWeight = Number(detail.charged_weight ?? 0);
    const packages = Number(detail.no_of_packages ?? 0);
    const rateType = detail.rate_type || 'Fixed';

    if (rateType === 'Per Actual Weight' || rateType === 'Per KG') {
        return rate * weight;
    }

    if (rateType === 'Per Charged Weight') {
        return rate * chargedWeight;
    }

    if (rateType === 'Per Package') {
        return rate * packages;
    }

    return rate;
};

const detailRows = computed(() => {
    const details = props.bilty.bilty_details || props.bilty.biltyDetails || [];

    return details.map((detail) => ({
        id: detail.id,
        good_name: detail.good?.name || '—',
        package_type_name: detail.package_type?.name || detail.packageType?.name || '—',
        no_of_packages: detail.no_of_packages,
        gross_weight: detail.gross_weight,
        charged_weight: detail.charged_weight,
        rate_type: detail.rate_type,
        rate: detail.rate,
        amount: detailAmount(detail),
    }));
});

const ewayRows = computed(() => props.bilty.eway_bills || props.bilty.ewayBills || []);

const addonCharges = computed(() => {
    const charges = props.bilty.addon_charges;

    return Array.isArray(charges) ? charges : [];
});

const statusBadgeClass = computed(() => {
    switch (props.bilty.status) {
        case 'Pending':
            return 'bg-warning';
        case 'In Transit':
            return 'bg-info';
        case 'Delivered':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
});

const bookingFields = computed(() => ([
    { label: 'Date', value: formatDate(props.bilty.date) },
    { label: 'Payment Type', value: props.bilty.payment_type },
    { label: 'From Station', value: props.bilty.from_station?.name || props.bilty.fromStation?.name },
    { label: 'To Station', value: props.bilty.to_station?.name || props.bilty.toStation?.name },
]));

const chargeRows = computed(() => {
    const rows = [
        { label: 'Freight', amount: props.bilty.freight },
        { label: 'Bilty Charge', amount: props.bilty.bilty_charge },
        { label: 'Surcharge', amount: props.bilty.surcharge },
        { label: 'Handling Charge', amount: props.bilty.handling_charge },
        {
            label: props.bilty.cartage_type
                ? `Cartage (${props.bilty.cartage_type}${props.bilty.cartage_account?.name ? ` · ${props.bilty.cartage_account.name}` : ''})`
                : 'Cartage',
            amount: props.bilty.cartage,
        },
        { label: 'Risk / DD Charge', amount: props.bilty.risk_dd_charge },
    ];

    addonCharges.value.forEach((charge) => {
        rows.push({
            label: charge.particulars || charge.description || 'Addon Charge',
            amount: charge.amount,
        });
    });

    if (Number(props.bilty.gst ?? 0) > 0) {
        const gstAmount = Number(props.bilty.cgst ?? 0)
            + Number(props.bilty.sgst ?? 0)
            + Number(props.bilty.igst ?? 0);

        rows.push({
            label: `GST (${props.bilty.gst}%${props.bilty.gst_liability_by ? ` · ${props.bilty.gst_liability_by}` : ''})`,
            amount: gstAmount,
            display: gstAmount > 0 ? null : '—',
            keep: true,
        });
    }

    if (Number(props.bilty.less_advance_received ?? 0) > 0) {
        rows.push({
            label: props.bilty.advance_payment_mode
                ? `Less Advance (${props.bilty.advance_payment_mode})`
                : 'Less Advance',
            amount: props.bilty.less_advance_received,
            keep: true,
        });
    }

    return rows.filter((row) => row.keep || Number(row.amount ?? 0) !== 0 || ['Freight', 'Bilty Charge'].includes(row.label));
});

const shipmentFields = computed(() => ([
    { label: 'Private Marka', value: props.bilty.private_marka },
    { label: 'Delivery At', value: props.bilty.delivery_at },
    { label: 'Actual Weight', value: formatQty(props.bilty.weight_actual) },
    { label: 'Declared Value', value: props.bilty.declared_value ? `₹${formatMoney(props.bilty.declared_value)}` : '' },
    { label: 'Invoice No', value: props.bilty.invoice_number },
    {
        label: 'E-Way Bills',
        value: ewayRows.value.length
            ? ewayRows.value.map((eway) => eway.eway_bill_no).filter(Boolean).join(', ')
            : '',
    },
]));

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.bilty.created_at),
        by: props.bilty.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.bilty.updated_at),
        by: '',
    }
]));
const deleteBilty = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this bilty? ${props.bilty.bilty_number}`
    );

    if (!remark) {
        return;
    }

    router.delete(`/bilties/${props.bilty.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

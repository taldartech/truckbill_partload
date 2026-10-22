<template>
    <AdminLayout :title="`Main Challan ${main_challan.main_challan_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Main Challan"
                        :identity="main_challan.main_challan_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/main-challans"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Main Challans"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit challans')"
                                :href="`/main-challans/${main_challan.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Main Challan"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                type="button"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Print Main Challan"
                                @click="printMainChallan"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </button>
                            <button
                                v-if="can('delete challans')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Main Challan"
                                @click="deleteMainChallan"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="mainChallanFields" :columns="3" />
                        <div v-if="main_challan.status" class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="statusBadgeClass">
                                {{ main_challan.status }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Challans" :hint="`${challanRows.length} challan(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Challan No</th>
                                        <th>Date</th>
                                        <th>From Station</th>
                                        <th>To Station</th>
                                        <th class="text-end">Packages</th>
                                        <th class="text-end">Weight</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in challanRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.challan_number }}</td>
                                        <td class="text-nowrap ">{{ row.date }}</td>
                                        <td>{{ row.from_station }}</td>
                                        <td>{{ row.to_station }}</td>
                                        <td class="text-end">{{ formatQty(row.packages) }}</td>
                                        <td class="text-end">{{ formatQty(row.weight) }}</td>
                                        <td class="text-end">₹{{ formatMoney(row.amount) }}</td>
                                    </tr>
                                    <tr v-if="challanRows.length === 0">
                                        <td colspan="8" class="text-center text-muted py-3">No challans loaded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>

                    <AccountingPanel v-if="accounting" :vouchers="accounting" />
                </div>

                <ShowSidebar>
                    <ShowSection title="Charges &amp; Total">
                        <dl class="app-show-charge-list">
                            <template v-for="(charge, index) in chargeRows" :key="`${charge.label}-${index}`">
                                <dt>{{ charge.label }}</dt>
                                <dd>₹{{ formatMoney(charge.amount) }}</dd>
                            </template>
                        </dl>
                        <div class="app-show-total">
                            <span class="app-show-total__label">Total Amount</span>
                            <span class="app-show-total__value">₹{{ formatMoney(main_challan.total_amount) }}</span>
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
import AccountingPanel from '@/Components/Accounting/AccountingPanel.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowFieldGrid from '@/Components/Show/ShowFieldGrid.vue';
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { formatMoney, formatQty, formatDate, formatDateTime } from '@/utils/showFormatters';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';
const props = defineProps({
    accounting: { type: Array, default: null },
    main_challan: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const statusBadgeClass = computed(() => {
    switch (props.main_challan.status) {
        case 'Created':
            return 'bg-warning';
        case 'In Progress':
            return 'bg-info';
        case 'Completed':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
});

const mainChallanFields = computed(() => ([
    { label: 'Date', value: formatDate(props.main_challan.date) },
    { label: 'From Station', value: props.main_challan.from_station?.name },
    { label: 'To Station', value: props.main_challan.to_station?.name },
    { label: 'Truck Number', value: props.main_challan.truck?.truck_number },
    { label: 'Driver', value: props.main_challan.driver_name },
    { label: 'To (Account)', value: props.main_challan.pay_by_agent?.name },
    { label: 'Remark', value: props.main_challan.remark },
]));

const challanRows = computed(() => {
    const details = props.main_challan.details || [];

    return details.map((detail) => {
        const challan = detail.challan || {};

        return {
            id: detail.id,
            challan_number: challan.challan_number || '—',
            date: formatDate(challan.date),
            from_station: challan.from_station?.name || '—',
            to_station: challan.to_station?.name || '—',
            packages: challan.total_packages,
            weight: challan.total_weight,
            amount: challan.total_amount,
        };
    });
});

const chargeRows = computed(() => ([
    { label: 'Crossing Freight', amount: props.main_challan.crossing_freight },
    { label: 'Truck Freight', amount: props.main_challan.truck_freight },
    { label: 'Labour', amount: props.main_challan.labour_charge },
    { label: 'Kat', amount: props.main_challan.kat },
    { label: 'Home Delivery', amount: props.main_challan.home_delivery_charge },
    { label: 'Cartage/Dalali/PF', amount: props.main_challan.cartage_dalali_pf },
    { label: 'GST', amount: props.main_challan.gst },
    { label: 'Cash Received', amount: props.main_challan.cash_received },
    { label: 'Cash Paid', amount: props.main_challan.cash_paid },
    { label: 'To Pay', amount: props.main_challan.to_pay_amount },
    { label: 'Paid', amount: props.main_challan.paid_amount },
]));

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.main_challan.created_at),
        by: props.main_challan.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.main_challan.updated_at),
        by: '',
    },
]));

const printMainChallan = () => {
    window.open(`/main-challans/${props.main_challan.id}/print`, '_blank');
};

const deleteMainChallan = async () => {
    const challan = props.main_challan;

    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this challan?

Challan    : ${challan.main_challan_number}
Date       : ${formatDate(challan.date)}
Truck      : ${challan.truck?.truck_number || '—'}

From       : ${challan.from_station?.name || '—'}
To         : ${challan.to_station?.name || '—'}`
    );

    if (!remark) {
        return;
    }

    router.delete(`/main-challans/${challan.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

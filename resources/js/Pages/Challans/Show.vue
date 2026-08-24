<template>
    <AdminLayout :title="`Challan ${challan.challan_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Challan"
                        :identity="challan.challan_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/challans"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Challans"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit challans')"
                                :href="`/challans/${challan.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Challan"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                type="button"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Print Challan"
                                @click="printChallan"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </button>
                            <button
                                v-if="can('delete challans')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Challan"
                                @click="deleteChallan"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="challanFields" :columns="3" />
                        <div v-if="challan.status" class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="statusBadgeClass">
                                {{ challan.status }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Items" :hint="`${detailRows.length} item(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>GR/Bilty No</th>
                                        <th>Type</th>
                                        <th>Consignee</th>
                                        <th>To Station</th>
                                        <th>Goods</th>
                                        <th class="text-end">Packages</th>
                                        <th class="text-end">Weight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in detailRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.number }}</td>
                                        <td>{{ row.type }}</td>
                                        <td>{{ row.consignee }}</td>
                                        <td>{{ row.to_station }}</td>
                                        <td>{{ row.good_name }}</td>
                                        <td class="text-end">{{ formatQty(row.packages) }}</td>
                                        <td class="text-end">{{ formatQty(row.weight) }}</td>
                                    </tr>
                                    <tr v-if="detailRows.length === 0">
                                        <td colspan="8" class="text-center text-muted py-3">No items loaded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>
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
                            <span class="app-show-total__value">₹{{ formatMoney(challan.total_amount) }}</span>
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
import { formatMoney, formatQty, formatDate, formatDateTime } from '@/utils/showFormatters';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';
const props = defineProps({
    challan: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const statusBadgeClass = computed(() => {
    switch (props.challan.status) {
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

const challanFields = computed(() => ([
    { label: 'Date', value: formatDate(props.challan.date) },
    { label: 'From Station', value: props.challan.from_station?.name },
    { label: 'To Station', value: props.challan.to_station?.name },
    { label: 'Truck Number', value: props.challan.truck?.truck_number },
    { label: 'Driver', value: props.challan.driver_name },
    { label: 'Pay By', value: props.challan.pay_by_agent?.name || props.challan.pay_by },
    { label: 'Delivery At', value: props.challan.delivery_at_agent?.name || props.challan.delivery_at },
    { label: 'Remark', value: props.challan.remark },
]));

const detailRows = computed(() => {
    const details = props.challan.details || [];

    return details.map((detail) => {
        if (detail.bilty) {
            const bilty = detail.bilty;
            const biltyDetails = bilty.bilty_details || bilty.biltyDetails || [];

            return {
                id: detail.id,
                number: bilty.bilty_number || '—',
                type: 'Bilty',
                consignee: bilty.consignee?.name || '—',
                to_station: detail.to_station?.name || bilty.to_station?.name || '—',
                good_name: biltyDetails[0]?.good?.name || '—',
                packages: biltyDetails.reduce((sum, item) => sum + Number(item.no_of_packages ?? 0), 0),
                weight: biltyDetails.reduce((sum, item) => sum + Number(item.gross_weight ?? 0), 0),
            };
        }

        const inward = detail.inward_register_detail;
        if (inward) {
            return {
                id: detail.id,
                number: inward.gr_no || `GR-${inward.id}`,
                type: 'Inward',
                consignee: inward.consignee?.name || '—',
                to_station: detail.to_station?.name || inward.to_station?.name || '—',
                good_name: inward.good?.name || '—',
                packages: Number(inward.no_of_packages ?? 0),
                weight: Number(inward.weight ?? 0),
            };
        }

        return {
            id: detail.id,
            number: '—',
            type: '—',
            consignee: '—',
            to_station: detail.to_station?.name || '—',
            good_name: '—',
            packages: 0,
            weight: 0,
        };
    });
});

const chargeRows = computed(() => ([
    { label: 'Booking Freight', amount: props.challan.booking_freight },
    { label: 'Crossing Freight', amount: props.challan.crossing_freight },
    { label: 'Truck Freight', amount: props.challan.truck_freight },
    { label: 'Labour', amount: props.challan.labour_charge },
    { label: 'Kat', amount: props.challan.kat },
    { label: 'Home Delivery', amount: props.challan.home_delivery_charge },
    { label: 'Other', amount: props.challan.other_charge },
    { label: 'Cartage/Dalali/PF', amount: props.challan.cartage_dalali_pf },
    { label: 'GST', amount: props.challan.gst },
    { label: 'Cash Received', amount: props.challan.cash_received },
    { label: 'Cash Payment', amount: props.challan.cash_payment },
    { label: 'To Pay', amount: props.challan.to_pay_amount },
    { label: 'Paid', amount: props.challan.paid_amount },
]));

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.challan.created_at),
        by: props.challan.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.challan.updated_at),
        by: '',
    },
]));

const printChallan = () => {
    window.open(`/challans/${props.challan.id}/print`, '_blank');
};

const deleteChallan = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this challan?');
    if (yes) {router.delete(`/challans/${props.challan.id}`);}
};
</script>

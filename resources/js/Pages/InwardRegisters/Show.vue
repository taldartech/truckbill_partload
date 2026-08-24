<template>
    <AdminLayout :title="`Inward Register ${register.inward_register_no || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Inward Register"
                        :identity="register.inward_register_no || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/inward-registers"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Inward Registers"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit inward_registers')"
                                :href="`/inward-registers/${register.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Inward Register"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete inward_registers')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Inward Register"
                                @click="deleteRegister"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="headerFields" :columns="4" />
                    </ShowSection>

                    <ShowSection title="Details" :hint="`${detailRows.length} item(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>GR No</th>
                                        <th>Consignor</th>
                                        <th>Consignee</th>
                                        <th>Good</th>
                                        <th class="text-end">Pkg</th>
                                        <th class="text-end">Freight</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in detailRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.gr_no || '—' }}</td>
                                        <td>{{ row.consignor_name }}</td>
                                        <td>{{ row.consignee_name }}</td>
                                        <td>{{ row.good_name }}</td>
                                        <td class="text-end">{{ formatQty(row.no_of_packages) }}</td>
                                        <td class="text-end">₹{{ formatMoney(row.freight) }}</td>
                                        <td>
                                            <span class="badge" :class="statusBadgeClass(row.status)">
                                                {{ row.status || '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="detailRows.length === 0">
                                        <td colspan="8" class="text-center text-muted py-3">No details recorded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Totals">
                        <dl class="app-show-charge-list">
                            <dt>Total Packages</dt>
                            <dd>{{ formatQty(register.total_packages) }}</dd>
                            <dt>Delivery Freight</dt>
                            <dd>₹{{ formatMoney(register.total_delivery_freight) }}</dd>
                            <dt>Crossing Freight</dt>
                            <dd>₹{{ formatMoney(register.total_crossing_freight) }}</dd>
                        </dl>
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
    register: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const headerFields = computed(() => ([
    { label: 'Date', value: formatDate(props.register.date) },
    { label: 'From Station', value: props.register.from_station?.name },
    { label: 'To Station', value: props.register.to_station?.name },
    { label: 'Truck', value: props.register.truck?.truck_number },
    { label: 'Driver', value: props.register.driver_name },
    { label: 'Challan No', value: props.register.challan_number },
    { label: 'Challan Date', value: formatDate(props.register.challan_date) },
]));

const detailRows = computed(() => (props.register.details || []).map((detail) => ({
    id: detail.id,
    gr_no: detail.gr_no,
    consignor_name: detail.consignor?.name || '—',
    consignee_name: detail.consignee?.name || '—',
    good_name: detail.good?.name || '—',
    no_of_packages: detail.no_of_packages,
    freight: detail.freight,
    status: detail.status,
})));

const statusBadgeClass = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-warning';
        case 'challaned':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
};

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.register.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.register.updated_at) },
]));

const deleteRegister = async () => {
    const yes= await askKeyboardConfirm('Are you sure you want to delete this inward register?');
     if(yes){router.delete(`/inward-registers/${props.register.id}`);}
};
</script>

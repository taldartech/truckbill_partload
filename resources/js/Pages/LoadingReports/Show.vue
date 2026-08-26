<template>
    <AdminLayout :title="`Loading Report ${identity}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Loading Report"
                        :identity="identity"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/loading-reports"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Loading Reports"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit loading_reports')"
                                :href="`/loading-reports/${report.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Loading Report"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <a
                                :href="`/loading-reports/${report.id}/print`"
                                target="_blank"
                                rel="noopener"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Print Loading Report"
                            >
                                <i class="fas fa-print" aria-hidden="true"></i>
                                <span class="visually-hidden">Print</span>
                            </a>
                            <button
                                v-if="can('delete loading_reports')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Loading Report"
                                @click="deleteReport"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="headerFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Line Items" :hint="`${detailRows.length} item(s)`">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>GR/Bilty No</th>
                                        <th>Type</th>
                                        <th>To Station</th>
                                        <th>Consignee</th>
                                        <th>Good</th>
                                        <th>Package Type</th>
                                        <th class="text-end">Pkg</th>
                                        <th class="text-end">Weight (Kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in detailRows" :key="row.id || index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.document_number }}</td>
                                        <td>{{ row.row_type }}</td>
                                        <td>{{ row.to_station_name }}</td>
                                        <td>{{ row.consignee_name }}</td>
                                        <td>{{ row.good_name }}</td>
                                        <td>{{ row.package_type_name }}</td>
                                        <td class="text-end">{{ formatQty(row.no_of_packages) }}</td>
                                        <td class="text-end">{{ formatQty(row.gross_weight) }}</td>
                                    </tr>
                                    <tr v-if="detailRows.length === 0">
                                        <td colspan="9" class="text-center text-muted py-3">No line items recorded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Summary">
                        <ShowFieldGrid :fields="summaryFields" :columns="1" />
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
import { formatQty, formatDate, formatDateTime } from '@/utils/showFormatters';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const identity = computed(() => `LR-${props.report.id}`);

const headerFields = computed(() => ([
    { label: 'Date', value: formatDate(props.report.date) },
    { label: 'From Station', value: props.report.from_station?.name },
    { label: 'To Station', value: props.report.to_station?.name },
    { label: 'Truck', value: props.report.truck?.truck_number },
    { label: 'Driver', value: props.report.driver_name },
    { label: 'Remark', value: props.report.remark },
]));

const detailRows = computed(() => (props.report.details || []).map((detail) => {
    const inward = detail.inward_register_detail || detail.inwardRegisterDetail;
    const isInward = Boolean(detail.inward_register_detail_id || inward);

    return {
        id: detail.id,
        document_number: detail.bilty?.bilty_number
            || inward?.gr_no
            || (inward ? `GR-${inward.id}` : '—'),
        row_type: isInward ? 'Crossing' : 'Booking',
        to_station_name: detail.bilty?.to_station?.name
            || detail.bilty?.toStation?.name
            || inward?.to_station?.name
            || inward?.toStation?.name
            || '—',
        consignee_name: detail.consignee?.name || inward?.consignee?.name || '—',
        good_name: detail.good?.name || inward?.good?.name || '—',
        package_type_name: detail.package_type?.name || detail.packageType?.name || '—',
        no_of_packages: detail.no_of_packages,
        gross_weight: detail.gross_weight,
    };
}));

const summaryFields = computed(() => ([
    { label: 'Total Packages', value: formatQty(props.report.no_of_packages) },
    { label: 'Total Weight (Kg)', value: formatQty(props.report.gross_weight) },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.report.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.report.updated_at) },
]));

const deleteReport = () => {
    if (!window.confirm('Are you sure you want to delete this loading report?')) {
        return;
    }

    router.delete(`/loading-reports/${props.report.id}`);
};
</script>

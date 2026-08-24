<template>
    <AdminLayout :title="`Truck ${truck.truck_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Truck"
                        :identity="truck.truck_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/trucks"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Trucks"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit trucks')"
                                :href="`/trucks/${truck.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Truck"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete trucks')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Truck"
                                @click="deleteTruck"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="statusBadgeClass">
                                {{ truck.status || 'Available' }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Owner Details">
                        <ShowFieldGrid :fields="ownerFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Documents">
                        <div class="app-show-table-wrap">
                            <table class="table table-sm table-bordered app-show-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Number</th>
                                        <th>Expiry</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in documentRows" :key="row.label">
                                        <td>{{ row.label }}</td>
                                        <td>{{ row.number || '—' }}</td>
                                        <td>{{ formatDate(row.expiry) }}</td>
                                        <td>
                                            <span v-if="row.expired" class="badge bg-danger">Expired</span>
                                            <span v-else-if="row.expiry" class="badge bg-success">Valid</span>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                </tbody>
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
import { formatDate, formatDateTime } from '@/utils/showFormatters';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    truck: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Truck Type', value: props.truck.truck_type?.name || props.truck.truckType?.name },
    { label: 'Capacity', value: props.truck.capacity },
    { label: 'Current State', value: props.truck.current_state?.name || props.truck.currentState?.name },
    { label: 'Current City', value: props.truck.current_city?.name || props.truck.currentCity?.name },
    { label: 'Current Station', value: props.truck.current_station?.name || props.truck.currentStation?.name },
]));

const ownerFields = computed(() => ([
    { label: 'Owner Name', value: props.truck.owner_name },
    { label: 'Owner Mobile', value: props.truck.owner_mobile },
    { label: 'Owner Email', value: props.truck.owner_email },
    { label: 'Owner Address', value: props.truck.owner_address },
    { label: 'Owner City', value: props.truck.owner_city },
    { label: 'Owner State', value: props.truck.owner_state },
]));

const isExpired = (value) => {
    if (!value) {
        return false;
    }

    const date = new Date(value);

    return !Number.isNaN(date.getTime()) && date.getTime() < Date.now();
};

const documentRows = computed(() => ([
    { label: 'RC', number: props.truck.rc_no, expiry: props.truck.rc_expiry_date, expired: isExpired(props.truck.rc_expiry_date) },
    { label: 'Insurance', number: props.truck.insurance_no, expiry: props.truck.insurance_expiry_date, expired: isExpired(props.truck.insurance_expiry_date) },
    { label: 'Fitness', number: props.truck.fitness_no, expiry: props.truck.fitness_expiry_date, expired: isExpired(props.truck.fitness_expiry_date) },
    { label: 'Pollution', number: props.truck.pollution_no, expiry: props.truck.pollution_expiry_date, expired: isExpired(props.truck.pollution_expiry_date) },
    { label: 'Permit', number: props.truck.permit_no, expiry: props.truck.permit_expiry_date, expired: isExpired(props.truck.permit_expiry_date) },
]));

const statusBadgeClass = computed(() => {
    switch (props.truck.status) {
        case 'Booked':
            return 'bg-warning text-dark';
        case 'In Transit':
            return 'bg-info';
        case 'Maintenance':
            return 'bg-secondary';
        default:
            return 'bg-success';
    }
});

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.truck.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.truck.updated_at) },
]));

const deleteTruck = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this truck?');
    if (yes) router.delete(`/trucks/${props.truck.id}`);
};
</script>

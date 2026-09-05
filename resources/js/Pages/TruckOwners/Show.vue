<template>
    <AdminLayout :title="`Truck Owner ${truckOwner.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Truck Owner"
                        :identity="truckOwner.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/truck-owners"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Truck Owners"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit truck_owners')"
                                :href="`/truck-owners?edit=${truckOwner.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Truck Owner"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete truck_owners')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Truck Owner"
                                @click="deleteTruckOwner"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="addressFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Contact">
                        <ShowFieldGrid :fields="contactFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="GST">
                        <ShowFieldGrid :fields="gstFields" :columns="3" />
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
import { formatDateTime } from '@/utils/showFormatters';

const props = defineProps({
    truckOwner: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const addressFields = computed(() => ([
    { label: 'Address', value: props.truckOwner.address },
    { label: 'State', value: props.truckOwner.state?.name },
    { label: 'City', value: props.truckOwner.city?.name },
]));

const contactFields = computed(() => ([
    { label: 'Mobile', value: props.truckOwner.mobile },
    { label: 'Email', value: props.truckOwner.email },
]));

const gstFields = computed(() => ([
    { label: 'GST Number', value: props.truckOwner.gst_number },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.truckOwner.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.truckOwner.updated_at) },
]));

const deleteTruckOwner = () => {
    if (!window.confirm('Are you sure you want to delete this truck owner?')) {
        return;
    }

    router.delete(`/truck-owners/${props.truckOwner.id}`);
};
</script>

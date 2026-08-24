<template>
    <AdminLayout :title="`Party ${party.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Party"
                        :identity="party.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/parties"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Parties"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit parties')"
                                :href="`/parties?edit=${party.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Party"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete parties')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Party"
                                @click="deleteParty"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Contact">
                        <ShowFieldGrid :fields="contactFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Address">
                        <ShowFieldGrid :fields="addressFields" :columns="3" />
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
import { formatDateTime, formatMoney } from '@/utils/showFormatters';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';
const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Group', value: props.party.group?.name },
    { label: 'Bilty Charge', value: props.party.bilty_charge ? `₹${formatMoney(props.party.bilty_charge)}` : '' },
]));

const contactFields = computed(() => ([
    { label: 'Mobile', value: props.party.mobile },
    { label: 'Email', value: props.party.email },
]));

const addressFields = computed(() => ([
    { label: 'Address', value: props.party.address },
    { label: 'State', value: props.party.state?.name },
    { label: 'City', value: props.party.city?.name },
]));

const gstFields = computed(() => ([
    { label: 'GST Number', value: props.party.gst_number },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.party.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.party.updated_at) },
]));

const deleteParty = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this party?');
    if (yes) router.delete(`/parties/${props.party.id}`);
};
</script>

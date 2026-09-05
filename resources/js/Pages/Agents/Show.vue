<template>
    <AdminLayout :title="`Agent ${agent.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Agent"
                        :identity="agent.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/agents"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Agents"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit agents')"
                                :href="`/agents?edit=${agent.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Agent"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete agents')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Agent"
                                @click="deleteAgent"
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
    agent: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const addressFields = computed(() => ([
    { label: 'Address', value: props.agent.address },
    { label: 'State', value: props.agent.state?.name },
    { label: 'City', value: props.agent.city?.name },
]));

const contactFields = computed(() => ([
    { label: 'Mobile', value: props.agent.mobile },
    { label: 'Email', value: props.agent.email },
]));

const gstFields = computed(() => ([
    { label: 'GST Number', value: props.agent.gst_no },
    { label: 'Transporter Id', value: props.agent.transport_id },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.agent.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.agent.updated_at) },
]));

const deleteAgent = () => {
    if (!window.confirm('Are you sure you want to delete this agent?')) {
        return;
    }

    router.delete(`/agents/${props.agent.id}`);
};
</script>

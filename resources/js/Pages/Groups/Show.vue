<template>
    <AdminLayout :title="`Group ${group.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Group"
                        :identity="group.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/groups"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Groups"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit groups')"
                                :href="`/groups?edit=${group.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Group"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete groups')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Group"
                                @click="deleteGroup"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
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
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Name', value: props.group.name },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.group.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.group.updated_at) },
]));

const deleteGroup = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this group?

Group : ${props.group.name}`
    );

    if (!remark) return;

    router.delete(`/groups/${props.group.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

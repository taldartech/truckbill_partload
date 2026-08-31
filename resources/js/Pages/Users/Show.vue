<template>
    <AdminLayout :title="`User ${user.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="User"
                        :identity="user.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/users"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Users"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit users') && user.can_manage"
                                :href="`/users?edit=${user.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit User"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete users') && user.can_manage"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete User"
                                @click="deleteUser"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Roles:</span>
                            <span v-if="roleNames.length === 0" class="text-muted">—</span>
                            <span
                                v-for="role in roleNames"
                                :key="role"
                                class="badge bg-info app-show-status-row__badge"
                            >
                                {{ role }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Assignment">
                        <ShowFieldGrid :fields="assignmentFields" :columns="3" />
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
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowFieldGrid from '@/Components/Show/ShowFieldGrid.vue';
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { formatDateTime } from '@/utils/showFormatters';
import { usePermissions } from '@/composables/usePermissions';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const { can } = usePermissions();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const roleNames = computed(() => (props.user.roles ?? []).map((role) => role.name));

const overviewFields = computed(() => ([
    { label: 'Name', value: props.user.name },
    { label: 'Email', value: props.user.email },
]));

const assignmentFields = computed(() => ([
    { label: 'Company', value: props.user.company?.name || 'System/Global' },
    { label: 'Branch', value: props.user.branch?.name },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.user.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.user.updated_at) },
]));

const deleteUser = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this user?

Name    : ${props.user.name || '—'}
Email   : ${props.user.email || '—'}
Company : ${props.user.company?.name || 'System/Global'}
Branch  : ${props.user.branch?.name || '—'}
Role    : ${props.user.roles?.map(role => role.name)|| '—'}`
    );

    if (!remark) return;

    router.delete(`/users/${props.user.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

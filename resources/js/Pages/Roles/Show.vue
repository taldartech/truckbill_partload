<template>
    <AdminLayout :title="`Role ${role.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Role"
                        :identity="role.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/roles"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Roles"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit roles')"
                                :href="`/roles?edit=${role.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Role"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete roles') && !role.is_system_role"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Role"
                                @click="deleteRole"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Type:</span>
                            <span class="badge app-show-status-row__badge" :class="role.is_system_role ? 'bg-primary' : 'bg-secondary'">
                                {{ role.is_system_role ? 'System Role' : 'Custom Role' }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Permissions" :hint="`${permissionNames.length} assigned`">
                        <div v-if="permissionNames.length === 0" class="text-muted mb-0">
                            No permissions assigned to this role.
                        </div>
                        <div v-else class="d-flex flex-wrap gap-1">
                            <span
                                v-for="permission in permissionNames"
                                :key="permission"
                                class="badge bg-secondary"
                            >
                                {{ permission }}
                            </span>
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
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { formatDateTime } from '@/utils/showFormatters';

const props = defineProps({
    role: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const permissionNames = computed(() => (props.role.permissions ?? []).map((permission) => permission.name));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.role.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.role.updated_at) },
]));

const deleteRole = () => {
    if (!window.confirm('Are you sure you want to delete this role?')) {
        return;
    }

    router.delete(`/roles/${props.role.id}`);
};
</script>

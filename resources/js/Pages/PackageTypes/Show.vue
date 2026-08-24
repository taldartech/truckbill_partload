<template>
    <AdminLayout :title="`Package Type ${packageType.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Package Type"
                        :identity="packageType.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/package-types"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Package Types"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit package_types')"
                                :href="`/package-types?edit=${packageType.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Package Type"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete package_types')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Package Type"
                                @click="deletePackageType"
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
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    packageType: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Name', value: props.packageType.name },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.packageType.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.packageType.updated_at) },
]));

const deletePackageType = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this package type?');
    if (yes) router.delete(`/package-types/${props.packageType.id}`);
};
</script>
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';
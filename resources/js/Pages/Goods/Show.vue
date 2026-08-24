<template>
    <AdminLayout :title="`Good ${good.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Good"
                        :identity="good.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/goods"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Goods"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit goods')"
                                :href="`/goods?edit=${good.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Good"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete goods')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Good"
                                @click="deleteGood"
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
    good: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Name', value: props.good.name },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.good.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.good.updated_at) },
]));

const deleteGood = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this good?');
    if (yes) {router.delete(`/goods/${props.good.id}`);}
};
</script>

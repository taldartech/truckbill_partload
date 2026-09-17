<template>
    <AdminLayout :title="`Account ${account.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Chart of Account"
                        :identity="account.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/accounting/accounts"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Accounts"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                :href="`/accounting/reports/ledger?account_id=${account.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="View Ledger"
                            >
                                <i class="fas fa-book" aria-hidden="true"></i>
                                <span class="visually-hidden">Ledger</span>
                            </Link>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Selectable:</span>
                            <span class="badge app-show-status-row__badge" :class="account.is_selectable ? 'bg-success' : 'bg-secondary'">
                                {{ account.is_selectable ? 'Yes' : 'No' }}
                            </span>
                            <span class="app-show-status-row__label ms-3">Active:</span>
                            <span class="badge app-show-status-row__badge" :class="account.is_active ? 'bg-success' : 'bg-secondary'">
                                {{ account.is_active ? 'Yes' : 'No' }}
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
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowFieldGrid from '@/Components/Show/ShowFieldGrid.vue';
import ShowSidebar from '@/Components/Show/ShowSidebar.vue';
import { formatDateTime } from '@/utils/showFormatters';

const props = defineProps({
    account: { type: Object, required: true },
});

const overviewFields = computed(() => [
    { label: 'Code', value: props.account.code || '—' },
    { label: 'Group', value: props.account.account_group?.name || '—' },
    { label: 'Nature', value: props.account.account_group?.nature || '—' },
]);

const recordInfoItems = computed(() => [
    { label: 'System account', value: props.account.is_system ? 'Yes' : 'No' },
    { label: 'Created at', value: formatDateTime(props.account.created_at) },
    { label: 'Updated at', value: formatDateTime(props.account.updated_at) },
]);
</script>

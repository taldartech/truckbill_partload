<template>
    <AdminLayout :title="`Bank Account ${bankAccount.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Bank Account"
                        :identity="bankAccount.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/bank-accounts"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Bank Accounts"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit bank_accounts')"
                                :href="`/bank-accounts?edit=${bankAccount.id}`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Bank Account"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete bank_accounts')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Bank Account"
                                @click="deleteBankAccount"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Status:</span>
                            <span class="badge app-show-status-row__badge" :class="bankAccount.is_active ? 'bg-success' : 'bg-secondary'">
                                {{ bankAccount.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Banking">
                        <ShowFieldGrid :fields="bankFields" :columns="3" />
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
    bankAccount: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Company', value: props.bankAccount.company?.name },
    { label: 'Ledger Account', value: props.bankAccount.ledger_account?.name },
]));

const bankFields = computed(() => ([
    { label: 'Bank Name', value: props.bankAccount.bank_name },
    { label: 'Account Number', value: props.bankAccount.account_number },
    { label: 'IFSC Code', value: props.bankAccount.ifsc_code },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.bankAccount.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.bankAccount.updated_at) },
]));

const deleteBankAccount = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this bank account?

Bank Account : ${props.bankAccount.name}
Bank Name    : ${props.bankAccount.bank_name || '—'}
Account No   : ${props.bankAccount.account_number || '—'}`
    );

    if (!remark) return;

    router.delete(`/bank-accounts/${props.bankAccount.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

<template>
    <AdminLayout :title="`Voucher ${voucher.voucher_number || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Voucher"
                        :identity="voucher.voucher_number || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/vouchers"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Vouchers"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit vouchers')"
                                :href="`/vouchers/${voucher.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Voucher"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete vouchers')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Voucher"
                                @click="deleteVoucher"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="voucherFields" :columns="3" />
                    </ShowSection>
                </div>

                <ShowSidebar>
                    <ShowSection title="Amount">
                        <div class="app-show-total">
                            <span class="app-show-total__label">Amount</span>
                            <span class="app-show-total__value">₹{{ formatMoney(voucher.amount) }}</span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Record Info">
                        <div class="app-show-meta-list">
                            <div
                                v-for="item in recordInfoItems"
                                :key="item.label"
                                class="app-show-meta-list__item"
                            >
                                <span class="app-show-meta-list__label">{{ item.label }}</span>
                                <span class="app-show-meta-list__value">{{ item.value }}</span>
                                <span v-if="item.by" class="app-show-meta-list__by">by {{ item.by }}</span>
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
import { formatMoney, formatDate, formatDateTime } from '@/utils/showFormatters';

const props = defineProps({
    voucher: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const entityLabel = computed(() => {
    if (!props.voucher.entity_name) {
        return '—';
    }

    return props.voucher.entity_type_label
        ? `${props.voucher.entity_name} (${props.voucher.entity_type_label})`
        : props.voucher.entity_name;
});

const voucherFields = computed(() => ([
    { label: 'Date', value: formatDate(props.voucher.date) },
    { label: 'Type', value: props.voucher.voucher_type },
    { label: 'Entity', value: entityLabel.value },
    { label: 'Amount', value: `₹${formatMoney(props.voucher.amount)}`, accent: true },
    { label: 'Mode', value: props.voucher.mode === 'bank' ? 'Bank' : 'Cash' },
    { label: 'Bank', value: props.voucher.mode === 'bank' ? props.voucher.bank_account?.name : '' },
    { label: 'Remark', value: props.voucher.remark },
]));

const recordInfoItems = computed(() => ([
    {
        label: 'Created',
        value: formatDateTime(props.voucher.created_at),
        by: props.voucher.creator?.name || '',
    },
    {
        label: 'Last Updated',
        value: formatDateTime(props.voucher.updated_at),
        by: '',
    },
]));

const deleteVoucher = () => {
    if (!window.confirm('Are you sure you want to delete this voucher?')) {
        return;
    }

    router.delete(`/vouchers/${props.voucher.id}`);
};
</script>

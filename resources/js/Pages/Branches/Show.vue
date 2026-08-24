<template>
    <AdminLayout :title="`Branch ${branch.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Branch"
                        :identity="branch.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/branches"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Branches"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit branches')"
                                :href="`/branches/${branch.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Branch"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <Link
                                v-if="can('edit branches')"
                                :href="`/branches/${branch.id}/settings`"
                                class="app-show-action-btn app-show-action-btn--print"
                                title="Branch Settings"
                            >
                                <i class="fas fa-cog" aria-hidden="true"></i>
                                <span class="visually-hidden">Settings</span>
                            </Link>
                            <button
                                v-if="can('delete branches')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Branch"
                                @click="deleteBranch"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                        <div class="app-show-status-row">
                            <span class="app-show-status-row__label">Type:</span>
                            <span class="badge app-show-status-row__badge" :class="branch.is_head_office ? 'bg-primary' : 'bg-secondary'">
                                {{ branch.is_head_office ? 'Head Office' : 'Branch' }}
                            </span>
                        </div>
                    </ShowSection>

                    <ShowSection title="Address">
                        <ShowFieldGrid :fields="addressFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Contact">
                        <ShowFieldGrid :fields="contactFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="GST">
                        <div v-if="branch.has_gst">
                            <ShowFieldGrid :fields="gstFields" :columns="3" />
                        </div>
                        <p v-else class="text-muted mb-0">GST is not applicable for this branch.</p>
                    </ShowSection>

                    <ShowSection v-if="hasBankDetails" title="Banking">
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
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    branch: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Company', value: props.branch.company?.name },
    { label: 'Station', value: props.branch.station?.name },
]));

const addressFields = computed(() => ([
    { label: 'Address', value: props.branch.address },
    { label: 'City', value: props.branch.city?.name },
    { label: 'State', value: props.branch.state?.name },
    { label: 'Pin Code', value: props.branch.pin_code },
]));

const contactFields = computed(() => ([
    { label: 'Contact Person', value: props.branch.contact_person },
    { label: 'Contact Number', value: props.branch.contact_number },
    { label: 'Email', value: props.branch.email },
]));

const gstFields = computed(() => ([
    { label: 'GST Number', value: props.branch.gst_number },
    { label: 'GST Type', value: props.branch.gst_type },
    { label: 'GST Scheme', value: props.branch.gst_scheme },
    { label: 'GST Slab', value: props.branch.gst_slab ? `${props.branch.gst_slab}%` : '' },
    { label: 'GST Exemption Limit', value: props.branch.gst_exemption_limit },
]));

const hasBankDetails = computed(() => !!(props.branch.bank_name || props.branch.account_number || props.branch.ifsc_code));

const bankFields = computed(() => ([
    { label: 'Bank Name', value: props.branch.bank_name },
    { label: 'Account Number', value: props.branch.account_number },
    { label: 'IFSC Code', value: props.branch.ifsc_code },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.branch.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.branch.updated_at) },
]));

const deleteBranch = async () => {
    const yes = await askKeyboardConfirm('Are you sure you want to delete this branch?');
    if (yes) router.delete(`/branches/${props.branch.id}`);
};
</script>

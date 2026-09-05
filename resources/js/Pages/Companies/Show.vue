<template>
    <AdminLayout :title="`Company ${company.name || ''}`">
        <div class="app-show-page">
            <div class="app-show-layout">
                <div class="app-show-main">
                    <ShowSection
                        title="Company"
                        :identity="company.name || ''"
                        first
                    >
                        <template #title-actions>
                            <Link
                                href="/companies"
                                class="app-show-action-btn app-show-action-btn--back"
                                title="Back to Companies"
                            >
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span class="visually-hidden">Back</span>
                            </Link>
                            <Link
                                v-if="can('edit companies')"
                                :href="`/companies/${company.id}/edit`"
                                class="app-show-action-btn app-show-action-btn--edit"
                                title="Edit Company"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="visually-hidden">Edit</span>
                            </Link>
                            <button
                                v-if="can('delete companies')"
                                type="button"
                                class="app-show-action-btn app-show-action-btn--danger"
                                title="Delete Company"
                                @click="deleteCompany"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                <span class="visually-hidden">Delete</span>
                            </button>
                        </template>

                        <ShowFieldGrid :fields="overviewFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Address">
                        <ShowFieldGrid :fields="addressFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="Contact">
                        <ShowFieldGrid :fields="contactFields" :columns="3" />
                    </ShowSection>

                    <ShowSection title="GST">
                        <div v-if="company.has_gst">
                            <ShowFieldGrid :fields="gstFields" :columns="3" />
                        </div>
                        <p v-else class="text-muted mb-0">GST is not applicable for this company.</p>
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
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const overviewFields = computed(() => ([
    { label: 'Email', value: props.company.email },
    { label: 'PAN No', value: props.company.pan_no },
]));

const addressFields = computed(() => ([
    { label: 'Address', value: props.company.address },
    { label: 'City', value: props.company.city?.name },
    { label: 'State', value: props.company.state?.name },
    { label: 'Country', value: props.company.country?.name },
    { label: 'Pin Code', value: props.company.pin_code },
]));

const contactFields = computed(() => ([
    { label: 'Contact Person', value: props.company.contact_person },
    { label: 'Contact Number', value: props.company.contact_number },
]));

const gstFields = computed(() => ([
    { label: 'GST Number', value: props.company.gst_number },
    { label: 'GST Type', value: props.company.gst_type },
    { label: 'GST Scheme', value: props.company.gst_scheme },
    { label: 'GST Slab', value: props.company.gst_slab ? `${props.company.gst_slab}%` : '' },
    { label: 'GST Exemption Limit', value: props.company.gst_exemption_limit },
]));

const hasBankDetails = computed(() => !!(props.company.bank_name || props.company.account_number || props.company.ifsc_code));

const bankFields = computed(() => ([
    { label: 'Bank Name', value: props.company.bank_name },
    { label: 'Account Number', value: props.company.account_number },
    { label: 'IFSC Code', value: props.company.ifsc_code },
]));

const recordInfoItems = computed(() => ([
    { label: 'Created', value: formatDateTime(props.company.created_at) },
    { label: 'Last Updated', value: formatDateTime(props.company.updated_at) },
]));

const deleteCompany = async () => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this company?

Company         : ${props.company.name || '—'}
Email           : ${props.company.email || '—'}
City            : ${props.company.city?.name || '—'}`
    );

    if (!remark) return;

    router.delete(`/companies/${props.company.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

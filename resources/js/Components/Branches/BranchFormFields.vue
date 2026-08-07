<template>
    <FormSection title="Branch Identity" first>
        <div class="row">
            <FormField
                v-if="showCompanyField"
                label="Company"
                field-id="company_id"
                :error="form.errors.company_id"
                required
                col="col-md-4"
            >
                <select id="company_id" class="form-select" v-model="form.company_id" required>
                    <option value="">Select Company</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                    </option>
                </select>
            </FormField>

            <div :class="['app-form-field', 'mb-3', showCompanyField ? 'col-md-4' : 'col-md-6']">
                <div class="app-form-field-label-row">
                    <label for="branch_name" class="form-label mb-0">
                        Branch Name<span class="text-danger"> *</span>
                    </label>
                    <AppFormToggle
                        v-if="showHeadOfficeToggle"
                        input-id="is_head_office"
                        v-model="form.is_head_office"
                        label="Head Office"
                    />
                </div>
                <input id="branch_name" type="text" class="form-control" v-model="form.name" required>
                <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}</div>
            </div>
            <FormField
                label="Station"
                field-id="station_id"
                :error="form.errors.station_id"
                required
                :col="showCompanyField ? 'col-md-4' : 'col-md-6'"
            >
                <SearchableSelect
                    id="station_id"
                    v-model="form.station_id"
                    :options="stationOptions"
                    master="station"
                    placeholder="Select Station"
                    @created="(record) => appendMasterOption(stationOptions, record)"
                />
            </FormField>

        </div>
    </FormSection>

    <FormSection title="Location">
        <template #title-actions>
            <AppFormToggle
                input-id="same_as_company_location"
                v-model="sameAsCompany"
                label="Same as Company"
            />
        </template>
        <div class="row">
            <FormField label="Address" field-id="branch_address" :error="form.errors.address" required col="col-md-8">
                <textarea
                    id="branch_address"
                    class="form-control"
                    v-model="form.address"
                    rows="2"
                    required
                    :disabled="sameAsCompany"
                />
            </FormField>

            <div class="app-form-field mb-3 col-md-4">
                <div class="app-form-field-label-row">
                    <label class="form-label mb-0">Branch Logo</label>
                    <AppFormToggle
                        input-id="use_company_logo"
                        v-model="form.use_company_logo"
                        label="Same as company logo"
                    />
                </div>
                <template v-if="form.use_company_logo && resolvedCompanyLogoUrl">
                    <img :src="resolvedCompanyLogoUrl" alt="Company logo" class="app-form-logo-preview">
                    <div class="form-text">Using company logo</div>
                </template>
                <template v-else-if="!form.use_company_logo">
                    <input id="branch_logo" type="file" class="form-control" accept="image/*" @change="onLogoChange">
                    <img
                        v-if="branchLogoUrl"
                        :src="branchLogoUrl"
                        alt="Current branch logo"
                        class="app-form-logo-preview mt-2"
                    >
                </template>
                <div v-if="form.errors.branch_logo" class="invalid-feedback d-block">{{ form.errors.branch_logo }}</div>
            </div>

            <FormField label="Country" :error="form.errors.country_id" required col="col-md-3">
                <SearchableSelect
                    v-model="form.country_id"
                    :options="countryOptions"
                    master="country"
                    placeholder="Select Country"
                    :disabled="locationLocked"
                    @created="(record) => appendMasterOption(countryOptions, record)"
                />
            </FormField>
            <FormField label="State" :error="form.errors.state_id" required col="col-md-3">
                <SearchableSelect
                    id="state_id"
                    v-model="form.state_id"
                    :options="stateOptions"
                    master="state"
                    :quick-add-context="{ country_id: form.country_id }"
                    placeholder="Select State"
                    :disabled="locationLocked"
                    @created="(record) => appendMasterOption(stateOptions, record)"
                />
            </FormField>
            <FormField label="City" :error="form.errors.city_id" required col="col-md-3">
                <SearchableSelect
                    v-model="form.city_id"
                    :options="filteredCities"
                    master="city"
                    :quick-add-context="{ state_id: form.state_id }"
                    placeholder="Select City"
                    :disabled="locationLocked || !form.state_id"
                    @created="(record) => appendMasterOption(cityOptions, record)"
                />
            </FormField>
            <FormField label="PIN Code" field-id="branch_pin_code" :error="form.errors.pin_code" required col="col-md-3">
                <input
                    id="branch_pin_code"
                    type="text"
                    class="form-control"
                    v-model="form.pin_code"
                    required
                    :disabled="sameAsCompany"
                >
            </FormField>
        </div>
    </FormSection>

    <FormSection title="Contact">
        <div class="row">
            <FormField label="Email Address" field-id="branch_email" :error="form.errors.email" required col="col-md-4">
                <input id="branch_email" type="email" class="form-control" v-model="form.email" required>
            </FormField>
            <FormField label="Contact Person" field-id="branch_contact_person" :error="form.errors.contact_person" required col="col-md-4">
                <input id="branch_contact_person" type="text" class="form-control" v-model="form.contact_person" required>
            </FormField>
            <FormField label="Contact Number" field-id="branch_contact_number" :error="form.errors.contact_number" required col="col-md-4">
                <input id="branch_contact_number" type="text" class="form-control" v-model="form.contact_number" required>
            </FormField>
        </div>
    </FormSection>

    <FormSection title="GST">
        <template #title-actions>
            <div class="app-form-section__actions">
                <AppFormToggle
                    input-id="has_gst"
                    v-model="form.has_gst"
                    label="Do you have GST?"
                />
                <AppFormToggle
                    v-if="form.has_gst && companyHasGstDetails"
                    input-id="same_as_company_gst"
                    v-model="sameAsCompanyGst"
                    label="Same as Company"
                />
            </div>
        </template>
        <div v-if="form.has_gst" class="row">
            <FormField label="GST Number" field-id="gst_number" :error="form.errors.gst_number" required col="col-md-3">
                <input
                    id="gst_number"
                    type="text"
                    class="form-control"
                    v-model="form.gst_number"
                    required
                    :readonly="gstFieldsReadonly"
                >
            </FormField>
            <FormField label="GST Type" field-id="gst_type" required col="col-md-3">
                <select
                    id="gst_type"
                    class="form-select"
                    v-model="form.gst_type"
                    required
                    :disabled="gstFieldsReadonly"
                >
                    <option value="">Select</option>
                    <option value="Forward">Forward</option>
                    <option value="Reverse">Reverse</option>
                </select>
            </FormField>
            <FormField label="GST Scheme" field-id="gst_scheme" required col="col-md-3">
                <select
                    id="gst_scheme"
                    class="form-select"
                    v-model="form.gst_scheme"
                    required
                    :disabled="gstFieldsReadonly"
                >
                    <option value="">Select</option>
                    <option value="Regular">Regular</option>
                    <option value="Composition">Composition</option>
                </select>
            </FormField>
            <FormField label="GST Slab (%)" field-id="gst_slab" :error="form.errors.gst_slab" required col="col-md-3">
                <input
                    id="gst_slab"
                    type="number"
                    step="0.01"
                    class="form-control"
                    v-model="form.gst_slab"
                    required
                    :readonly="gstFieldsReadonly"
                >
            </FormField>
            <FormField label="GST Exemption Limit" field-id="gst_exemption_limit" :error="form.errors.gst_exemption_limit" required col="col-md-3">
                <input
                    id="gst_exemption_limit"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    v-model="form.gst_exemption_limit"
                    required
                    :readonly="gstFieldsReadonly"
                >
            </FormField>
        </div>
    </FormSection>

    <FormSection
        title="Bank Details"
        hint="Used to be printed on invoice, delivery receipt and gatepass"
    >
        <template v-if="companyHasBankDetails" #title-actions>
            <AppFormToggle
                input-id="same_as_company_bank"
                v-model="sameAsCompanyBank"
                label="Same as Company"
            />
        </template>
        <div class="row">
            <FormField label="Bank Name" field-id="bank_name" col="col-md-4">
                <input
                    id="bank_name"
                    type="text"
                    class="form-control"
                    v-model="form.bank_name"
                    :readonly="bankFieldsReadonly"
                >
            </FormField>
            <FormField label="Account Number" field-id="account_number" col="col-md-4">
                <input
                    id="account_number"
                    type="text"
                    class="form-control"
                    v-model="form.account_number"
                    :readonly="bankFieldsReadonly"
                >
            </FormField>
            <FormField label="IFSC Code" field-id="ifsc_code" col="col-md-4">
                <input
                    id="ifsc_code"
                    type="text"
                    class="form-control"
                    v-model="form.ifsc_code"
                    :readonly="bankFieldsReadonly"
                >
            </FormField>
        </div>
    </FormSection>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormField from '@/Components/Forms/FormField.vue';
import AppFormToggle from '@/Components/Forms/AppFormToggle.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';

const props = defineProps({
    form: { type: Object, required: true },
    companies: { type: Array, default: () => [] },
    stationOptions: { type: Array, required: true },
    countryOptions: { type: Array, required: true },
    stateOptions: { type: Array, required: true },
    cityOptions: { type: Array, required: true },
    filteredCities: { type: Array, required: true },
    showCompanyField: { type: Boolean, default: false },
    branchId: { type: [Number, String], default: null },
    companyLogoUrl: { type: String, default: '' },
    branchLogoUrl: { type: String, default: '' },
});

const emit = defineEmits(['logo-change']);
const page = usePage();
const sameAsCompany = ref(false);
const sameAsCompanyGst = ref(false);
const sameAsCompanyBank = ref(false);

const selectedCompany = computed(() => {
    const companyId = props.form.company_id || page.props.auth?.active_company?.id;

    if (companyId) {
        return props.companies.find((company) => String(company.id) === String(companyId)) || null;
    }

    return props.companies.length === 1 ? props.companies[0] : null;
});

const showHeadOfficeToggle = computed(() => {
    const headOfficeBranchId = selectedCompany.value?.head_office_branch_id;

    if (!headOfficeBranchId) {
        return true;
    }

    return props.branchId != null && String(headOfficeBranchId) === String(props.branchId);
});

watch(showHeadOfficeToggle, (visible) => {
    if (!visible) {
        props.form.is_head_office = false;
    }
}, { immediate: true });

const resolvedCompanyLogoUrl = computed(
    () => props.companyLogoUrl || selectedCompany.value?.company_logo || '',
);

const companyHasGstDetails = computed(() => {
    const company = selectedCompany.value;

    if (!company) {
        return false;
    }

    return !!company.has_gst;
});

const companyHasBankDetails = computed(() => {
    const company = selectedCompany.value;

    if (!company) {
        return false;
    }

    return !!(
        (company.bank_name && String(company.bank_name).trim() !== '')
        || (company.account_number && String(company.account_number).trim() !== '')
        || (company.ifsc_code && String(company.ifsc_code).trim() !== '')
    );
});

const gstFieldsReadonly = computed(() => sameAsCompanyGst.value && companyHasGstDetails.value);
const bankFieldsReadonly = computed(() => sameAsCompanyBank.value && companyHasBankDetails.value);

const locationFromStation = computed(() => !!props.form.station_id && !sameAsCompany.value);
const locationLocked = computed(() => sameAsCompany.value || locationFromStation.value);

const resolveCountryId = (station) => {
    if (station.country_id) {
        return station.country_id;
    }

    if (!station.state_id) {
        return '';
    }

    const state = props.stateOptions.find((item) => String(item.id) === String(station.state_id));

    return state?.country_id || '';
};

const applyLocationFromStation = (stationId) => {
    if (!stationId || sameAsCompany.value) {
        return;
    }

    const station = props.stationOptions.find((item) => String(item.id) === String(stationId));

    if (!station) {
        return;
    }

    const countryId = resolveCountryId(station);

    if (countryId) {
        props.form.country_id = countryId;
    }

    if (station.state_id) {
        props.form.state_id = station.state_id;
    }

    if (station.city_id) {
        props.form.city_id = station.city_id;
    }
};

const applyLocationFromCompany = () => {
    const company = selectedCompany.value;

    if (!company) {
        return;
    }

    props.form.address = company.address || '';
    props.form.country_id = company.country_id || '';
    props.form.state_id = company.state_id || '';
    props.form.city_id = company.city_id || '';
    props.form.pin_code = company.pin_code || '';
};

const applyGstFromCompany = () => {
    const company = selectedCompany.value;

    if (!company) {
        return;
    }

    props.form.gst_number = company.gst_number || '';
    props.form.gst_type = company.gst_type || '';
    props.form.gst_scheme = company.gst_scheme || '';
    props.form.gst_slab = company.gst_slab ?? '';
    props.form.gst_exemption_limit = company.gst_exemption_limit ?? '';
};

const applyBankFromCompany = () => {
    const company = selectedCompany.value;

    if (!company) {
        return;
    }

    props.form.bank_name = company.bank_name || '';
    props.form.account_number = company.account_number || '';
    props.form.ifsc_code = company.ifsc_code || '';
};

watch(sameAsCompany, (enabled) => {
    if (enabled) {
        applyLocationFromCompany();
        return;
    }

    if (props.form.station_id) {
        applyLocationFromStation(props.form.station_id);
    }
});

watch(sameAsCompanyGst, (enabled) => {
    if (enabled) {
        applyGstFromCompany();
    }
});

watch(sameAsCompanyBank, (enabled) => {
    if (enabled) {
        applyBankFromCompany();
    }
});

watch(
    () => props.form.has_gst,
    (hasGst) => {
        if (!hasGst) {
            sameAsCompanyGst.value = false;
        }
    },
);

watch(companyHasGstDetails, (hasDetails) => {
    if (!hasDetails) {
        sameAsCompanyGst.value = false;
    }
});

watch(companyHasBankDetails, (hasDetails) => {
    if (!hasDetails) {
        sameAsCompanyBank.value = false;
    }
});

watch(
    () => [props.form.company_id, selectedCompany.value],
    () => {
        if (!companyHasGstDetails.value) {
            sameAsCompanyGst.value = false;
        }

        if (!companyHasBankDetails.value) {
            sameAsCompanyBank.value = false;
        }

        if (sameAsCompany.value) {
            applyLocationFromCompany();
        }

        if (sameAsCompanyGst.value) {
            applyGstFromCompany();
        }

        if (sameAsCompanyBank.value) {
            applyBankFromCompany();
        }
    },
);

watch(
    () => props.form.station_id,
    (stationId) => {
        applyLocationFromStation(stationId);
    },
    { immediate: true },
);

watch(
    () => props.stationOptions,
    () => {
        if (props.form.station_id) {
            applyLocationFromStation(props.form.station_id);
        }
    },
    { deep: true },
);

const onLogoChange = (event) => {
    emit('logo-change', event);
};
</script>

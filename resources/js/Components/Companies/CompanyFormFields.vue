<template>
    <FormSection title="Company Information" first>
        <div class="row">
            <FormField label="Company Name" field-id="company_name" :error="form.errors.name" required col="col-md-8">
                <input id="company_name" type="text" class="form-control" v-model="form.name" required>
            </FormField>
            <FormField label="PAN Number" field-id="company_pan_no" col="col-md-4">
                <input id="company_pan_no" type="text" class="form-control text-uppercase" v-model="form.pan_no" maxlength="10">
            </FormField>
            <FormField label="Address" field-id="company_address" :error="form.errors.address" required col="col-md-8">
                <textarea id="company_address" class="form-control" v-model="form.address" rows="2" required />
            </FormField>
            <FormField label="Upload Logo" field-id="company_logo" :error="form.errors.company_logo" col="col-md-4" hint="Optional. JPG, PNG, or WEBP.">
                <input id="company_logo" type="file" class="form-control" accept="image/*" @change="onLogoChange">
                <img
                    v-if="logoPreviewUrl"
                    :src="logoPreviewUrl"
                    alt="Current logo"
                    class="app-form-logo-preview mt-2"
                >
            </FormField>
            <FormField label="Country" :error="form.errors.country_id" required col="col-md-3">
                <SearchableSelect
                    v-model="form.country_id"
                    :options="countryOptions"
                    master="country"
                    placeholder="Select Country"
                    @created="(record) => appendMasterOption(countryOptions, record)"
                />
            </FormField>
            <FormField label="State" :error="form.errors.state_id" required col="col-md-3">
                <SearchableSelect
                    v-model="form.state_id"
                    :options="stateOptions"
                    master="state"
                    :quick-add-context="{ country_id: form.country_id }"
                    placeholder="Select State"
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
                    :disabled="!form.state_id"
                    @created="(record) => appendMasterOption(cityOptions, record)"
                />
            </FormField>
            <FormField label="PIN Code" field-id="company_pin_code" :error="form.errors.pin_code" required col="col-md-3">
                <input id="company_pin_code" type="text" class="form-control" v-model="form.pin_code" required>
            </FormField>
        </div>
    </FormSection>

    <FormSection title="Contact">
        <div class="row">
            <FormField label="Email Address" field-id="company_email" :error="form.errors.email" required col="col-md-4">
                <input id="company_email" type="email" class="form-control" v-model="form.email" required>
            </FormField>
            <FormField label="Contact Person" field-id="company_contact_person" :error="form.errors.contact_person" required col="col-md-4">
                <input id="company_contact_person" type="text" class="form-control" v-model="form.contact_person" required>
            </FormField>
            <FormField label="Contact Number" field-id="company_contact_number" :error="form.errors.contact_number" required col="col-md-4">
                <input id="company_contact_number" type="text" class="form-control" v-model="form.contact_number" required>
            </FormField>
        </div>
    </FormSection>

    <FormSection title="GST">
        <template #title-actions>
            <AppFormToggle
                input-id="company_has_gst"
                v-model="form.has_gst"
                label="Do you have GST?"
            />
        </template>
        <div v-if="form.has_gst" class="row">
            <FormField label="GST Number" field-id="company_gst_number" :error="form.errors.gst_number" required col="col-md-3">
                <input id="company_gst_number" type="text" class="form-control" v-model="form.gst_number" required>
            </FormField>
            <FormField label="GST Type" field-id="company_gst_type" required col="col-md-3">
                <select id="company_gst_type" class="form-select" v-model="form.gst_type" required>
                    <option value="">Select</option>
                    <option value="Forward">Forward</option>
                    <option value="Reverse">Reverse</option>
                </select>
            </FormField>
            <FormField label="GST Scheme" field-id="company_gst_scheme" required col="col-md-3">
                <select id="company_gst_scheme" class="form-select" v-model="form.gst_scheme" required>
                    <option value="">Select</option>
                    <option value="Regular">Regular</option>
                    <option value="Composition">Composition</option>
                </select>
            </FormField>
            <FormField label="GST Slab (%)" field-id="company_gst_slab" :error="form.errors.gst_slab" required col="col-md-3">
                <input id="company_gst_slab" type="number" step="0.01" class="form-control" v-model="form.gst_slab" required>
            </FormField>
        </div>
    </FormSection>

    <FormSection
        title="Bank Details"
        hint="Used to be printed on invoice, delivery receipt and gatepass"
    >
        <div class="row">
            <FormField label="Bank Name" field-id="company_bank_name" :error="form.errors.bank_name" col="col-md-4">
                <input id="company_bank_name" type="text" class="form-control" v-model="form.bank_name">
            </FormField>
            <FormField label="Account Number" field-id="company_account_number" :error="form.errors.account_number" col="col-md-4">
                <input id="company_account_number" type="text" class="form-control" v-model="form.account_number">
            </FormField>
            <FormField label="IFSC Code" field-id="company_ifsc_code" :error="form.errors.ifsc_code" col="col-md-4">
                <input id="company_ifsc_code" type="text" class="form-control text-uppercase" v-model="form.ifsc_code">
            </FormField>
        </div>
    </FormSection>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormField from '@/Components/Forms/FormField.vue';
import AppFormToggle from '@/Components/Forms/AppFormToggle.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';

defineProps({
    form: { type: Object, required: true },
    countryOptions: { type: Array, required: true },
    stateOptions: { type: Array, required: true },
    cityOptions: { type: Array, required: true },
    filteredCities: { type: Array, required: true },
    logoPreviewUrl: { type: String, default: '' },
});

const emit = defineEmits(['logo-change']);

const onLogoChange = (event) => {
    emit('logo-change', event);
};
</script>

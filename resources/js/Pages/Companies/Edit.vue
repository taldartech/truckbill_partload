<template>
    <AdminLayout title="Edit Company">
        <FormPageCard
            title="Update Company Details"
            cancel-href="/companies"
            submit-label="Update Company"
            submit-class="btn-warning"
            :processing="form.processing"
            enctype="multipart/form-data"
            column-class="col-md-10 mx-auto"
            @submit="submit"
        >
            <CompanyFormFields
                :form="form"
                :country-options="countryOptions"
                :state-options="stateOptions"
                :city-options="cityOptions"
                :filtered-cities="filteredCities"
                :logo-preview-url="company.company_logo || ''"
                @logo-change="onLogoChange"
            />
        </FormPageCard>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormPageCard from '@/Components/Forms/FormPageCard.vue';
import CompanyFormFields from '@/Components/Companies/CompanyFormFields.vue';

const props = defineProps({
    company: Object,
    countries: Array,
    states: Array,
    cities: Array,
    defaultCountryId: [Number, String, null],
});

const countryOptions = ref([...(props.countries || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.countries, (v) => { countryOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });

const form = useForm({
    name: props.company.name || '',
    address: props.company.address || '',
    country_id: props.company.country_id || props.defaultCountryId || '',
    state_id: props.company.state_id || '',
    city_id: props.company.city_id || '',
    pin_code: props.company.pin_code || '',
    email: props.company.email || '',
    contact_person: props.company.contact_person || '',
    contact_number: props.company.contact_number || '',
    pan_no: props.company.pan_no || '',
    has_gst: !!props.company.has_gst,
    gst_number: props.company.gst_number || '',
    gst_type: props.company.gst_type || '',
    gst_scheme: props.company.gst_scheme || '',
    gst_slab: props.company.gst_slab || '',
    gst_exemption_limit: props.company.gst_exemption_limit ?? '',
    bank_name: props.company.bank_name || '',
    account_number: props.company.account_number || '',
    ifsc_code: props.company.ifsc_code || '',
    company_logo: null,
});

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return cityOptions.value.filter(city => String(city.state_id) === String(form.state_id));
});

watch(() => form.state_id, () => {
    if (form.city_id) {
        const cityExists = filteredCities.value.some(city => String(city.id) === String(form.city_id));
        if (!cityExists) {
            form.city_id = '';
        }
    }
});

const onLogoChange = (event) => {
    form.company_logo = event.target.files[0];
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/companies/${props.company.id}`, {
        forceFormData: true,
    });
};
</script>

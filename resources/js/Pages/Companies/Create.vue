<template>
    <AdminLayout title="Add Company">
        <FormPageCard
            title="Company Details"
            cancel-href="/companies"
            submit-label="Save Company"
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
    name: '',
    address: '',
    country_id: props.defaultCountryId || '',
    state_id: '',
    city_id: '',
    pin_code: '',
    email: '',
    contact_person: '',
    contact_number: '',
    pan_no: '',
    has_gst: false,
    gst_number: '',
    gst_type: '',
    gst_scheme: '',
    gst_slab: '',
    gst_exemption_limit: '',
    bank_name: '',
    account_number: '',
    ifsc_code: '',
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
    form.post('/companies', {
        forceFormData: true,
    });
};
</script>

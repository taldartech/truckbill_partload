<template>
    <AdminLayout title="Add Branch">
        <FormPageCard
            title="Branch Details"
            cancel-href="/branches"
            submit-label="Save Branch"
            :processing="form.processing"
            enctype="multipart/form-data"
            column-class="col-md-10 mx-auto"
            @submit="submit"
        >
            <BranchFormFields
                :form="form"
                :companies="companies"
                :station-options="stationOptions"
                :country-options="countryOptions"
                :state-options="stateOptions"
                :city-options="cityOptions"
                :filtered-cities="filteredCities"
                :show-company-field="showCompanyField"
                @logo-change="onLogoChange"
            />
        </FormPageCard>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormPageCard from '@/Components/Forms/FormPageCard.vue';
import BranchFormFields from '@/Components/Branches/BranchFormFields.vue';

const props = defineProps({
    companies: Array,
    stations: Array,
    countries: Array,
    states: Array,
    cities: Array,
    defaultCountryId: [Number, String, null],
});

const page = usePage();
const showCompanyField = computed(() => !!page.props.auth?.is_admin);

const stationOptions = ref([...(props.stations || [])]);
const countryOptions = ref([...(props.countries || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.countries, (v) => { countryOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });

const form = useForm({
    company_id: '',
    station_id: '',
    name: '',
    address: '',
    country_id: props.defaultCountryId || '',
    state_id: '',
    city_id: '',
    pin_code: '',
    is_head_office: false,
    has_gst: false,
    gst_number: '',
    gst_type: '',
    gst_scheme: '',
    gst_slab: '',
    bank_name: '',
    account_number: '',
    ifsc_code: '',
    contact_person: '',
    contact_number: '',
    email: '',
    use_company_logo: true,
    branch_logo: null,
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
    form.branch_logo = event.target.files[0];
};

const submit = () => {
    form.post('/branches', {
        forceFormData: true,
    });
};
</script>


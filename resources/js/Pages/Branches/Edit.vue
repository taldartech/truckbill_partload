<template>
    <AdminLayout title="Edit Branch">
        <FormPageCard
            title="Update Branch Details"
            cancel-href="/branches"
            submit-label="Update Branch"
            submit-class="btn-warning"
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
                :company-logo-url="branch.company?.company_logo || ''"
                :branch-logo-url="branch.branch_logo || ''"
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
    branch: Object,
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
    company_id: props.branch.company_id || '',
    station_id: props.branch.station_id || '',
    name: props.branch.name || '',
    address: props.branch.address || '',
    country_id: props.branch.country_id || props.defaultCountryId || '',
    state_id: props.branch.state_id || '',
    city_id: props.branch.city_id || '',
    pin_code: props.branch.pin_code || '',
    is_head_office: !!props.branch.is_head_office,
    has_gst: !!props.branch.has_gst,
    gst_number: props.branch.gst_number || '',
    gst_type: props.branch.gst_type || '',
    gst_scheme: props.branch.gst_scheme || '',
    gst_slab: props.branch.gst_slab || '',
    gst_exemption_limit: props.branch.gst_exemption_limit ?? '',
    bank_name: props.branch.bank_name || '',
    account_number: props.branch.account_number || '',
    ifsc_code: props.branch.ifsc_code || '',
    contact_person: props.branch.contact_person || '',
    contact_number: props.branch.contact_number || '',
    email: props.branch.email || '',
    use_company_logo: props.branch.use_company_logo !== false,
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
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/branches/${props.branch.id}`, {
        forceFormData: true,
    });
};
</script>

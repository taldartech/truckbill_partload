<template>
    <AdminLayout title="Edit Company">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Company Details</h3>
                    </div>
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="card-body">
                            <h5 class="text-primary border-bottom pb-2 mb-3">Company Information</h5>
                            <div class="row">
                                <div class="col-md-12 form-group mb-3">
                                    <label>Company Name *</label>
                                    <input type="text" class="form-control" v-model="form.name" required>
                                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                                </div>
                                <div class="col-md-12 form-group mb-3">
                                    <label>Address *</label>
                                    <textarea class="form-control" v-model="form.address" rows="2" required></textarea>
                                    <div v-if="form.errors.address" class="text-danger small">{{ form.errors.address }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Country *</label>
                                    <SearchableSelect
                                        v-model="form.country_id"
                                        :options="countryOptions"
                                        master="country"
                                        placeholder="Select Country"
                                        @created="(record) => appendMasterOption(countryOptions, record)"
                                    />
                                    <div v-if="form.errors.country_id" class="text-danger small">{{ form.errors.country_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="stateOptions"
                                        master="state"
                                        :quick-add-context="{ country_id: form.country_id }"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>City *</label>
                                    <SearchableSelect
                                        v-model="form.city_id"
                                        :options="filteredCities"
                                        master="city"
                                        :quick-add-context="{ state_id: form.state_id }"
                                        placeholder="Select City"
                                        :disabled="!form.state_id"
                                        @created="(record) => appendMasterOption(cityOptions, record)"
                                    />
                                    <div v-if="form.errors.city_id" class="text-danger small">{{ form.errors.city_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>PIN Code *</label>
                                    <input type="text" class="form-control" v-model="form.pin_code" required>
                                    <div v-if="form.errors.pin_code" class="text-danger small">{{ form.errors.pin_code }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Contact</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Email Address *</label>
                                    <input type="email" class="form-control" v-model="form.email" required>
                                    <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Person *</label>
                                    <input type="text" class="form-control" v-model="form.contact_person" required>
                                    <div v-if="form.errors.contact_person" class="text-danger small">{{ form.errors.contact_person }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Number *</label>
                                    <input type="text" class="form-control" v-model="form.contact_number" required>
                                    <div v-if="form.errors.contact_number" class="text-danger small">{{ form.errors.contact_number }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">PAN</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>PAN Number</label>
                                    <input type="text" class="form-control" v-model="form.pan_no">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Company Logo</h5>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Upload Logo</label>
                                    <input type="file" class="form-control" @change="onLogoChange" accept="image/*">
                                    <div v-if="company.company_logo" class="mt-2">
                                        <img :src="company.company_logo" alt="Current Logo" class="img-thumbnail" style="max-height: 80px;">
                                        <small class="text-muted d-block mt-1">Current Logo</small>
                                    </div>
                                    <div v-if="form.errors.company_logo" class="text-danger small">{{ form.errors.company_logo }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between bg-light">
                            <Link href="/companies" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Company
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';

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

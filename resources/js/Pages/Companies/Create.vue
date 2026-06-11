<template>
    <AdminLayout title="Add Company">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Company Details</h3>
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
                                    <select class="form-control" v-model="form.country_id" required>
                                        <option value="">Select Country</option>
                                        <option v-for="country in countries" :key="country.id" :value="country.id">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.country_id" class="text-danger small">{{ form.errors.country_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="states"
                                        placeholder="Select State"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>City *</label>
                                    <select class="form-control" v-model="form.city_id" required>
                                        <option value="">Select City</option>
                                        <option v-for="city in filteredCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
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
                                    <div v-if="form.errors.company_logo" class="text-danger small">{{ form.errors.company_logo }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between bg-light">
                            <Link href="/companies" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Company
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    countries: Array,
    states: Array,
    cities: Array,
    defaultCountryId: [Number, String, null],
});

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
    company_logo: null,
});

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return props.cities.filter(city => String(city.state_id) === String(form.state_id));
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

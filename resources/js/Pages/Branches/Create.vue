<template>
    <AdminLayout title="Add Branch">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Branch Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div v-if="$page.props.auth.is_admin" class="form-group mb-3">
                                <label for="company_id">Company *</label>
                                <select class="form-control" id="company_id" v-model="form.company_id" >
                                    <option value="">Select Company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.company_id" class="text-danger mt-1 small">{{ form.errors.company_id }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="name">Branch Name</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter Branch Name">
                            </div>

                            <div class="form-group mb-3">
                                <label for="station_id">Station *</label>
                                <SearchableSelect
                                    v-model="form.station_id"
                                    :options="filteredStations"
                                    placeholder="Select Station"
                                    :disabled="!resolvedCompanyId"
                                />
                                <div v-if="!resolvedCompanyId" class="text-muted mt-1 small">Select a company first.</div>
                                <div v-if="form.errors.station_id" class="text-danger mt-1 small">{{ form.errors.station_id }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" v-model="form.address" rows="2" placeholder="Enter Address"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Country</label>
                                    <select class="form-control" v-model="form.country_id">
                                        <option value="">Select Country</option>
                                        <option v-for="country in countries" :key="country.id" :value="country.id">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="states"
                                        placeholder="Select State"
                                    />
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>City</label>
                                    <select class="form-control" v-model="form.city_id">
                                        <option value="">Select City</option>
                                        <option v-for="city in filteredCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>PIN Code</label>
                                    <input type="text" class="form-control" v-model="form.pin_code">
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_head_office" v-model="form.is_head_office">
                                <label class="form-check-label" for="is_head_office">Head Office</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/branches" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Branch
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
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const page = usePage();

const props = defineProps({
    companies: Array,
    stations: Array,
    countries: Array,
    states: Array,
    cities: Array
});

const form = useForm({
    company_id: '',
    station_id: '',
    name: '',
    address: '',
    country_id: '',
    state_id: '',
    city_id: '',
    pin_code: '',
    is_head_office: false
});


const filteredStations = computed(() => props.stations);

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return props.cities.filter(city => String(city.state_id) === String(form.state_id));
});

// Auto-reset city_id if selected state changes and the city doesn't belong to it
watch(() => form.state_id, (newStateId) => {
    if (form.city_id) {
        const cityExists = filteredCities.value.some(city => String(city.id) === String(form.city_id));
        if (!cityExists) {
            form.city_id = '';
        }
    }
});

const submit = () => {
    form.post('/branches');
};
</script>

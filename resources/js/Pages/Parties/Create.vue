<template>
    <AdminLayout title="Add Party">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Party Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row">
                                <div v-if="$page.props.auth.is_admin" class="col-md-6 form-group mb-3">
                                    <label>Company *</label>
                                    <select class="form-control" v-model="form.company_id" >
                                        <option value="">Select Company</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.company_id" class="text-danger small">{{ form.errors.company_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Group</label>
                                    <select class="form-control" v-model="form.group_id">
                                        <option value="">No Group</option>
                                        <option v-for="group in groups" :key="group.id" :value="group.id">
                                            {{ group.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 form-group mb-3">
                                    <label>Party Name *</label>
                                    <input type="text" class="form-control" v-model="form.name" required>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Type</label>
                                    <select class="form-control" v-model="form.type">
                                        <option value="Consignor">Consignor</option>
                                        <option value="Consignee">Consignee</option>
                                        <option value="Both">Both</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 form-group mb-3">
                                    <label>Address</label>
                                    <input type="text" class="form-control" v-model="form.address">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>State</label>
                                    <SearchableSelect
    v-model="form.state_id"
    :options="states"
    placeholder="Select State"
/>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>City</label>
                                    <SearchableSelect
    v-model="form.city_id"
    :options="filteredCities"
    placeholder="Select City"
:disabled="!form.state_id"
/>
                                </div>
                                <div class="col-md-8 form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Mobile</label>
                                    <input type="text" class="form-control" v-model="form.mobile">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Mobile (SMS)</label>
                                    <input type="text" class="form-control" v-model="form.mobile_sms">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>GST Number</label>
                                    <input type="text" class="form-control" v-model="form.gst_number">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Bilty Charge (Fixed)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.bilty_charge">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/parties" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Party
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    companies: Array,
    groups: Array,
    cities: Array,
    states: Array
});

const form = useForm({
    company_id: '',
    group_id: '',
    name: '',
    type: 'Both',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    mobile_sms: '',
    email: '',
    gst_number: '',
    bilty_charge: ''
});


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
    form.post('/parties');
};
</script>

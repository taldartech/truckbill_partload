<template>
    <AdminLayout title="Add Driver">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Driver Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Basic Details -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Driver Name *</label>
                                    <input type="text" class="form-control" v-model="form.name" required>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Father's Name</label>
                                    <input type="text" class="form-control" v-model="form.father_name">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Mobile</label>
                                    <input type="text" class="form-control" v-model="form.mobile">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                </div>
                            </div>

                            <!-- Address & KYC -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Address & KYC</h5>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Address</label>
                                    <input type="text" class="form-control" v-model="form.address">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="stateOptions"
                                        master="state"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>City</label>
                                    <SearchableSelect
                                        v-model="form.city_id"
                                        :options="filteredCities"
                                        master="city"
                                        :quick-add-context="{ state_id: form.state_id }"
                                        placeholder="Select City"
                                        :disabled="!form.state_id"
                                        @created="(record) => appendMasterOption(cityOptions, record)"
                                    />
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Aadhar Number</label>
                                    <input type="text" class="form-control" v-model="form.aadhar_no">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>PAN Number</label>
                                    <input type="text" class="form-control" v-model="form.pan_no">
                                </div>
                            </div>

                            <!-- License Details -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">License Information</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>License Number</label>
                                    <input type="text" class="form-control" v-model="form.license_number">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.license_expiry_date">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Verification Status</label>
                                    <input type="text" class="form-control" v-model="form.license_verification">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>RTO Name</label>
                                    <input type="text" class="form-control" v-model="form.rto_name">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>RTO State</label>
                                    <input type="text" class="form-control" v-model="form.rto_state">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/drivers" class="btn btn-back">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Driver
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    cities: Array,
    states: Array
});

const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });


const form = useForm({
    name: '',
    father_name: '',
    mobile: '',
    email: '',
    address: '',
    city_id: '',
    state_id: '',
    license_number: '',
    license_expiry_date: '',
    aadhar_no: '',
    pan_no: '',
    rto_name: '',
    rto_state: '',
    license_verification: ''
});


const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return cityOptions.value.filter(city => String(city.state_id) === String(form.state_id));
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
    form.post('/drivers');
};
</script>
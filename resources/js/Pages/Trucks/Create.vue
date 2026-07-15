<template>
    <AdminLayout title="Add Truck">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Truck Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Core Info -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Core Information</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Truck Number *</label>
                                    <input
                                        type="text"
                                        class="form-control text-uppercase"
                                        :value="form.truck_number"
                                        placeholder="e.g. MH04AB1234"
                                        required
                                        @input="onTruckNumberInput"
                                    >
                                    <div v-if="form.errors.truck_number" class="text-danger small">{{ form.errors.truck_number }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Truck Type</label>
                                    <SearchableSelect
                                        v-model="form.truck_type_id"
                                        :options="truckTypeOptions"
                                        master="truck_type"
                                        placeholder="Select Type"
                                        @created="(record) => appendMasterOption(truckTypeOptions, record)"
                                    />
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Capacity (in Tons)</label>
                                    <input type="text" class="form-control" v-model="form.capacity" placeholder="e.g. 15">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Status</label>
                                    <select class="form-control" v-model="form.status">
                                        <option value="Available">Available</option>
                                        <option value="Booked">Booked</option>
                                        <option value="In Transit">In Transit</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Current Location Details -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Current Location</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Current State</label>
                                    <SearchableSelect
                                        v-model="form.current_state_id"
                                        :options="stateOptions"
                                        master="state"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Current City</label>
                                    <SearchableSelect
                                        v-model="form.current_city_id"
                                        :options="filteredCities"
                                        master="city"
                                        :quick-add-context="{ state_id: form.current_state_id }"
                                        placeholder="Select City"
                                        :disabled="!form.current_state_id"
                                        @created="(record) => appendMasterOption(cityOptions, record)"
                                    />
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Current Station</label>
                                    <SearchableSelect
                                        v-model="form.current_station_id"
                                        :options="filteredStations"
                                        master="station"
                                        placeholder="Select Station"
                                        :disabled="!form.current_city_id"
                                        @created="(record) => appendMasterOption(stationOptions, record)"
                                    />
                                </div>
                            </div>

                            <!-- Owner Details -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Owner Details</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Owner Name</label>
                                    <input type="text" class="form-control" v-model="form.owner_name">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Owner Mobile</label>
                                    <input type="text" class="form-control" v-model="form.owner_mobile">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Owner Email</label>
                                    <input type="email" class="form-control" v-model="form.owner_email">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Owner Address</label>
                                    <input type="text" class="form-control" v-model="form.owner_address">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Owner City</label>
                                    <input type="text" class="form-control" v-model="form.owner_city">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Owner State</label>
                                    <input type="text" class="form-control" v-model="form.owner_state">
                                </div>
                            </div>

                            <!-- Compliance & Documentation -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Compliance & Documents</h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>RC Number</label>
                                    <input type="text" class="form-control" v-model="form.rc_no">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>RC Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.rc_expiry_date">
                                </div>

                                <div class="col-md-3 form-group mb-3">
                                    <label>Insurance No.</label>
                                    <input type="text" class="form-control" v-model="form.insurance_no">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Insurance Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.insurance_expiry_date">
                                </div>

                                <div class="col-md-3 form-group mb-3">
                                    <label>Fitness No.</label>
                                    <input type="text" class="form-control" v-model="form.fitness_no">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Fitness Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.fitness_expiry_date">
                                </div>

                                <div class="col-md-3 form-group mb-3">
                                    <label>Pollution No.</label>
                                    <input type="text" class="form-control" v-model="form.pollution_no">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Pollution Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.pollution_expiry_date">
                                </div>

                                <div class="col-md-3 form-group mb-3">
                                    <label>Permit No.</label>
                                    <input type="text" class="form-control" v-model="form.permit_no">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Permit Expiry Date</label>
                                    <input type="date" class="form-control" v-model="form.permit_expiry_date">
                                </div>
                            </div>

                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/trucks" class="btn btn-back">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Truck
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
    truckTypes: Array,
    states: Array,
    cities: Array,
    stations: Array
});

const truckTypeOptions = ref([...(props.truckTypes || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
const stationOptions = ref([...(props.stations || [])]);
watch(() => props.truckTypes, (v) => { truckTypeOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });

const form = useForm({
    truck_number: '',
    truck_type_id: '',
    status: 'Available',
    current_state_id: '',
    current_city_id: '',
    current_station_id: '',
    capacity: '',
    owner_name: '',
    owner_mobile: '',
    owner_email: '',
    owner_address: '',
    owner_city: '',
    owner_state: '',
    rc_no: '',
    rc_expiry_date: '',
    insurance_no: '',
    insurance_expiry_date: '',
    fitness_no: '',
    fitness_expiry_date: '',
    pollution_no: '',
    pollution_expiry_date: '',
    permit_no: '',
    permit_expiry_date: ''
});

const filteredCities = computed(() => {
    if (!form.current_state_id) return [];
    return cityOptions.value.filter(c => String(c.state_id) === String(form.current_state_id));
});

const filteredStations = computed(() => {
    if (!form.current_city_id) return [];
    return stationOptions.value.filter(s => String(s.city_id) === String(form.current_city_id));
});

watch(() => form.current_state_id, () => {
    form.current_city_id = '';
    form.current_station_id = '';
});

watch(() => form.current_city_id, () => {
    form.current_station_id = '';
});

const onTruckNumberInput = (event) => {
    form.truck_number = event.target.value.toUpperCase();
};

const submit = () => {
    form.truck_number = form.truck_number.toUpperCase();
    form.post('/trucks');
};
</script>

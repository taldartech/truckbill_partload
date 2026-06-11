<template>
    <AdminLayout title="Edit Truck">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Truck Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Core Info -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Core Information</h5>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Transporter (Optional)</label>
                                    <select class="form-control" v-model="form.transporter_id">
                                        <option value="">Self / Unknown</option>
                                        <option v-for="transporter in transporters" :key="transporter.id" :value="transporter.id">
                                            {{ transporter.name }}
                                        </option>
                                    </select>
                                </div>
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
                                    <select class="form-control" v-model="form.truck_type_id">
                                        <option value="">Select Type</option>
                                        <option v-for="type in truckTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
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
                                    <SearchableSelect v-model="form.current_state_id" :options="states" placeholder="Select State" />
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Current City</label>
                                    <select class="form-control" v-model="form.current_city_id">
                                        <option value="">Select City</option>
                                        <option v-for="city in filteredCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Current Station</label>
                                    <select class="form-control" v-model="form.current_station_id">
                                        <option value="">Select Station</option>
                                        <option v-for="station in filteredStations" :key="station.id" :value="station.id">
                                            {{ station.name }}
                                        </option>
                                    </select>
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
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Truck
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
    truck: Object,
    transporters: Array,
    truckTypes: Array,
    states: Array,
    cities: Array,
    stations: Array
});

const form = useForm({
    transporter_id: props.truck.transporter_id || '',
    truck_number: (props.truck.truck_number || '').toUpperCase(),
    truck_type_id: props.truck.truck_type_id || '',
    status: props.truck.status || 'Available',
    current_state_id: props.truck.current_state_id || '',
    current_city_id: props.truck.current_city_id || '',
    current_station_id: props.truck.current_station_id || '',
    capacity: props.truck.capacity || '',
    owner_name: props.truck.owner_name || '',
    owner_mobile: props.truck.owner_mobile || '',
    owner_email: props.truck.owner_email || '',
    owner_address: props.truck.owner_address || '',
    owner_city: props.truck.owner_city || '',
    owner_state: props.truck.owner_state || '',
    rc_no: props.truck.rc_no || '',
    rc_expiry_date: props.truck.rc_expiry_date || '',
    insurance_no: props.truck.insurance_no || '',
    insurance_expiry_date: props.truck.insurance_expiry_date || '',
    fitness_no: props.truck.fitness_no || '',
    fitness_expiry_date: props.truck.fitness_expiry_date || '',
    pollution_no: props.truck.pollution_no || '',
    pollution_expiry_date: props.truck.pollution_expiry_date || '',
    permit_no: props.truck.permit_no || '',
    permit_expiry_date: props.truck.permit_expiry_date || ''
});

const filteredCities = computed(() => {
    if (!form.current_state_id) return [];
    return props.cities.filter(c => String(c.state_id) === String(form.current_state_id));
});

const filteredStations = computed(() => {
    if (!form.current_city_id) return [];
    return props.stations.filter(s => String(s.city_id) === String(form.current_city_id));
});

watch(() => form.current_state_id, (newVal, oldVal) => {
    if(oldVal) {
        form.current_city_id = '';
        form.current_station_id = '';
    }
});

watch(() => form.current_city_id, (newVal, oldVal) => {
    if(oldVal) {
        form.current_station_id = '';
    }
});

const onTruckNumberInput = (event) => {
    form.truck_number = event.target.value.toUpperCase();
};

const submit = () => {
    form.truck_number = form.truck_number.toUpperCase();
    form.put(`/trucks/${props.truck.id}`);
};
</script>

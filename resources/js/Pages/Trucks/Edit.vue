<template>
    <AdminLayout title="Edit Truck">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Core Information -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Core Information</h2>
                                <p class="bilty-section__desc">Truck identity, type and status</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field" style="min-width: 12rem;">
                                    <span class="bilty-id__label">Truck Number<span class="bilty-field__req">*</span></span>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm text-uppercase"
                                        :class="{ 'is-invalid': form.errors.truck_number }"
                                        :value="form.truck_number"
                                        placeholder="e.g. MH04AB1234"
                                        required
                                        @input="onTruckNumberInput"
                                    >
                                    <div v-if="form.errors.truck_number" class="text-danger small mt-1">{{ form.errors.truck_number }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Truck Type</label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.truck_type_id"
                                    :options="truckTypeOptions"
                                    master="truck_type"
                                    placeholder="Select Type"
                                    @created="(record) => appendMasterOption(truckTypeOptions, record)"
                                />
                                <div v-if="form.errors.truck_type_id" class="text-danger small mt-1">{{ form.errors.truck_type_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Capacity (in Tons)</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.capacity" placeholder="e.g. 15">
                                <div v-if="form.errors.capacity" class="text-danger small mt-1">{{ form.errors.capacity }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Status</label>
                                <select class="form-select form-select-sm" v-model="form.status">
                                    <option value="Available">Available</option>
                                    <option value="Booked">Booked</option>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                                <div v-if="form.errors.status" class="text-danger small mt-1">{{ form.errors.status }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Current Location -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Current Location</h2>
                                <p class="bilty-section__desc">Where the truck is currently based</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Current State</label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.current_state_id"
                                    :options="stateOptions"
                                    master="state"
                                    placeholder="Select State"
                                    @created="(record) => appendMasterOption(stateOptions, record)"
                                />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Current City</label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.current_city_id"
                                    :options="filteredCities"
                                    master="city"
                                    :quick-add-context="{ state_id: form.current_state_id }"
                                    placeholder="Select City"
                                    :disabled="!form.current_state_id"
                                    @created="(record) => appendMasterOption(cityOptions, record)"
                                />
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Current Station</label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.current_station_id"
                                    :options="filteredStations"
                                    master="station"
                                    placeholder="Select Station"
                                    :disabled="!form.current_city_id"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. Owner -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">3</div>
                            <div>
                                <h2 class="bilty-section__title">Owner</h2>
                                <p class="bilty-section__desc">Select or add a truck owner</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Truck Owner</label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.truck_owner_id"
                                    :options="truckOwnerOptions"
                                    master="truck_owner"
                                    placeholder="Select Owner"
                                    @created="(record) => appendMasterOption(truckOwnerOptions, record)"
                                />
                                <div v-if="form.errors.truck_owner_id" class="text-danger small mt-1">{{ form.errors.truck_owner_id }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. Compliance & Documents -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">4</div>
                            <div>
                                <h2 class="bilty-section__title">Compliance &amp; Documents</h2>
                                <p class="bilty-section__desc">RC, insurance, fitness, pollution and permit</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">RC Number</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.rc_no">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">RC Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.rc_expiry_date">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Insurance No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.insurance_no">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Insurance Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.insurance_expiry_date">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Fitness No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.fitness_no">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Fitness Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.fitness_expiry_date">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Pollution No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.pollution_no">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Pollution Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.pollution_expiry_date">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Permit No.</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.permit_no">
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Permit Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.permit_expiry_date">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/trucks" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Update Truck
                    </button>
                </div>
            </form>
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
    truck: Object,
    truckTypes: Array,
    truckOwners: Array,
    states: Array,
    cities: Array,
    stations: Array
});

const truckTypeOptions = ref([...(props.truckTypes || [])]);
const truckOwnerOptions = ref([...(props.truckOwners || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
const stationOptions = ref([...(props.stations || [])]);
watch(() => props.truckTypes, (v) => { truckTypeOptions.value = [...(v || [])]; });
watch(() => props.truckOwners, (v) => { truckOwnerOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });

const form = useForm({
    truck_number: (props.truck.truck_number || '').toUpperCase(),
    truck_type_id: props.truck.truck_type_id || '',
    status: props.truck.status || 'Available',
    current_state_id: props.truck.current_state_id || '',
    current_city_id: props.truck.current_city_id || '',
    current_station_id: props.truck.current_station_id || '',
    capacity: props.truck.capacity || '',
    truck_owner_id: props.truck.truck_owner_id || '',
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
    return cityOptions.value.filter(c => String(c.state_id) === String(form.current_state_id));
});

const filteredStations = computed(() => {
    if (!form.current_city_id) return [];
    return stationOptions.value.filter(s => String(s.city_id) === String(form.current_city_id));
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

<template>
    <AdminLayout title="Edit Station">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Station Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="name">Name *</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter Name" required>
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="state">State *</label>
                                        <SearchableSelect
                                            v-model="form.state_id"
                                            :options="states"
                                            placeholder="Select State"
                                        />
                                        <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{ form.errors.state_id }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="city">City *</label>
                                        <SearchableSelect
                                            v-model="form.city_id"
                                            :options="filteredCities"
                                            placeholder="Select City"
                                            :disabled="!form.state_id"
                                        />
                                        <div v-if="form.errors.city_id" class="text-danger mt-1 small">{{ form.errors.city_id }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/stations" class="btn btn-back">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Station
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
    station: Object,
    cities: Array,
    states: Array
});

const form = useForm({
    name: props.station.name || '',
    city_id: props.station.city_id || '',
    state_id: props.station.state_id || ''
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
    form.put(`/stations/${props.station.id}`);
};
</script>
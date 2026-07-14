<template>
    <AdminLayout title="Add Agent">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Agent Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="name">Agent Name *</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="address">Address *</label>
                                <input type="text" class="form-control" id="address" v-model="form.address" required>
                                <div v-if="form.errors.address" class="text-danger mt-1 small">{{ form.errors.address }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="state">State *</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="states"
                                        placeholder="Select State"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
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

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="mobile">Mobile *</label>
                                    <input type="text" class="form-control" id="mobile" v-model="form.mobile" required maxlength="10">
                                    <div v-if="form.errors.mobile" class="text-danger mt-1 small">{{ form.errors.mobile }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" v-model="form.email">
                                    <div v-if="form.errors.email" class="text-danger mt-1 small">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="gst_no">GST Number</label>
                                <input type="text" class="form-control" id="gst_no" v-model="form.gst_no">
                                <div v-if="form.errors.gst_no" class="text-danger mt-1 small">{{ form.errors.gst_no }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/agents" class="btn btn-back">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Agent
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
    cities: Array,
    states: Array
});

const form = useForm({
    name: '',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    email: '',
    gst_no: ''
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

const submit = () => {
    form.post('/agents');
};
</script>

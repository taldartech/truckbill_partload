<template>
    <AdminLayout title="Add Party">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Party Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row">
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

                            <div class="form-group mb-3">
                                <label>Party Name *</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Address *</label>
                                <input type="text" class="form-control" v-model="form.address" required>
                                <div v-if="form.errors.address" class="text-danger mt-1 small">{{ form.errors.address }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="states"
                                        placeholder="Select State"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>City *</label>
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
                                    <label>Mobile *</label>
                                    <input type="text" class="form-control" v-model="form.mobile" required maxlength="10">
                                    <div v-if="form.errors.mobile" class="text-danger mt-1 small">{{ form.errors.mobile }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                    <div v-if="form.errors.email" class="text-danger mt-1 small">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>GST Number</label>
                                    <input type="text" class="form-control" v-model="form.gst_number">
                                    <div v-if="form.errors.gst_number" class="text-danger mt-1 small">{{ form.errors.gst_number }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Bilty Charge (Fixed)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.bilty_charge">
                                    <div v-if="form.errors.bilty_charge" class="text-danger mt-1 small">{{ form.errors.bilty_charge }}</div>
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
    groups: Array,
    cities: Array,
    states: Array,
});

const form = useForm({
    group_id: '',
    name: '',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    email: '',
    gst_number: '',
    bilty_charge: '',
});

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return props.cities.filter((city) => String(city.state_id) === String(form.state_id));
});

watch(() => form.state_id, () => {
    if (form.city_id && !filteredCities.value.some((city) => String(city.id) === String(form.city_id))) {
        form.city_id = '';
    }
});

const submit = () => {
    form.post('/parties');
};
</script>

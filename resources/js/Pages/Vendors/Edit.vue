<template>
    <AdminLayout title="Edit Vendor">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vendor Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
<div class="form-group mb-3">
                                <label for="name">Vendor Name *</label>
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
                                        :options="stateOptions"
                                        master="state"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="city">City *</label>
                                    <SearchableSelect
                                        v-model="form.city_id"
                                        :options="filteredCities"
                                        master="city"
                                        :quick-add-context="{ state_id: form.state_id }"
                                        placeholder="Select City"
                                        :disabled="!form.state_id"
                                        @created="(record) => appendMasterOption(cityOptions, record)"
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
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="gst_number">GST Number</label>
                                <input type="text" class="form-control" id="gst_number" v-model="form.gst_number">
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/vendors" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, watch, ref } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    vendor: Object,
    cities: Array,
    states: Array,
});

const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });


const form = useForm({
    name: props.vendor.name || '',
    address: props.vendor.address || '',
    state_id: props.vendor.state_id || '',
    city_id: props.vendor.city_id || '',
    mobile: props.vendor.mobile || '',
    email: props.vendor.email || '',
    gst_number: props.vendor.gst_number || '',
});

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return cityOptions.value.filter((city) => String(city.state_id) === String(form.state_id));
});

watch(() => form.state_id, () => {
    if (form.city_id && !filteredCities.value.some((city) => String(city.id) === String(form.city_id))) {
        form.city_id = '';
    }
});

const submit = () => {
    form.put(`/vendors/${props.vendor.id}`);
};
</script>

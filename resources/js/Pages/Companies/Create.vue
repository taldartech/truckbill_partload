<template>
    <AdminLayout title="Add Company">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Company Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Basic Information -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Company Name *</label>
                                    <input type="text" class="form-control" v-model="form.name" required>
                                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Short Name</label>
                                    <input type="text" class="form-control" v-model="form.short_name">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Company Type</label>
                                    <select class="form-control" v-model="form.company_type">
                                        <option value="">Select Type</option>
                                        <option value="Proprietorship">Proprietorship</option>
                                        <option value="Partnership">Partnership</option>
                                        <option value="Private Limited">Private Limited</option>
                                        <option value="Public Limited">Public Limited</option>
                                        <option value="LLP">LLP</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                    <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Phone Number</label>
                                    <input type="text" class="form-control" v-model="form.phone">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Mobile Number</label>
                                    <input type="text" class="form-control" v-model="form.mobile">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Contact Person</label>
                                    <input type="text" class="form-control" v-model="form.contact_person">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Contact Number</label>
                                    <input type="text" class="form-control" v-model="form.contact_number">
                                </div>
                            </div>

                            <!-- Location Information -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Location Details</h5>
                            <div class="row">
                                <div class="col-md-12 form-group mb-3">
                                    <label>Address</label>
                                    <textarea class="form-control" v-model="form.address" rows="2"></textarea>
                                </div>
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

                            <!-- Taxation & Bank -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Taxation & Bank Details</h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Number</label>
                                    <input type="text" class="form-control" v-model="form.gst_number">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Scheme</label>
                                    <select class="form-control" v-model="form.gst_scheme">
                                        <option value="">Select</option>
                                        <option value="Regular">Regular</option>
                                        <option value="Composition">Composition</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Type</label>
                                    <select class="form-control" v-model="form.gst_type">
                                        <option value="">Select</option>
                                        <option value="Forward">Forward</option>
                                        <option value="Reverse">Reverse</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>PAN Number</label>
                                    <input type="text" class="form-control" v-model="form.pan_no">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Bank Name</label>
                                    <input type="text" class="form-control" v-model="form.bank">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Account Number</label>
                                    <input type="text" class="form-control" v-model="form.account_number">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>IFSC Code</label>
                                    <input type="text" class="form-control" v-model="form.ifsc">
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">System Settings</h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Invoice Template</label>
                                    <select class="form-control" v-model="form.invoice_template">
                                        <option value="default">Default</option>
                                        <option value="format_1">Format 1</option>
                                        <option value="format_2">Format 2</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>LR Copy Template</label>
                                    <select class="form-control" v-model="form.lr_copy_template">
                                        <option value="default">Default</option>
                                        <option value="format_1">Format 1</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Slab (%)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.gst_slab">
                                </div>
                                <div class="col-md-3 form-group mb-3 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="status" v-model="form.status">
                                        <label class="form-check-label" for="status">Active Status</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer d-flex justify-content-between bg-light">
                            <Link href="/companies" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Company
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
    countries: Array,
    states: Array,
    cities: Array
});

const form = useForm({
    name: '',
    short_name: '',
    company_type: '',
    email: '',
    phone: '',
    mobile: '',
    contact_person: '',
    contact_number: '',
    address: '',
    country_id: '',
    state_id: '',
    city_id: '',
    pin_code: '',
    gst_number: '',
    gst_scheme: '',
    gst_type: '',
    pan_no: '',
    bank: '',
    account_number: '',
    ifsc: '',
    invoice_template: 'default',
    lr_copy_template: 'default',
    gst_slab: '',
    status: true
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
    form.post('/companies');
};
</script>

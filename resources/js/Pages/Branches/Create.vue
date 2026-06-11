<template>
    <AdminLayout title="Add Branch">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Branch Details</h3>
                    </div>
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="card-body">
                            <h5 class="text-primary border-bottom pb-2 mb-3">Branch Identity</h5>
                            <div v-if="$page.props.auth.is_admin" class="form-group mb-3">
                                <label for="company_id">Company *</label>
                                <select class="form-control" id="company_id" v-model="form.company_id" required>
                                    <option value="">Select Company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.company_id" class="text-danger mt-1 small">{{ form.errors.company_id }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="name">Branch Name *</label>
                                    <input type="text" class="form-control" id="name" v-model="form.name" required>
                                    <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="station_id">Station *</label>
                                    <SearchableSelect
                                        id="station_id"
                                        v-model="form.station_id"
                                        :options="stations"
                                        placeholder="Select Station"
                                    />
                                    <div v-if="form.errors.station_id" class="text-danger mt-1 small">{{ form.errors.station_id }}</div>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_head_office" v-model="form.is_head_office">
                                <label class="form-check-label" for="is_head_office">Head Office</label>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Location</h5>
                            <div class="form-group mb-3">
                                <label for="address">Address *</label>
                                <textarea class="form-control" id="address" v-model="form.address" rows="2" required></textarea>
                                <div v-if="form.errors.address" class="text-danger small">{{ form.errors.address }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Country *</label>
                                    <select class="form-control" v-model="form.country_id" required>
                                        <option value="">Select Country</option>
                                        <option v-for="country in countries" :key="country.id" :value="country.id">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.country_id" class="text-danger small">{{ form.errors.country_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        id="state_id"
                                        v-model="form.state_id"
                                        :options="states"
                                        placeholder="Select State"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>City *</label>
                                    <select class="form-control" v-model="form.city_id" required>
                                        <option value="">Select City</option>
                                        <option v-for="city in filteredCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.city_id" class="text-danger small">{{ form.errors.city_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>PIN Code *</label>
                                    <input type="text" class="form-control" v-model="form.pin_code" required>
                                    <div v-if="form.errors.pin_code" class="text-danger small">{{ form.errors.pin_code }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Contact</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Email Address *</label>
                                    <input type="email" class="form-control" v-model="form.email" required>
                                    <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Person *</label>
                                    <input type="text" class="form-control" v-model="form.contact_person" required>
                                    <div v-if="form.errors.contact_person" class="text-danger small">{{ form.errors.contact_person }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Number *</label>
                                    <input type="text" class="form-control" v-model="form.contact_number" required>
                                    <div v-if="form.errors.contact_number" class="text-danger small">{{ form.errors.contact_number }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">GST</h5>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="has_gst" v-model="form.has_gst">
                                <label class="form-check-label" for="has_gst">Do you have GST?</label>
                            </div>
                            <div v-if="form.has_gst" class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Number *</label>
                                    <input type="text" class="form-control" v-model="form.gst_number" required>
                                    <div v-if="form.errors.gst_number" class="text-danger small">{{ form.errors.gst_number }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Type *</label>
                                    <select class="form-control" v-model="form.gst_type" required>
                                        <option value="">Select</option>
                                        <option value="Forward">Forward</option>
                                        <option value="Reverse">Reverse</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Scheme *</label>
                                    <select class="form-control" v-model="form.gst_scheme" required>
                                        <option value="">Select</option>
                                        <option value="Regular">Regular</option>
                                        <option value="Composition">Composition</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Slab (%) *</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.gst_slab" required>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Bank Details</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Bank Name</label>
                                    <input type="text" class="form-control" v-model="form.bank_name">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Account Number</label>
                                    <input type="text" class="form-control" v-model="form.account_number">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>IFSC Code</label>
                                    <input type="text" class="form-control" v-model="form.ifsc_code">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Document Initials <small class="text-muted fw-normal">(optional — leave blank for no prefix)</small></h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Bilty Initial</label>
                                    <input type="text" class="form-control" v-model="form.bilty_initial" placeholder="e.g. BLT-">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.challan_initial" placeholder="e.g. CHL-">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Main Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.main_challan_initial" placeholder="e.g. MCH-">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Invoice Initial</label>
                                    <input type="text" class="form-control" v-model="form.invoice_initial" placeholder="e.g. INV-">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Inward Register Initial</label>
                                    <input type="text" class="form-control" v-model="form.inward_register_initial" placeholder="e.g. IR-">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Logo</h5>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="use_company_logo" v-model="form.use_company_logo">
                                <label class="form-check-label" for="use_company_logo">Same as company logo</label>
                            </div>
                            <div v-if="!form.use_company_logo" class="form-group mb-3">
                                <label>Branch Logo</label>
                                <input type="file" class="form-control" @change="onLogoChange" accept="image/*">
                                <div v-if="form.errors.branch_logo" class="text-danger small">{{ form.errors.branch_logo }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/branches" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Branch
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
    companies: Array,
    stations: Array,
    countries: Array,
    states: Array,
    cities: Array,
    defaultCountryId: [Number, String, null],
});

const form = useForm({
    company_id: '',
    station_id: '',
    name: '',
    address: '',
    country_id: props.defaultCountryId || '',
    state_id: '',
    city_id: '',
    pin_code: '',
    is_head_office: false,
    has_gst: false,
    gst_number: '',
    gst_type: '',
    gst_scheme: '',
    gst_slab: '',
    bank_name: '',
    account_number: '',
    ifsc_code: '',
    contact_person: '',
    contact_number: '',
    email: '',
    bilty_initial: '',
    challan_initial: '',
    main_challan_initial: '',
    invoice_initial: '',
    inward_register_initial: '',
    use_company_logo: true,
    branch_logo: null,
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

const onLogoChange = (event) => {
    form.branch_logo = event.target.files[0];
};

const submit = () => {
    form.post('/branches', {
        forceFormData: true,
    });
};
</script>

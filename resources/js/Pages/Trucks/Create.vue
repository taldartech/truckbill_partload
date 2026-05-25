<template>
    <AdminLayout title="Add Truck">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Truck Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Core Info -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Core Information</h5>
                            <div class="row">
                                <div v-if="$page.props.auth.is_admin" class="col-md-6 form-group mb-3">
                                    <label>Company *</label>
                                    <select class="form-control" v-model="form.company_id" >
                                        <option value="">Select Company</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.company_id" class="text-danger small">{{ form.errors.company_id }}</div>
                                </div>
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
                                    <input type="text" class="form-control text-uppercase" v-model="form.truck_number" placeholder="e.g. MH 04 AB 1234" required>
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
                                <div class="col-md-4 form-group mb-3">
                                    <label>Capacity (in Tons)</label>
                                    <input type="text" class="form-control" v-model="form.capacity" placeholder="e.g. 15">
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
                            <Link href="/trucks" class="btn btn-default">Cancel</Link>
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
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    companies: Array,
    transporters: Array,
    truckTypes: Array
});

const form = useForm({
    company_id: '',
    transporter_id: '',
    truck_number: '',
    truck_type_id: '',
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

const submit = () => {
    form.post('/trucks');
};
</script>
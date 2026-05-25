<template>
    <AdminLayout title="Add Challan">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Freight Challan Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <!-- Core Info -->
                            <h5 class="text-primary border-bottom pb-2 mb-3">Core Details</h5>
                            <div class="row">
                                <div v-if="$page.props.auth.is_admin" class="col-md-3 form-group mb-3">
                                    <label>Company *</label>
                                    <SearchableSelect
    v-model="form.company_id"
    :options="companies"
    placeholder="Select Company"
/>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Challan Number</label>
                                    <input type="text" class="form-control" v-model="form.challan_number">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Date</label>
                                    <input type="date" class="form-control" v-model="form.date">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Status</label>
                                    <select class="form-control" v-model="form.status">
                                        <option value="Draft">Draft</option>
                                        <option value="Dispatched">Dispatched</option>
                                        <option value="Reached">Reached</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>From Station *</label>
                                    <SearchableSelect
    v-model="form.from_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>To Station *</label>
                                    <SearchableSelect
    v-model="form.to_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Truck *</label>
                                    <SearchableSelect
    v-model="form.truck_id"
    :options="trucks"
    labelKey="truck_number"
    placeholder="Select Truck"
/>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Driver *</label>
                                    <SearchableSelect
    v-model="form.driver_id"
    :options="drivers"
    placeholder="Select Driver"
/>
                                </div>
                            </div>

                            <!-- Charges -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Charges & Financials</h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Truck Freight</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.truck_freight" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Labour Charge</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.labour_charge" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Kat</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.kat" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Home Delivery Charge</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.home_delivery_charge" @input="calculateTotal">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Crossing Freight</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.crossing_freight" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Cartage / Dalali (PF)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.cartage_dalali_pf" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Other Charge</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.other_charge" @input="calculateTotal">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.gst" @input="calculateTotal">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Advance / Paid Amount</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.paid_amount">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>To Pay Amount</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.to_pay_amount">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Cash Received</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.cash_received">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Cash Payment</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.cash_payment">
                                </div>
                            </div>

                            <!-- Cargo details -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Additional Info</h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Total Weight (KG)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.total_weight">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Total Packages</label>
                                    <input type="number" class="form-control" v-model="form.total_packages">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Pay By</label>
                                    <input type="text" class="form-control" v-model="form.pay_by">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Delivery At</label>
                                    <input type="text" class="form-control" v-model="form.delivery_at">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-2 form-group mb-3 d-flex align-items-center">
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" v-model="form.add_to_main_challan" id="add_main">
                                        <label class="form-check-label" for="add_main">Add to Main Challan?</label>
                                    </div>
                                </div>
                                <div class="col-md-7 form-group mb-3">
                                    <label>Remark</label>
                                    <input type="text" class="form-control" v-model="form.remark">
                                </div>
                                <div class="col-md-3 form-group mb-3 border-start">
                                    <label class="text-danger fw-bold">Total Amount</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg bg-light text-danger fw-bold" v-model="form.total_amount" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/challans" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Challan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    companies: Array,
    stations: Array,
    drivers: Array,
    trucks: Array,
    next_challan_number: String
});

const form = useForm({
    company_id: '',
    from_station_id: '',
    to_station_id: '',
    driver_id: '',
    truck_id: '',
    challan_number: props.next_challan_number || '',
    date: new Date().toISOString().substr(0, 10),
    truck_freight: 0,
    labour_charge: 0,
    kat: 0,
    home_delivery_charge: 0,
    crossing_freight: 0,
    cartage_dalali_pf: 0,
    other_charge: 0,
    gst: 0,
    paid_amount: 0,
    to_pay_amount: 0,
    cash_received: 0,
    cash_payment: 0,
    total_amount: 0,
    total_weight: 0,
    total_packages: 0,
    pay_by: '',
    delivery_at: '',
    add_to_main_challan: false,
    remark: '',
    status: 'Draft'
});

const calculateTotal = () => {
    const sum = 
        parseFloat(form.truck_freight || 0) + 
        parseFloat(form.labour_charge || 0) + 
        parseFloat(form.kat || 0) + 
        parseFloat(form.home_delivery_charge || 0) + 
        parseFloat(form.crossing_freight || 0) + 
        parseFloat(form.cartage_dalali_pf || 0) + 
        parseFloat(form.other_charge || 0) + 
        parseFloat(form.gst || 0);
        
    form.total_amount = sum.toFixed(2);
};

const submit = () => {
    form.post('/challans');
};
</script>
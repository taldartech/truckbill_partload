<template>
    <AdminLayout title="Edit Inward Register">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Edit Inward Register</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <h5 class="border-bottom pb-2 mb-3 text-warning">Register Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Reg. No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.inward_register_no">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">From Station <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.from_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">To Station <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.to_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mt-4 mb-3 text-warning">Transport Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Challan <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    @change="autoFillTransport"
    v-model="form.challan_id"
    :options="challans"
    labelKey="challan_no"
    placeholder="Select Challan"
/>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.challan_date" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Truck <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.truck_id"
    :options="trucks"
    labelKey="truck_no"
    placeholder="Select Truck"
/>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Driver <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.driver_id"
    :options="drivers"
    placeholder="Select Driver"
/>
                                </div>
                            </div>
                            
                            <h5 class="border-bottom pb-2 mt-4 mb-3 text-warning">Goods Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Goods Type <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.good_id"
    :options="goods"
    placeholder="Select Goods"
/>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Goods Ready No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.goods_ready_no">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Goods Ready Date</label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.goods_ready_date">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/inward-registers" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">Update</button>
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
    register: Object,
    stations: Array,
    challans: Array,
    drivers: Array,
    trucks: Array,
    goods: Array
});

const form = useForm({
    inward_register_no: props.register.inward_register_no || '',
    date: props.register.date || new Date().toISOString().substr(0, 10),
    from_station_id: props.register.from_station_id || '',
    to_station_id: props.register.to_station_id || '',
    challan_id: props.register.challan_id || '',
    challan_date: props.register.challan_date || new Date().toISOString().substr(0, 10),
    driver_id: props.register.driver_id || '',
    truck_id: props.register.truck_id || '',
    goods_ready_no: props.register.goods_ready_no || '',
    goods_ready_date: props.register.goods_ready_date || '',
    good_id: props.register.good_id || ''
});

const autoFillTransport = () => {
    const selected = props.challans.find(c => c.id === form.challan_id);
    if(selected) {
        form.truck_id = selected.truck_id || form.truck_id;
        form.driver_id = selected.driver_id || form.driver_id;
        form.from_station_id = selected.from_station_id || form.from_station_id;
        form.to_station_id = selected.to_station_id || form.to_station_id;
        if(selected.challan_date) {
            form.challan_date = selected.challan_date;
        }
    }
};

const submit = () => {
    form.put(`/inward-registers/${props.register.id}`);
};
</script>

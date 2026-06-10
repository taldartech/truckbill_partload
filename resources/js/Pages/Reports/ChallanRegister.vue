<template>
    <AdminLayout title="Challan Register">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter me-2"></i>Filter Challan Register</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="filterReports" class="row">
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.start_date">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.end_date">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-edit btn-sm w-100">Filter Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Challan No.</th>
                                    <th>Truck No</th>
                                    <th>Driver</th>
                                    <th>From Station</th>
                                    <th>To Station</th>
                                    <th class="text-end">Truck Freight</th>
                                    <th class="text-end">Advance</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="challan in challans" :key="challan.id">
                                    <td>{{ challan.date }}</td>
                                    <td>
                                        <a :href="`/challans/${challan.id}/print`" target="_blank" class="fw-bold">{{ challan.challan_no }}</a>
                                    </td>
                                    <td>{{ challan.truck?.truck_no }}</td>
                                    <td>{{ challan.driver?.name }}</td>
                                    <td>{{ challan.from_station?.name }}</td>
                                    <td>{{ challan.to_station?.name }}</td>
                                    <td class="text-end">₹ {{ challan.truck_freight }}</td>
                                    <td class="text-end">₹ {{ challan.advance }}</td>
                                    <td class="text-end fw-bold">₹ {{ challan.balance }}</td>
                                </tr>
                                <tr v-if="challans.length === 0">
                                    <td colspan="9" class="text-center py-4 text-muted">No challans found for selected filters.</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="challans.length > 0">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Totals:</td>
                                    <td class="text-end fw-bold">₹ {{ totalFreight.toFixed(2) }}</td>
                                    <td class="text-end fw-bold">₹ {{ totalAdvance.toFixed(2) }}</td>
                                    <td class="text-end fw-bold text-danger">₹ {{ totalBalance.toFixed(2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    challans: Array,
    filters: Object
});

const form = useForm({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || ''
});

const filterReports = () => {
    form.get('/reports/challan-register', {
        preserveState: true
    });
};

const totalFreight = computed(() => {
    return props.challans.reduce((sum, item) => sum + parseFloat(item.truck_freight || 0), 0);
});

const totalAdvance = computed(() => {
    return props.challans.reduce((sum, item) => sum + parseFloat(item.advance || 0), 0);
});

const totalBalance = computed(() => {
    return props.challans.reduce((sum, item) => sum + parseFloat(item.balance || 0), 0);
});
</script>

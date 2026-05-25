<template>
    <AdminLayout title="Inward Registers">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="m-0 text-dark">Inward Registers</h4>
                <Link href="/inward-registers/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Inward Register
                </Link>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped m-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th># ID</th>
                                <th>Reg No.</th>
                                <th>Date</th>
                                <th>From -> To</th>
                                <th>Challan No.</th>
                                <th>Truck No.</th>
                                <th>Goods</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="register in registers.data" :key="register.id">
                                <td>{{ register.id }}</td>
                                <td>{{ register.inward_register_no || '--' }}</td>
                                <td>{{ register.date }}</td>
                                <td>{{ register.from_station?.name }} <i class="fas fa-arrow-right mx-1 text-muted text-xs"></i> {{ register.to_station?.name }}</td>
                                <td>{{ register.challan?.challan_no }} ({{ register.challan_date }})</td>
                                <td>{{ register.truck?.truck_no }}</td>
                                <td>{{ register.good?.name }}</td>
                                <td class="text-end">
                                    <Link :href="`/inward-registers/${register.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteRegister(register.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="registers.data.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">No inward registers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    registers: Object
});

const deleteForm = useForm({});

const deleteRegister = (id) => {
    if (confirm('Are you sure you want to delete this register?')) {
        deleteForm.delete(`/inward-registers/${id}`);
    }
};
</script>

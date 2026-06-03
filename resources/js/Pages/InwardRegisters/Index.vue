<template>
    <AdminLayout title="Inward Register">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Inward Registers</h3>
                        <Link v-if="can('create inward_registers')" href="/inward-registers/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Inward Register
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reg No.</th>
                                    <th>Date</th>
                                    <th>From → To</th>
                                    <th>Challan No.</th>
                                    <th>Truck No.</th>
                                    <th>Goods</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="register in registers.data" :key="register.id">
                                    <td>{{ register.id }}</td>
                                    <td>{{ register.inward_register_no || '—' }}</td>
                                    <td>{{ register.date }}</td>
                                    <td>
                                        {{ register.from_station?.name }}
                                        <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                                        {{ register.to_station?.name }}
                                    </td>
                                    <td>{{ register.challan?.challan_number }} ({{ register.challan_date }})</td>
                                    <td>{{ register.truck?.truck_number }}</td>
                                    <td>{{ register.good?.name }}</td>
                                    <td class="text-end">
                                        <Link :href="`/inward-registers/${register.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button class="btn btn-sm btn-danger" @click="deleteRegister(register.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="registers.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No inward registers found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    registers: Object,
});

const deleteForm = useForm({});

const deleteRegister = (id) => {
    if (confirm('Are you sure you want to delete this register?')) {
        deleteForm.delete(`/inward-registers/${id}`);
    }
};
</script>

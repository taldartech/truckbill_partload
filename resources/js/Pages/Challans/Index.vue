<template>
    <AdminLayout title="Challans">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Challans</h3>
                        <Link v-if="can('create challans')" href="/challans/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Challan
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Vehicle & Driver</th>
                                    <th>Freight</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="challan in challans.data" :key="challan.id">
                                    <td>{{ challan.id }}</td>
                                    <td>{{ challan.challan_number || 'N/A' }}</td>
                                    <td>{{ challan.date || 'N/A' }}</td>
                                    <td>
                                        <span class="text-bold">{{ challan.from_station?.name }}</span> 
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ challan.to_station?.name }}</span>
                                    </td>
                                    <td>
                                        <div class="text-bold"><i class="fas fa-truck me-1 text-muted small"></i> {{ challan.truck?.truck_number }}</div>
                                        <div class="small text-muted"><i class="fas fa-user me-1 text-muted small"></i> {{ challan.driver?.name }}</div>
                                    </td>
                                    <td>{{ challan.total_amount || '0.00' }}</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-warning': challan.status === 'Created',
                                            'bg-info': challan.status === 'In Progress',
                                            'bg-success': challan.status === 'Completed',
                                            'bg-danger': challan.status === 'Cancelled'
                                        }">{{ challan.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a :href="`/challans/${challan.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Challan">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/challans/${challan.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(challan.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="challans.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No records found.</td>
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
import {  Link, useForm , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    challans: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/challans/${id}`);
    }
};
</script>
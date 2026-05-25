<template>
    <AdminLayout title="Transporters">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Transporters</h3>
                        <Link v-if="can('create transporters')" href="/transporters/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Transporter
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Short Name</th>
                                    <th>Gst Number</th>
                                    <th>Pan Number</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="transporter in transporters.data" :key="transporter.id">
                                    <td>{{ transporter.id }}</td>
                                    <td>{{ transporter.name || 'N/A' }}</td>
                                    <td>{{ transporter.short_name || 'N/A' }}</td>
                                    <td>{{ transporter.gst_number || 'N/A' }}</td>
                                    <td>{{ transporter.pan_number || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/transporters/${transporter.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(transporter.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="transporters.data.length === 0">
                                    <td colspan="10" class="text-center py-4">No records found.</td>
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
    transporters: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/transporters/${id}`);
    }
};
</script>
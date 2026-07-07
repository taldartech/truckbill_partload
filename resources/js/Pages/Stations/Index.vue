<template>
    <AdminLayout title="Stations">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Stations</h3>
                        <Link v-if="can('create stations')" href="/stations/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Station
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="station in stations.data" :key="station.id">
                                    <td>{{ station.id }}</td>
                                    <td>{{ station.name || 'N/A' }}</td>
                                    <td>{{ station.city?.name || 'N/A' }}</td>
                                    <td>{{ station.state?.name || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/stations/${station.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(station.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="stations.data.length === 0">
                                    <td colspan="10" class="text-center py-4">No records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="stations" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import {  Link, useForm , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    stations: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/stations/${id}`);
    }
};
</script>

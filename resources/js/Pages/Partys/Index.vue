<template>
    <AdminLayout title="Partys">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Partys</h3>
                        <Link href="/parties/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Party
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Gst Number</th>
                                    <th>Mobile</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="party in parties.data" :key="party.id">
                                    <td>{{ party.id }}</td>
                                    <td>{{ party.name || 'N/A' }}</td>
                                    <td>{{ party.type || 'N/A' }}</td>
                                    <td>{{ party.gst_number || 'N/A' }}</td>
                                    <td>{{ party.mobile || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/parties/${party.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(party.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="parties.data.length === 0">
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
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    parties: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/parties/${id}`);
    }
};
</script>
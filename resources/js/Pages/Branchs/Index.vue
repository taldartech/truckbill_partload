<template>
    <AdminLayout title="Branchs">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Branchs</h3>
                        <Link href="/branchs/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Branch
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>Is Head Office</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="branch in branchs.data" :key="branch.id">
                                    <td>{{ branch.id }}</td>
                                    <td>{{ branch.name || 'N/A' }}</td>
                                    <td>{{ branch.address || 'N/A' }}</td>
                                    <td>{{ branch.city || 'N/A' }}</td>
                                    <td>{{ branch.is_head_office || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/branchs/${branch.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(branch.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="branchs.data.length === 0">
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
    branchs: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/branchs/${id}`);
    }
};
</script>
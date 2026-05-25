<template>
    <AdminLayout title="Biltys">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Biltys</h3>
                        <Link href="/bilties/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Bilty
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bilty Number</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bilty in bilties.data" :key="bilty.id">
                                    <td>{{ bilty.id }}</td>
                                    <td>{{ bilty.bilty_number || 'N/A' }}</td>
                                    <td>{{ bilty.date || 'N/A' }}</td>
                                    <td>{{ bilty.amount || 'N/A' }}</td>
                                    <td>{{ bilty.status || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/bilties/${bilty.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(bilty.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bilties.data.length === 0">
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
    bilties: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/bilties/${id}`);
    }
};
</script>
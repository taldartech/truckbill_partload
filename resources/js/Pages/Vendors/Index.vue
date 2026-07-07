<template>
    <AdminLayout title="Vendors">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Vendors</h3>
                        <Link v-if="can('create vendors')" href="/vendors/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Vendor
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Mobile</th>
                                    <th>GST Number</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="vendor in vendors.data" :key="vendor.id">
                                    <td>{{ vendor.id }}</td>
                                    <td>{{ vendor.name }}</td>
                                    <td>{{ vendor.company ? vendor.company.name : 'N/A' }}</td>
                                    <td>{{ vendor.mobile || 'N/A' }}</td>
                                    <td>{{ vendor.gst_number || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link v-if="can('edit vendors')" :href="`/vendors/${vendor.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button v-if="can('delete vendors')" @click="deleteItem(vendor.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="vendors.data.length === 0">
                                    <td colspan="6" class="text-center py-4">No vendors found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="vendors" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

defineProps({
    vendors: Object,
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this vendor?')) {
        form.delete(`/vendors/${id}`);
    }
};
</script>

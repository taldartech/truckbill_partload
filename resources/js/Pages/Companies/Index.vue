<template>
    <AdminLayout title="Companies">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Companies</h3>
                        <Link v-if="can('create companies')" href="/companies/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Company
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>GST Number</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="company in companies.data" :key="company.id">
                                    <td>{{ company.id }}</td>
                                    <td>{{ company.name }}</td>
                                    <td>{{ company.email || 'N/A' }}</td>
                                    <td>{{ company.phone || 'N/A' }}</td>
                                    <td>{{ company.gst_number || 'N/A' }}</td>
                                    <td>
                                        <span v-if="company.status" class="badge bg-success">Active</span>
                                        <span v-else class="badge bg-danger">Inactive</span>
                                    </td>
                                    <td class="text-end">
                                        <Link :href="`/companies/${company.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteCompany(company.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="companies.data.length === 0">
                                    <td colspan="7" class="text-center py-4">No companies found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination can be added here if needed using companies.links -->
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
    companies: Object
});

const form = useForm({});

const deleteCompany = (id) => {
    if (confirm('Are you sure you want to delete this company?')) {
        form.delete(`/companies/${id}`);
    }
};
</script>

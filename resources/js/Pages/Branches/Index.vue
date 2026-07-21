<template>
    <AdminLayout title="Branches">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Branches</h3>
                        <Link v-if="can('create branches')" href="/branches/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Branch
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Station</th>
                                    <th>City</th>
                                    <th>Type</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="branch in branches.data" :key="branch.id">
                                    <td>{{ branch.id }}</td>
                                    <td>{{ branch.name || 'N/A' }}</td>
                                    <td>{{ branch.station?.name || 'N/A' }}</td>
                                    <td>{{ branch.city?.name || 'N/A' }}</td>
                                    <td>
                                        <span v-if="branch.is_head_office" class="badge bg-primary">Head Office</span>
                                        <span v-else class="badge bg-secondary">Branch</span>
                                    </td>
                                    <td class="text-end">
                                        <Link
                                            v-if="can('edit branches')"
                                            :href="`/branches/${branch.id}/settings`"
                                            class="btn btn-outline-secondary btn-sm me-2"
                                            title="Branch Settings"
                                        >
                                            <i class="fas fa-cog"></i>
                                        </Link>
                                        <Link :href="`/branches/${branch.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteBranch(branch.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="branches.data.length === 0">
                                    <td colspan="6" class="text-center py-4">No branches found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="branches" />
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
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    branches: Object,
});

const form = useForm({});

const deleteBranch = (id) => {
    if (confirm('Are you sure you want to delete this branch?')) {
        form.delete(`/branches/${id}`);
    }
};
</script>

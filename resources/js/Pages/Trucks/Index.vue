<template>
    <AdminLayout title="Truck Management">
        <div class="row">
            <div class="col-12">
                <div class="card rounded-3 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">Trucks List</h5>
                        <Link v-if="can('create trucks')" href="/trucks/create" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-plus me-1"></i> Add Truck
                        </Link>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
                            <div class="d-flex align-items-center">
                                <span class="small text-muted me-2">Show</span>
                                <select class="form-select form-select-sm" style="width: 70px;">
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                </select>
                                <span class="small text-muted ms-2">entries</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="small text-muted me-2">Search:</span>
                                <input type="text" class="form-control form-control-sm" style="width: 200px;">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Sr. No</th>
                                        <th>Truck Number</th>
                                        <th>Truck Type</th>
                                        <th>Registration Date</th>
                                        <th>Truck Owner</th>
                                        <th>Driver</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(truck, index) in trucks.data" :key="truck.id">
                                        <td class="ps-4 text-muted">{{ index + 1 }}</td>
                                        <td>
                                            <Link :href="`/trucks/${truck.id}`" class="text-decoration-none fw-bold" style="color: #27AE60;">
                                                {{ truck.truck_number }}
                                            </Link>
                                        </td>
                                        <td>{{ truck.truck_type?.name || 'N/A' }}</td>
                                        <td class="text-muted">{{ truck.rc_expiry_date || '-' }}</td>
                                        <td>{{ truck.transporter?.name || '-' }}</td>
                                        <td>{{ truck.driver?.name || '-' }}</td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <Link :href="`/trucks/${truck.id}/edit`" class="btn btn-success btn-sm me-2 rounded-2">
                                                    <i class="fas fa-user-edit"></i>
                                                </Link>
                                                <button @click="deleteTruck(truck.id)" class="btn btn-danger btn-sm rounded-2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="trucks.data.length === 0">
                                        <td colspan="7" class="text-center py-5 text-muted">No trucks found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Showing 1 to {{ trucks.data.length }} of {{ trucks.total }} entries
                            </div>
                            <nav v-if="trucks.links.length > 3">
                                <ul class="pagination pagination-sm mb-0">
                                    <li v-for="link in trucks.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                        <Link class="page-link" :href="link.url || '#'" v-html="link.label"></Link>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import {  Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    trucks: Object
});

const deleteTruck = (id) => {
    if (confirm('Are you sure you want to delete this truck?')) {
        router.delete(`/trucks/${id}`);
    }
};
</script>

<style scoped>
.table thead th {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.btn-success {
    background-color: #27AE60;
    border-color: #27AE60;
}
.page-item.active .page-link {
    background-color: #27AE60;
    border-color: #27AE60;
}
</style>
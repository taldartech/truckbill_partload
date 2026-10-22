<template>
    <AdminLayout title="Drivers">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Drivers</h3>
                        <Link v-if="can('create drivers')" href="/drivers/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Driver
                        </Link>
                    </div>
                    <div class="card-body p-0">
                    <div class="d-sm-none">
                            <div v-for="(driver, index) in drivers.data" :key="driver.id"
                                class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="fw-bold text-truncate d-flex align-items-center">
                                        <span class="  me-2 flex-shrink-0">{{ (drivers.total ??
                                            drivers.data.length) - ((drivers.from ?? 1) - 1) - index }}</span>
                                        <span class="text-truncate">{{ driver.name || 'N/A' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 ms-2">
                                        <Link :href="`/drivers/${driver.id}/edit`" class="btn btn-sm btn-edit py-0 px-2"
                                            title="Edit Driver">
                                            <i class="fas fa-edit small"></i>
                                        </Link>
                                        <button @click="deleteItem(driver.id)" class="btn btn-sm btn-danger py-0 px-2"
                                            title="Delete Driver">
                                            <i class="fas fa-trash small"></i>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    class="small text-muted mt-1 d-flex align-items-center justify-content-between text-truncate">
                                    <span v-if="driver.mobile">
                                        <i class="fas fa-phone me-1 small"></i>{{ driver.mobile }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                    <span class="text-truncate ms-2" v-if="driver.license_number">
                                        Lic: {{ driver.license_number }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="drivers.data.length === 0" class="text-center text-muted py-4 small">
                                No records found.
                            </div>
                        </div>

                        <div class="table-responsive d-none d-sm-block">
                            <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sr No.</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>License Number</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(driver, index) in drivers.data" :key="driver.id">
                                    <td>{{ (drivers.from ?? 1) + index }}</td>
                                    <td>{{ driver.name || 'N/A' }}</td>
                                    <td>{{ driver.mobile || 'N/A' }}</td>
                                    <td>{{ driver.license_number || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/drivers/${driver.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(driver.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="drivers.data.length === 0">
                                    <td colspan="10" class="text-center py-4">No records found.</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <PaginationLinks :pagination="drivers" />
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
    drivers: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        form.delete(`/drivers/${id}`);
    }
};
</script>
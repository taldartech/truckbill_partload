<template>
    <AdminLayout title="Loading Report">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Loading Reports</h3>
                        <Link v-if="can('create loading_reports')" href="/loading-reports/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Loading Report
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>From Station</th>
                                    <th>To Station</th>
                                    <th>Truck No.</th>
                                    <th>Driver</th>
                                    <th>Total Weight (Kg)</th>
                                    <th>Packages</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="report in reports.data" :key="report.id">
                                    <td>{{ report.id }}</td>
                                    <td>{{ report.date }}</td>
                                    <td>{{ report.from_station?.name }}</td>
                                    <td>{{ report.to_station?.name }}</td>
                                    <td>{{ report.truck?.truck_number }}</td>
                                    <td>{{ report.driver_name || 'N/A' }}</td>
                                    <td>{{ report.gross_weight }}</td>
                                    <td>{{ report.no_of_packages }}</td>
                                    <td class="text-end">
                                        <a :href="`/loading-reports/${report.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Loading Report">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/loading-reports/${report.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button class="btn btn-sm btn-danger" @click="deleteReport(report.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="reports.data.length === 0">
                                    <td colspan="9" class="text-center py-4">No loading reports found.</td>
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
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    reports: Object,
});

const deleteForm = useForm({});

const deleteReport = (id) => {
    if (confirm('Are you sure you want to delete this loading report?')) {
        deleteForm.delete(`/loading-reports/${id}`);
    }
};
</script>

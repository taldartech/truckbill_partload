<template>
    <AdminLayout title="Loading Reports">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="m-0 text-dark">Loading Reports</h4>
                <Link href="/loading-reports/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Loading Report
                </Link>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped m-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th># ID</th>
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
                                <td>{{ report.truck?.truck_no }}</td>
                                <td>{{ report.driver?.name }}</td>
                                <td>{{ report.gross_weight }}</td>
                                <td>{{ report.no_of_packages }}</td>
                                <td class="text-end">
                                    <Link :href="`/loading-reports/${report.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteReport(report.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="reports.data.length === 0">
                                <td colspan="9" class="text-center py-4 text-muted">No loading reports found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination could go here -->
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    reports: Object
});

const deleteForm = useForm({});

const deleteReport = (id) => {
    if (confirm('Are you sure you want to delete this loading report?')) {
        deleteForm.delete(`/loading-reports/${id}`);
    }
};
</script>

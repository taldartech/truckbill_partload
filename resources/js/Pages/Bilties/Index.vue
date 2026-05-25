<template>
    <AdminLayout title="Bilties">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Bilties</h3>
                        <Link v-if="can('create bilties')" href="/bilties/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Bilty
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>From - To</th>
                                    <th>Consignor - Consignee</th>
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
                                    <td>
                                        <span class="text-bold">{{ bilty.from_station?.name }}</span> 
                                        <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                        <span class="text-bold">{{ bilty.to_station?.name }}</span>
                                    </td>
                                    <td>
                                        <div>S: {{ bilty.consignor?.name }}</div>
                                        <div class="small text-muted text-italic">R: {{ bilty.consignee?.name }}</div>
                                    </td>
                                    <td>{{ bilty.amount || '0.00' }}</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-warning': bilty.status === 'Pending',
                                            'bg-info': bilty.status === 'In Transit',
                                            'bg-success': bilty.status === 'Delivered',
                                            'bg-danger': bilty.status === 'Cancelled'
                                        }">{{ bilty.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a :href="`/bilties/${bilty.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Bilty">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/bilties/${bilty.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(bilty.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bilties.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No records found.</td>
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
import {  Link, useForm , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

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

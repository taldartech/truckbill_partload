<template>
    <AdminLayout title="Consignees / Customers">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Parties (Consignees/Customers)</h3>
                        <Link v-if="can('create parties')" href="/parties/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Party
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>GST Number</th>
                                    <th>Mobile</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="party in parties.data" :key="party.id">
                                    <td>{{ party.id }}</td>
                                    <td>{{ party.company ? party.company.name : 'N/A' }}</td>
                                    <td>{{ party.name }}</td>
                                    <td>
                                        <span class="badge" 
                                            :class="{
                                                'bg-info': party.type === 'Consignor',
                                                'bg-primary': party.type === 'Consignee',
                                                'bg-success': party.type === 'Both' || !party.type
                                            }">
                                            {{ party.type || 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ party.gst_number || 'N/A' }}</td>
                                    <td>{{ party.mobile || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/parties/${party.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteParty(party.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="parties.data.length === 0">
                                    <td colspan="7" class="text-center py-4">No parties found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="parties" />
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
    parties: Object
});

const form = useForm({});

const deleteParty = (id) => {
    if (confirm('Are you sure you want to delete this party?')) {
        form.delete(`/parties/${id}`);
    }
};
</script>

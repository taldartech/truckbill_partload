<template>
    <AdminLayout title="Agents">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Agents</h3>
                        <Link href="/agents/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Agent
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="agent in agents.data" :key="agent.id">
                                    <td>{{ agent.id }}</td>
                                    <td>{{ agent.name }}</td>
                                    <td>{{ agent.company ? agent.company.name : 'N/A' }}</td>
                                    <td>{{ agent.mobile || 'N/A' }}</td>
                                    <td>{{ agent.city || 'N/A' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/agents/${agent.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteItem(agent.id)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="agents.data.length === 0">
                                    <td colspan="6" class="text-center py-4">No agents found.</td>
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
    agents: Object
});

const form = useForm({});

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this agent?')) {
        form.delete(`/agents/${id}`);
    }
};
</script>

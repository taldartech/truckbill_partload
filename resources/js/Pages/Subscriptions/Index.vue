<template>
    <AdminLayout title="Subscriptions">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tabs-plans-tab" data-bs-toggle="pill" href="#tabs-plans" role="tab">Subscription Plans</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tabs-active-tab" data-bs-toggle="pill" href="#tabs-active" role="tab">Active Subscriptions</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Plans Tab -->
                            <div class="tab-pane fade show active" id="tabs-plans" role="tabpanel">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5 class="mb-0">Available Tiers</h5>
                                    <button v-if="can('create subscriptions')" class="btn btn-primary btn-sm" @click="openModal()">
                                        <i class="fas fa-plus"></i> Create New Plan
                                    </button>
                                </div>
                                <div class="row">
                                    <div v-for="plan in plans" :key="plan.id" class="col-md-4">
                                        <div class="card shadow-sm border-primary">
                                            <div class="card-header bg-primary text-white text-center py-3">
                                                <h4 class="mb-0 fw-bold text-uppercase">{{ plan.name }}</h4>
                                            </div>
                                            <div class="card-body text-center">
                                                <h2 class="display-6 fw-bold">₹{{ plan.price }}</h2>
                                                <p class="text-muted">for {{ plan.duration_days }} days</p>
                                                <hr>
                                                <ul class="list-unstyled text-start px-3">
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>{{ plan.max_users }}</strong> Total Users</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>{{ plan.max_branches }}</strong> Branches</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Full CRM Access</li>
                                                </ul>
                                            </div>
                                            <div class="card-footer bg-white border-top-0 d-flex justify-content-center pb-3">
                                                <button class="btn btn-outline-warning btn-sm me-2" @click="editPlan(plan)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" @click="deletePlan(plan.id)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Active Subscriptions Tab (Placeholder) -->
                            <div class="tab-pane fade" id="tabs-active" role="tabpanel">
                                <p class="text-muted text-center py-5">Active company subscriptions will appear here once linked.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="planModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Plan' : 'Create New Plan' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label>Plan Name</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label>Max Users</label>
                                        <input type="number" class="form-control" v-model="form.max_users" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label>Max Branches</label>
                                        <input type="number" class="form-control" v-model="form.max_branches" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label>Price (₹)</label>
                                        <input type="number" step="0.01" class="form-control" v-model="form.price" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label>Duration (Days)</label>
                                        <input type="number" class="form-control" v-model="form.duration_days" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ editMode ? 'Update Plan' : 'Save Plan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {  useForm, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    plans: Array
});

const form = useForm({
    id: null,
    name: '',
    max_users: 1,
    max_branches: 1,
    price: 0,
    duration_days: 365
});

const editMode = ref(false);
let modal = null;

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('planModal'));
});

const openModal = () => {
    editMode.value = false;
    form.reset();
    modal.show();
};

const editPlan = (plan) => {
    editMode.value = true;
    form.id = plan.id;
    form.name = plan.name;
    form.max_users = plan.max_users;
    form.max_branches = plan.max_branches;
    form.price = plan.price;
    form.duration_days = plan.duration_days;
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/subscriptions/${form.id}`, {
            onSuccess: () => closeModal()
        });
    } else {
        form.post('/subscriptions', {
            onSuccess: () => closeModal()
        });
    }
};

const deletePlan = (id) => {
    if (confirm('Are you sure you want to delete this plan?')) {
        router.delete(`/subscriptions/${id}`);
    }
};
</script>

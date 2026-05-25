<template>
    <AdminLayout title="Edit Branch">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Update Branch Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter Name">
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" id="address" v-model="form.address" placeholder="Enter Address">
                                <div v-if="form.errors.address" class="text-danger mt-1 small">{{ form.errors.address }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" v-model="form.city" placeholder="Enter City">
                                <div v-if="form.errors.city" class="text-danger mt-1 small">{{ form.errors.city }}</div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_head_office" v-model="form.is_head_office">
                                <label class="form-check-label" for="is_head_office">Is Head Office</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/branchs" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    branch: Object
});

const form = useForm({
    name: props.branch.name || '',
    address: props.branch.address || '',
    city: props.branch.city || '',
    is_head_office: props.branch.is_head_office ? true : false
});

const submit = () => {
    form.put(`/branchs/${props.branch.id}`);
};
</script>
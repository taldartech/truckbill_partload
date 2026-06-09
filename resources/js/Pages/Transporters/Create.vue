<template>
    <AdminLayout title="Add Transporter">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Transporter Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
<div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter Name">
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="short_name">Short Name</label>
                                <input type="text" class="form-control" id="short_name" v-model="form.short_name" placeholder="Enter Short Name">
                                <div v-if="form.errors.short_name" class="text-danger mt-1 small">{{ form.errors.short_name }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="gst_number">Gst Number</label>
                                <input type="text" class="form-control" id="gst_number" v-model="form.gst_number" placeholder="Enter Gst Number">
                                <div v-if="form.errors.gst_number" class="text-danger mt-1 small">{{ form.errors.gst_number }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="pan_number">Pan Number</label>
                                <input type="text" class="form-control" id="pan_number" v-model="form.pan_number" placeholder="Enter Pan Number">
                                <div v-if="form.errors.pan_number" class="text-danger mt-1 small">{{ form.errors.pan_number }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/transporters" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Transporter
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
});

const form = useForm({
    name: '',
    short_name: '',
    gst_number: '',
    pan_number: ''
});

const submit = () => {
    form.post('/transporters');
};
</script>
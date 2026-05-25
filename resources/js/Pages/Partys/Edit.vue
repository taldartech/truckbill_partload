<template>
    <AdminLayout title="Edit Party">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Update Party Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter Name">
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="type">Type</label>
                                <input type="text" class="form-control" id="type" v-model="form.type" placeholder="Enter Type">
                                <div v-if="form.errors.type" class="text-danger mt-1 small">{{ form.errors.type }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="gst_number">Gst Number</label>
                                <input type="text" class="form-control" id="gst_number" v-model="form.gst_number" placeholder="Enter Gst Number">
                                <div v-if="form.errors.gst_number" class="text-danger mt-1 small">{{ form.errors.gst_number }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="mobile">Mobile</label>
                                <input type="text" class="form-control" id="mobile" v-model="form.mobile" placeholder="Enter Mobile">
                                <div v-if="form.errors.mobile" class="text-danger mt-1 small">{{ form.errors.mobile }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/parties" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Party
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
    party: Object
});

const form = useForm({
    name: props.party.name || '',
    type: props.party.type || '',
    gst_number: props.party.gst_number || '',
    mobile: props.party.mobile || ''
});

const submit = () => {
    form.put(`/parties/${props.party.id}`);
};
</script>
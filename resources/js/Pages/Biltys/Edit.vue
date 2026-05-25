<template>
    <AdminLayout title="Edit Bilty">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Update Bilty Details</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="bilty_number">Bilty Number</label>
                                <input type="text" class="form-control" id="bilty_number" v-model="form.bilty_number" placeholder="Enter Bilty Number">
                                <div v-if="form.errors.bilty_number" class="text-danger mt-1 small">{{ form.errors.bilty_number }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="date">Date</label>
                                <input type="date" class="form-control" id="date" v-model="form.date" placeholder="Enter Date">
                                <div v-if="form.errors.date" class="text-danger mt-1 small">{{ form.errors.date }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="amount">Amount</label>
                                <input type="text" class="form-control" id="amount" v-model="form.amount" placeholder="Enter Amount">
                                <div v-if="form.errors.amount" class="text-danger mt-1 small">{{ form.errors.amount }}</div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="status">Status</label>
                                <input type="text" class="form-control" id="status" v-model="form.status" placeholder="Enter Status">
                                <div v-if="form.errors.status" class="text-danger mt-1 small">{{ form.errors.status }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/bilties" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Bilty
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
    bilty: Object
});

const form = useForm({
    bilty_number: props.bilty.bilty_number || '',
    date: props.bilty.date || '',
    amount: props.bilty.amount || '',
    status: props.bilty.status || ''
});

const submit = () => {
    form.put(`/bilties/${props.bilty.id}`);
};
</script>
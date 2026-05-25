<template>
    <AdminLayout title="System Settings">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cog me-2"></i>Global Configuration</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">General Settings</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice Prefix</label>
                                    <input type="text" class="form-control" v-model="form.invoice_prefix" placeholder="e.g. INV-">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default GST Percentage (%)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.default_gst_percent">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Bilty Initial</label>
                                    <input type="text" class="form-control" v-model="form.bilty_initial" placeholder="e.g. BLT-">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.challan_initial" placeholder="e.g. CHL-">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Main Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.main_challan_initial" placeholder="e.g. MCH-">
                                </div>
                            </div>
                            
                            <hr>
                            <h5 class="text-primary mb-3">Company Logo</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Logo</label>
                                <input type="file" class="form-control" @change="e => form.logo = e.target.files[0]" accept="image/*">
                                <div v-if="props.settings.logo" class="mt-2">
                                    <img :src="props.settings.logo" alt="Current Logo" class="img-thumbnail" style="max-height: 80px;">
                                    <small class="text-muted d-block mt-1">Current Logo</small>
                                </div>
                                <small class="text-danger" v-if="form.errors.logo">{{ form.errors.logo }}</small>
                            </div>
                            
                            <hr>
                            <h5 class="text-primary mb-3">Print Configurations</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Bilty (Lorry Receipt) Terms & Conditions</label>
                                <textarea class="form-control" v-model="form.bilty_terms" rows="4" placeholder="Enter terms that will appear on printed Bilties..."></textarea>
                                <small class="text-muted">These will appear at the bottom of the Bilty print format.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tax Invoice Terms & Conditions</label>
                                <textarea class="form-control" v-model="form.invoice_terms" rows="4" placeholder="Enter terms that will appear on printed Invoices..."></textarea>
                                <small class="text-muted">These will appear at the bottom of the Invoice print format.</small>
                            </div>
                            
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    invoice_prefix: props.settings.invoice_prefix || '',
    default_gst_percent: props.settings.default_gst_percent || 18,
    bilty_terms: props.settings.bilty_terms || '',
    invoice_terms: props.settings.invoice_terms || '',
    bilty_initial: props.settings.bilty_initial || '',
    challan_initial: props.settings.challan_initial || '',
    main_challan_initial: props.settings.main_challan_initial || '',
    logo: null
});

const submit = () => {
    form.post('/settings', {
        preserveScroll: true
    });
};
</script>

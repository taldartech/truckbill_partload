<template>
    <AdminLayout title="Edit Delivery Receipt">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Delivery Receipt</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Receipt No.</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.delivery_receipt_no">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GR/Bilty No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.gr_no_bilty_no" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mode of Payment</label>
                                    <select class="form-select form-select-sm" v-model="form.mode_of_payment">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank">Bank</option>
                                        <option value="To Pay">To Pay</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Consignee <span class="text-danger">*</span></label>
                                    <SearchableSelect
    :small="true"
    v-model="form.consignee_id"
    :options="parties"
    placeholder="Select Consignee"
/>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Office Name</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.office_name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Haste</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.haste">
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3 text-warning">Charges Breakdown</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Freight</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.freight" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Handling Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.handling_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Risk/DD Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.risk_dd_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Delivery Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.delivery_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Stationary Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.stationary_charge" @input="calculateTotals"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 border-start">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Demurrage</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.demurrage" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Other Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.other_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Cartage Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.cartage_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0 fw-bold">Sub Total Amount</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm bg-light" v-model="form.total_amount" readonly></div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h6 class="mb-3 text-warning">GST & Final Calculation</h6>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">GST On</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.gst_on">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GST</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.gst" @input="calculateTotals">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GST Pay By</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.gst_pay_by">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Less</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.less" @input="calculateTotals">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <div class="row align-items-center border p-2 bg-light rounded">
                                        <div class="col-5"><label class="form-label mb-0 fw-bold fs-5 text-danger">Final Amount</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control fw-bold text-danger fs-5" v-model="form.final_amount" readonly></div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/delivery-receipts" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    receipt: Object,
    parties: Array
});

const form = useForm({
    delivery_receipt_no: props.receipt.delivery_receipt_no || '',
    date: props.receipt.date || new Date().toISOString().substr(0, 10),
    gr_no_bilty_no: props.receipt.gr_no_bilty_no || '',
    mode_of_payment: props.receipt.mode_of_payment || 'Cash',
    office_name: props.receipt.office_name || '',
    consignee_id: props.receipt.consignee_id || '',
    haste: props.receipt.haste || '',
    payment_due_from: props.receipt.payment_due_from || '',
    freight: props.receipt.freight || 0,
    handling_charge: props.receipt.handling_charge || 0,
    risk_dd_charge: props.receipt.risk_dd_charge || 0,
    delivery_charge: props.receipt.delivery_charge || 0,
    stationary_charge: props.receipt.stationary_charge || 0,
    demurrage: props.receipt.demurrage || 0,
    other_charge: props.receipt.other_charge || 0,
    total_amount: props.receipt.total_amount || 0,
    gst_on: props.receipt.gst_on || '',
    gst: props.receipt.gst || 0,
    gst_pay_by: props.receipt.gst_pay_by || '',
    less: props.receipt.less || 0,
    final_amount: props.receipt.final_amount || 0,
    cartage_charge: props.receipt.cartage_charge || 0
});

const calculateTotals = () => {
    let subTotal = 
        parseFloat(form.freight || 0) + 
        parseFloat(form.handling_charge || 0) + 
        parseFloat(form.risk_dd_charge || 0) + 
        parseFloat(form.delivery_charge || 0) + 
        parseFloat(form.stationary_charge || 0) + 
        parseFloat(form.demurrage || 0) + 
        parseFloat(form.other_charge || 0) + 
        parseFloat(form.cartage_charge || 0);
        
    form.total_amount = subTotal.toFixed(2);
    
    let finalTotal = subTotal + parseFloat(form.gst || 0) - parseFloat(form.less || 0);
    form.final_amount = finalTotal.toFixed(2);
};
</script>

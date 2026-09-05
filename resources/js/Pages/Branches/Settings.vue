<template>
    <AdminLayout :title="`Settings — ${branch.name}`">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Branch Settings — {{ branch.name }}</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <h5 class="text-primary border-bottom pb-2 mb-3">Document Initials <small class="text-muted fw-normal">(optional — leave blank for no prefix)</small></h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Bilty Initial</label>
                                    <input type="text" class="form-control" v-model="form.bilty_initial" placeholder="e.g. BLT-">
                                    <div v-if="form.errors.bilty_initial" class="text-danger small">{{ form.errors.bilty_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.challan_initial" placeholder="e.g. CHL-">
                                    <div v-if="form.errors.challan_initial" class="text-danger small">{{ form.errors.challan_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Main Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.main_challan_initial" placeholder="e.g. MCH-">
                                    <div v-if="form.errors.main_challan_initial" class="text-danger small">{{ form.errors.main_challan_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Invoice Initial</label>
                                    <input type="text" class="form-control" v-model="form.invoice_initial" placeholder="e.g. INV-">
                                    <div v-if="form.errors.invoice_initial" class="text-danger small">{{ form.errors.invoice_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Inward Register Initial</label>
                                    <input type="text" class="form-control" v-model="form.inward_register_initial" placeholder="e.g. IR-">
                                    <div v-if="form.errors.inward_register_initial" class="text-danger small">{{ form.errors.inward_register_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Delivery initial</label>
                                    <input type="text" class="form-control" v-model="form.delivery_initial" placeholder="e.g. DI-">
                                    <div v-if="form.errors.delivery_initial" class="text-danger small">{{ form.errors.delivery_initial }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Voucher Initial</label>
                                    <input type="text" class="form-control" v-model="form.voucher_initial" placeholder="e.g. VI-">
                                    <div v-if="form.errors.voucher_initial" class="text-danger small">{{ form.errors.voucher_initial }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Bilty Print Settings</h5>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Copy</th>
                                            <th class="text-center">Enabled</th>
                                            <th v-for="bob in BOB_KEYS" :key="bob" class="text-center">{{ BOB_LABELS[bob] }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="copy in biltyPrintCopies" :key="copy.key">
                                            <td>{{ copy.label }}</td>
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="form.bilty_print_config.copies[copy.key].enabled"
                                                >
                                            </td>
                                            <td v-for="bob in BOB_KEYS" :key="bob" class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="form.bilty_print_config.copies[copy.key].show_amount_by_bob[bob]"
                                                    :disabled="!form.bilty_print_config.copies[copy.key].enabled"
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group mb-3">
                                <label>Bilty Print Note</label>
                                <textarea
                                    class="form-control"
                                    rows="3"
                                    v-model="form.bilty_print_config.note"
                                    placeholder="Optional note printed on all bilty copies"
                                ></textarea>
                                <small class="text-muted">This note appears on every bilty copy when printing.</small>
                                <div v-if="form.errors['bilty_print_config.note']" class="text-danger small">{{ form.errors['bilty_print_config.note'] }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link :href="`/branches/${branch.id}/edit`" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
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
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    BOB_KEYS,
    BOB_LABELS,
    biltyPrintCopies,
    normalizeBiltyPrintConfigPayload,
    resolveBiltyPrintConfig,
} from '@/utils/biltyPrintConfig';

const props = defineProps({
    branch: Object,
    settings: Object,
});

const form = useForm({
    bilty_initial: props.settings.bilty_initial ?? '',
    challan_initial: props.settings.challan_initial ?? '',
    main_challan_initial: props.settings.main_challan_initial ?? '',
    invoice_initial: props.settings.invoice_initial ?? '',
    inward_register_initial: props.settings.inward_register_initial ?? '',
    voucher_initial: props.settings.voucher_initial ?? '',
    delivery_initial: props.settings.delivery_initial ?? '',
    bilty_print_config: resolveBiltyPrintConfig(props.settings.bilty_print_config),
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        bilty_print_config: normalizeBiltyPrintConfigPayload(data.bilty_print_config),
    })).put(`/branches/${props.branch.id}/settings`);
};
</script>

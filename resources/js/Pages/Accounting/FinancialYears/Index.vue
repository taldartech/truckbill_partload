<template>
    <AdminLayout title="Financial Years">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Financial Years</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create accounting_settings')"
                            type="button"
                            class="btn btn-sm app-index-header-btn"
                            @click="openModal()"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Starts On</th>
                                    <th>Ends On</th>
                                    <th>Current</th>
                                    <th>Status</th>
                                    <th class="text-end" v-if="can('edit accounting_settings')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="fy in financialYears" :key="fy.id">
                                    <td class="fw-bold text-brand-primary">{{ fy.name }}</td>
                                    <td>{{ fy.starts_on }}</td>
                                    <td>{{ fy.ends_on }}</td>
                                    <td>
                                        <span class="badge" :class="fy.is_current ? 'bg-primary' : 'bg-light text-dark'">
                                            {{ fy.is_current ? 'Current' : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="fy.is_closed ? 'bg-secondary' : 'bg-success'">
                                            {{ fy.is_closed ? 'Closed' : 'Open' }}
                                        </span>
                                    </td>
                                    <td class="text-end" v-if="can('edit accounting_settings')">
                                        <button
                                            v-if="!fy.is_current"
                                            type="button"
                                            class="btn btn-sm btn-primary me-2"
                                            @click="setCurrent(fy)"
                                        >
                                            Set Current
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-secondary"
                                            @click="toggleClosed(fy)"
                                        >
                                            {{ fy.is_closed ? 'Reopen' : 'Close' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="financialYears.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No financial years found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_financial_years" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Financial Year</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" v-model="form.name" required placeholder="FY 2026-27">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Starts On *</label>
                                <input type="date" class="form-control" v-model="form.starts_on" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ends On *</label>
                                <input type="date" class="form-control" v-model="form.ends_on" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Set as Current</label>
                                <select class="form-select" v-model="form.is_current">
                                    <option :value="true">Yes</option>
                                    <option :value="false">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

defineProps({
    financialYears: { type: Array, default: () => [] },
});

let modal = null;

const form = useForm({
    name: '',
    starts_on: '',
    ends_on: '',
    is_current: true,
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_financial_years'));
});

const openModal = () => {
    form.reset();
    form.is_current = true;
    form.clearErrors();
    modal.show();
};

const closeModal = () => modal.hide();

const submit = () => {
    form.post('/accounting/financial-years', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const setCurrent = (fy) => {
    router.post(`/accounting/financial-years/${fy.id}/set-current`, {}, { preserveScroll: true });
};

const toggleClosed = (fy) => {
    router.put(`/accounting/financial-years/${fy.id}`, {
        name: fy.name,
        is_closed: !fy.is_closed,
        is_current: fy.is_current,
    }, { preserveScroll: true });
};
</script>

<template>
    <AdminLayout title="Bank Accounts">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Bank Accounts</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create bank_accounts')"
                            type="button"
                            class="btn btn-sm app-index-header-btn"
                            @click="openModal()"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="bank-accounts-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="bank_accounts_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="bank_accounts_search"
                            placeholder="Name, bank, account, IFSC"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Bank</th>
                                    <th>Account No.</th>
                                    <th>IFSC</th>
                                    <th>Status</th>
                                    <th class="text-end"  v-if="can('edit bank_accounts') || can('edit bank_accounts')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in bankAccounts.data" :key="account.id">
                                    <td>
                                        <Link :href="`/bank-accounts/${account.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ account.name }}
                                        </Link>
                                    </td>
                                    <td>{{ account.bank_name || '—' }}</td>
                                    <td>{{ account.account_number || '—' }}</td>
                                    <td>{{ account.ifsc_code || '—' }}</td>
                                    <td>
                                        <span class="badge" :class="account.is_active ? 'bg-success' : 'bg-secondary'">
                                            {{ account.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end"  v-if="can('edit bank_accounts') || can('edit bank_accounts')">
                                        <button
                                            v-if="can('edit bank_accounts')"
                                            type="button"
                                            class="btn btn-sm btn-info text-white me-2"
                                            @click="editItem(account)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            v-if="can('delete bank_accounts')"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteItem(account.id)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bankAccounts.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No bank accounts found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="bankAccounts" />
            </div>
        </div>

        <div class="modal fade" id="modal_bank_accounts" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Bank Account' : 'Add Bank Account' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Account Name *</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" v-model="form.bank_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" v-model="form.account_number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" class="form-control" v-model="form.ifsc_code">
                            </div>
                            <div class="form-check">
                                <input id="bank_is_active" type="checkbox" class="form-check-input" v-model="form.is_active">
                                <label class="form-check-label" for="bank_is_active">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : (editMode ? 'Update' : 'Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const props = defineProps({
    bankAccounts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/bank-accounts');
const editMode = ref(false);
const editingId = ref(null);
let modal = null;

const form = useForm({
    name: '',
    bank_name: '',
    account_number: '',
    ifsc_code: '',
    is_active: true,
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_bank_accounts'));

    const editId = new URLSearchParams(window.location.search).get('edit');
    if (editId) {
        const account = props.bankAccounts.data.find((item) => String(item.id) === String(editId));
        if (account) {
            editItem(account);
        }
    }
});

const openModal = () => {
    editMode.value = false;
    editingId.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    modal.show();
};

const editItem = (account) => {
    editMode.value = true;
    editingId.value = account.id;
    form.name = account.name || '';
    form.bank_name = account.bank_name || '';
    form.account_number = account.account_number || '';
    form.ifsc_code = account.ifsc_code || '';
    form.is_active = account.is_active !== false;
    form.clearErrors();
    modal.show();
};

const closeModal = () => modal.hide();

const submit = () => {
    if (editMode.value) {
        form.put(`/bank-accounts/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post('/bank-accounts', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteItem = (id) => {
    if (!confirm('Delete this bank account?')) {
        return;
    }

    router.delete(`/bank-accounts/${id}`, { preserveScroll: true });
};
</script>

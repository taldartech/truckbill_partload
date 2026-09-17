<template>
    <AdminLayout title="Chart of Accounts">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Chart of Accounts</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create accounts')"
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
                    panel-id="accounts-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="accounts_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="accounts_search"
                            placeholder="Name or code"
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
                                    <th>Group</th>
                                    <th>Code</th>
                                    <th>Nature</th>
                                    <th>Status</th>
                                    <th class="text-end" v-if="can('edit accounts') || can('delete accounts')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in accounts.data" :key="account.id">
                                    <td>
                                        <Link :href="`/accounting/accounts/${account.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ account.name }}
                                        </Link>
                                        <div v-if="account.is_system" class="small text-muted">System</div>
                                    </td>
                                    <td>{{ account.account_group?.name || '—' }}</td>
                                    <td>{{ account.code || '—' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ account.account_group?.nature || '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="account.is_selectable ? 'bg-success' : 'bg-secondary'">
                                            {{ account.is_selectable ? 'Selectable' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="text-end" v-if="can('edit accounts') || can('delete accounts')">
                                        <button
                                            v-if="can('edit accounts')"
                                            type="button"
                                            class="btn btn-edit btn-sm me-2"
                                            @click="editItem(account)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            v-if="can('delete accounts') && !account.is_system"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteItem(account)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="accounts.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No accounts found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="accounts" />
            </div>
        </div>

        <div class="modal fade" id="modal_accounts" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Account' : 'Add Account' }}</h5>
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
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control" v-model="form.code">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Group *</label>
                                <select class="form-select" v-model="form.account_group_id" required :disabled="editingIsSystem">
                                    <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.nature }})</option>
                                </select>
                                <div v-if="form.errors.account_group_id" class="text-danger small">{{ form.errors.account_group_id }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Active</label>
                                    <select class="form-select" v-model="form.is_active">
                                        <option :value="true">Yes</option>
                                        <option :value="false">No</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Selectable</label>
                                    <select class="form-select" v-model="form.is_selectable">
                                        <option :value="true">Yes</option>
                                        <option :value="false">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ editMode ? 'Update' : 'Save' }}
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
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const props = defineProps({
    accounts: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/accounting/accounts');
const editMode = ref(false);
const editingId = ref(null);
const editingIsSystem = ref(false);
let modal = null;

const form = useForm({
    name: '',
    code: '',
    account_group_id: props.groups?.[0]?.id || '',
    is_active: true,
    is_selectable: true,
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_accounts'));
});

const openModal = () => {
    editMode.value = false;
    editingId.value = null;
    editingIsSystem.value = false;
    form.reset();
    form.account_group_id = props.groups?.[0]?.id || '';
    form.is_active = true;
    form.is_selectable = true;
    form.clearErrors();
    modal.show();
};

const editItem = (account) => {
    editMode.value = true;
    editingId.value = account.id;
    editingIsSystem.value = !!account.is_system;
    form.name = account.name || '';
    form.code = account.code || '';
    form.account_group_id = account.account_group_id;
    form.is_active = account.is_active !== false;
    form.is_selectable = account.is_selectable !== false;
    form.clearErrors();
    modal.show();
};

const closeModal = () => modal.hide();

const submit = () => {
    if (editMode.value) {
        form.put(`/accounting/accounts/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post('/accounting/accounts', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteItem = async (account) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this account?\n\nAccount: ${account.name}`
    );
    if (remark === null) return;

    router.delete(`/accounting/accounts/${account.id}`, {
        data: { delete_remark: remark },
        preserveScroll: true,
    });
};
</script>

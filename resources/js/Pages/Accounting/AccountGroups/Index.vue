<template>
    <AdminLayout title="Account Groups">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Account Groups</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create account_groups')"
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
                                    <th>Nature</th>
                                    <th>Parent</th>
                                    <th>Accounts</th>
                                    <th>Status</th>
                                    <th class="text-end" v-if="can('edit account_groups') || can('delete account_groups')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in groups" :key="group.id">
                                    <td>
                                        <span class="fw-bold text-brand-primary">{{ group.name }}</span>
                                        <div v-if="group.is_system" class="small text-muted">System</div>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ group.nature }}</span></td>
                                    <td>{{ group.parent?.name || '—' }}</td>
                                    <td>{{ group.accounts_count }}</td>
                                    <td>
                                        <span class="badge" :class="group.is_active ? 'bg-success' : 'bg-secondary'">
                                            {{ group.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end" v-if="can('edit account_groups') || can('delete account_groups')">
                                        <button
                                            v-if="can('edit account_groups')"
                                            type="button"
                                            class="btn btn-edit btn-sm me-2"
                                            @click="editItem(group)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            v-if="can('delete account_groups') && !group.is_system"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteItem(group)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="groups.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No account groups found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_account_groups" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Account Group' : 'Add Account Group' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nature *</label>
                                <select class="form-select" v-model="form.nature" required :disabled="editingIsSystem">
                                    <option v-for="n in natures" :key="n.value" :value="n.value">{{ n.label }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Parent Group</label>
                                <select class="form-select" v-model="form.parent_id" :disabled="editingIsSystem">
                                    <option value="">No parent</option>
                                    <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Active</label>
                                <select class="form-select" v-model="form.is_active">
                                    <option :value="true">Yes</option>
                                    <option :value="false">No</option>
                                </select>
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
import { useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const props = defineProps({
    groups: { type: Array, default: () => [] },
    natures: { type: Array, default: () => [] },
    parentOptions: { type: Array, default: () => [] },
});

const editMode = ref(false);
const editingId = ref(null);
const editingIsSystem = ref(false);
let modal = null;

const form = useForm({
    name: '',
    nature: 'assets',
    parent_id: '',
    is_active: true,
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_account_groups'));
});

const openModal = () => {
    editMode.value = false;
    editingId.value = null;
    editingIsSystem.value = false;
    form.reset();
    form.nature = 'assets';
    form.is_active = true;
    form.clearErrors();
    modal.show();
};

const editItem = (group) => {
    editMode.value = true;
    editingId.value = group.id;
    editingIsSystem.value = !!group.is_system;
    form.name = group.name || '';
    form.nature = group.nature;
    form.parent_id = group.parent_id || '';
    form.is_active = group.is_active !== false;
    form.clearErrors();
    modal.show();
};

const closeModal = () => modal.hide();

const submit = () => {
    if (editMode.value) {
        form.put(`/accounting/account-groups/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post('/accounting/account-groups', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteItem = async (group) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this account group?\n\nGroup: ${group.name}`
    );
    if (remark === null) return;

    router.delete(`/accounting/account-groups/${group.id}`, {
        data: { delete_remark: remark },
        preserveScroll: true,
    });
};
</script>

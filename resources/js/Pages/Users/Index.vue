<template>
    <AdminLayout title="User Management">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $page.props.auth.is_master_admin ? 'Super Admins' : 'Users List' }}</h3>
                        <div class="card-tools">
                            <button v-if="can('create users')" class="btn btn-primary btn-sm" @click="openModal()">
                                <i class="fas fa-plus"></i> Add New User
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Branch</th>
                                    <th>Role</th>
                                    <th v-if="invitesSuperAdmin">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id">
                                    <td>{{ user.id }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>{{ user.company ? user.company.name : 'System/Global' }}</td>
                                    <td>{{ user.branch ? user.branch.name : '—' }}</td>
                                    <td>
                                        <span v-for="role in user.roles" :key="role.id" class="badge bg-info me-1">
                                            {{ role.name }}
                                        </span>
                                    </td>
                                    <td v-if="invitesSuperAdmin">
                                        <span
                                            class="badge"
                                            :class="user.requires_onboarding ? 'bg-warning text-dark' : 'bg-success'"
                                        >
                                            {{ user.onboarding_status }}
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            v-if="invitesSuperAdmin && user.requires_onboarding && can('edit users')"
                                            class="btn btn-outline-primary btn-sm me-2"
                                            @click="resendInvitation(user.id)"
                                        >
                                            Resend Invite
                                        </button>
                                        <button v-if="can('edit users') && user.can_manage" class="btn btn-warning btn-sm me-2" @click="editUser(user)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-if="can('delete users') && user.can_manage" class="btn btn-danger btn-sm" @click="deleteUser(user.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit User' : 'Add New User' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" v-model="form.email" required>
                                <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                            </div>
                            <div class="form-group mb-3" v-if="showCompanyField">
                                <label>Company *</label>
                                <select class="form-control" v-model="form.company_id" @change="form.branch_id = ''" required>
                                    <option value="">Select Company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.company_id" class="text-danger small">{{ form.errors.company_id }}</div>
                            </div>
                            <div class="form-group mb-3" v-if="showBranchField">
                                <label>Branch *</label>
                                <select class="form-control" v-model="form.branch_id" :disabled="!form.company_id" required>
                                    <option value="">Select Branch</option>
                                    <option v-for="branch in filteredBranches" :key="branch.id" :value="branch.id">
                                        {{ branch.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.branch_id" class="text-danger small">{{ form.errors.branch_id }}</div>
                            </div>
                            <div class="form-group mb-3" v-if="!invitesSuperAdmin">
                                <label>Role</label>
                                <select class="form-control" v-model="form.role" required>
                                    <option value="">Select Role</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name }}
                                    </option>
                                </select>
                            </div>
                            <PasswordInput
                                v-if="showPasswordField"
                                v-model="form.password"
                                :label="editMode ? 'New Password (Leave blank to keep current)' : 'Password'"
                                wrapper-class="form-group mb-3"
                                :required="passwordRequired"
                                :error="form.errors.password"
                            />
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
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
import { ref, onMounted, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();
const page = usePage();

const props = defineProps({
    users: Object,
    roles: Array,
    companies: Array,
    branches: Array,
    assignable_roles: Array,
});

const operationalRoles = ['admin', 'moderator', 'accountant'];

const managesOperationalUsers = computed(() =>
    (props.assignable_roles ?? []).length > 0
    && props.assignable_roles.every((role) => operationalRoles.includes(role))
);

const invitesSuperAdmin = computed(() =>
    (props.assignable_roles ?? []).length === 1
    && props.assignable_roles[0] === 'super_admin'
);

const showPasswordField = computed(() => !invitesSuperAdmin.value || editMode.value);
const passwordRequired = computed(() => !editMode.value && !invitesSuperAdmin.value);

const form = useForm({
    id: null,
    name: '',
    email: '',
    company_id: '',
    branch_id: '',
    role: '',
    password: '',
});

const editMode = ref(false);
let modal = null;

const showCompanyField = computed(() =>
    managesOperationalUsers.value || operationalRoles.includes(form.role)
);
const showBranchField = computed(() =>
    managesOperationalUsers.value || operationalRoles.includes(form.role)
);
const filteredBranches = computed(() =>
    props.branches.filter((branch) => String(branch.company_id) === String(form.company_id))
);

const defaultCompanyId = () => {
    const companies = props.companies ?? [];
    if (companies.length === 1) {
        return companies[0].id;
    }

    return page.props.auth.active_company?.id ?? '';
};

const defaultBranchId = (companyId) => {
    if (! companyId) {
        return '';
    }

    const branches = props.branches.filter(
        (branch) => String(branch.company_id) === String(companyId)
    );

    if (branches.length === 1) {
        return branches[0].id;
    }

    const activeBranchId = page.props.auth.active_branch?.id;
    if (activeBranchId && branches.some((branch) => String(branch.id) === String(activeBranchId))) {
        return activeBranchId;
    }

    return '';
};

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('userModal'));
});

const openModal = () => {
    editMode.value = false;
    form.reset();

    if (managesOperationalUsers.value) {
        const companyId = defaultCompanyId();
        form.company_id = companyId;
        form.branch_id = defaultBranchId(companyId);
    }

    if (invitesSuperAdmin.value) {
        form.role = 'super_admin';
    }

    modal.show();
};

const editUser = (user) => {
    editMode.value = true;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.company_id = user.company_id || '';
    form.branch_id = user.branch_id || '';
    form.role = user.roles.length > 0 ? user.roles[0].name : '';
    form.password = '';
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/users/${form.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/users', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${id}`);
    }
};

const resendInvitation = (id) => {
    if (confirm('Resend onboarding invitation email with a new OTP?')) {
        router.post(`/users/${id}/resend-invitation`);
    }
};
</script>

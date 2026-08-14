<template>
    <AdminLayout title="Roles & Permissions">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Roles</h3>
                        <div class="card-tools">
                            <button v-if="can('create roles')" class="btn btn-primary btn-sm" @click="openModal()">
                                <i class="fas fa-plus"></i> Create Role
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in roles" :key="role.id">
                                    <td class="fw-bold">
                                        <Link :href="`/roles/${role.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ role.name }}
                                        </Link>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span v-for="perm in role.permissions" :key="perm.id" class="badge bg-secondary small">
                                                {{ perm.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <button v-if="can('edit roles')" class="btn btn-edit btn-sm me-2" @click="editRole(role)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" @click="deleteRole(role.id)" v-if="role.name !== 'Admin'">
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

        <!-- Modal -->
        <div class="modal fade modal-xl" id="roleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Role' : 'Create New Role' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-4">
                                <label class="fw-bold">Role Name</label>
                                <input type="text" class="form-control" v-model="form.name" required placeholder="e.g. Accountant">
                            </div>
                            
                            <label class="fw-bold mb-3">Assign Permissions</label>
                            <div class="row">
                                <div v-for="(perms, module) in groupedPermissions" :key="module" class="col-md-4 mb-4">
                                    <div class="card h-100 border-light shadow-sm">
                                        <div class="card-header py-2">
                                            <h6 class="mb-0 text-capitalize">{{ module }}</h6>
                                        </div>
                                        <div class="card-body py-2">
                                            <div v-for="perm in perms" :key="perm.id" class="form-check">
                                                <input class="form-check-input" type="checkbox" :id="'perm-'+perm.id" 
                                                       :value="perm.name" v-model="form.permissions">
                                                <label class="form-check-label small" :for="'perm-'+perm.id">
                                                    {{ perm.name.split(' ')[0] }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ editMode ? 'Update Role' : 'Save Role' }}
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
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    roles: Array,
    permissions: Array
});

const form = useForm({
    id: null,
    name: '',
    permissions: []
});

const editMode = ref(false);
let modal = null;

const groupedPermissions = computed(() => {
    const groups = {};
    props.permissions.forEach(perm => {
        const parts = perm.name.split(' ');
        const module = parts[1] || 'general';
        if (!groups[module]) groups[module] = [];
        groups[module].push(perm);
    });
    return groups;
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('roleModal'));

    const editId = new URLSearchParams(window.location.search).get('edit');
    if (editId) {
        const role = props.roles.find((item) => String(item.id) === String(editId));
        if (role) {
            editRole(role);
        }
    }
});

const openModal = () => {
    editMode.value = false;
    form.reset();
    modal.show();
};

const editRole = (role) => {
    editMode.value = true;
    form.id = role.id;
    form.name = role.name;
    form.permissions = role.permissions.map(p => p.name);
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/roles/${form.id}`, {
            onSuccess: () => closeModal()
        });
    } else {
        form.post('/roles', {
            onSuccess: () => closeModal()
        });
    }
};

const deleteRole = (id) => {
    if (confirm('Are you sure you want to delete this role?')) {
        router.delete(`/roles/${id}`);
    }
};
</script>

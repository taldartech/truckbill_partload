<template>
    <AdminLayout title="Branches">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title mb-0">Manage Branches</h3>
                            <p v-if="can_select_branch" class="text-muted small mb-0 mt-1">
                                Select a branch to operate as Admin for daily work.
                            </p>
                        </div>
                        <Link v-if="can('create branches')" href="/branches/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Branch
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th v-if="can_select_branch">Company</th>
                                    <th>Station</th>
                                    <th>City</th>
                                    <th>Type</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="branch in branches.data"
                                    :key="branch.id"
                                    :class="{ 'table-active': isActiveBranch(branch.id) }"
                                >
                                    <td>
                                        <Link :href="`/branches/${branch.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ branch.name || 'N/A' }}
                                        </Link>
                                        <span v-if="isActiveBranch(branch.id)" class="badge bg-success ms-2">Selected</span>
                                    </td>
                                    <td v-if="can_select_branch">{{ branch.company?.name || 'N/A' }}</td>
                                    <td>{{ branch.station?.name || 'N/A' }}</td>
                                    <td>{{ branch.city?.name || 'N/A' }}</td>
                                    <td>
                                        <span v-if="branch.is_head_office" class="badge bg-primary">Head Office</span>
                                        <span v-else class="badge bg-secondary">Branch</span>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            v-if="can_select_branch && !isActiveBranch(branch.id)"
                                            type="button"
                                            class="btn btn-sm btn-primary me-2"
                                            title="Select this branch"
                                            @click="selectBranch(branch.id)"
                                        >
                                            <i class="fas fa-check me-1"></i> Select
                                        </button>
                                        <Link
                                            v-if="can('edit branches')"
                                            :href="`/branches/${branch.id}/settings`"
                                            class="btn btn-outline-secondary btn-sm me-2"
                                            title="Branch Settings"
                                        >
                                            <i class="fas fa-cog"></i>
                                        </Link>
                                        <Link :href="`/branches/${branch.id}/edit`" class="btn btn-edit btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteBranch(branch)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="branches.data.length === 0">
                                    <td :colspan="can_select_branch ? 6 : 5" class="text-center py-4">No branches found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="branches" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    branches: Object,
    active_branch_id: {
        type: [Number, String, null],
        default: null,
    },
    can_select_branch: {
        type: Boolean,
        default: false,
    },
});

const isActiveBranch = (branchId) => String(props.active_branch_id) === String(branchId);

const selectBranch = (branchId) => {
    router.post('/session/branch', { branch_id: branchId });
};

const deleteBranch = async (branch) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this branch?

Branch  : ${branch.name || '—'}
Station : ${branch.station?.name || '—'}
City    : ${branch.city?.name || '—'}
Type    : ${branch.is_head_office ? 'Head Office' : 'Branch'}`
    );

    if (!remark) return;

    router.delete(`/branches/${branch.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

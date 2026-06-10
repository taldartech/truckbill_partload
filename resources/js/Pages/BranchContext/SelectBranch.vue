<template>
    <AdminLayout title="Select Branch">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select Branch to Continue</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Choose a branch to act as Admin for operational work. You can switch branches or return to setup mode anytime from the navbar.
                        </p>

                        <div v-if="branches.length === 0" class="alert alert-warning">
                            No branches found. Please create a company and branch first.
                            <Link href="/companies" class="alert-link ms-1">Manage Companies</Link>
                        </div>

                        <div v-else class="list-group">
                            <button
                                v-for="branch in branches"
                                :key="branch.id"
                                type="button"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                :class="{ active: active_branch_id === branch.id }"
                                @click="selectBranch(branch.id)"
                            >
                                <div>
                                    <div class="fw-semibold">{{ branch.name || 'Unnamed Branch' }}</div>
                                    <small :class="active_branch_id === branch.id ? 'text-white-50' : 'text-muted'">
                                        {{ branch.company?.name }}
                                    </small>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    branches: Array,
    active_branch_id: [Number, String, null],
});

const selectBranch = (branchId) => {
    router.post('/session/branch', { branch_id: branchId });
};
</script>

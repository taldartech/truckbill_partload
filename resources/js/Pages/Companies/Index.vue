<template>
    <AdminLayout title="Companies">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Companies</h3>
                        <Link v-if="can('create companies')" href="/companies/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Company
                        </Link>
                    </div>
                    <div class="card-body p-0">
                    <div class="d-sm-none">
                            <div v-for="company in companies.data" :key="company.id" class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="text-truncate">
                                        <Link :href="`/companies/${company.id}`" class="fw-bold text-decoration-none">
                                            {{ company.name }}
                                        </Link>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-2">
                                        <Link :href="`/companies/${company.id}/edit`"
                                            class="btn btn-sm btn-outline-info py-0 px-2" title="Edit Company">
                                            <i class="fas fa-edit small"></i>
                                        </Link>
                                        <button @click="deleteCompany(company)" class="btn btn-sm btn-danger py-0 px-2"
                                            title="Delete Company">
                                            <i class="fas fa-trash small"></i>
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="small text-muted mt-1 d-flex align-items-center justify-content-between text-truncate">
                                    <span class="text-truncate">
                                        <i class="fas fa-user me-1 small"></i>{{ company.contact_person || '—' }}
                                        <span v-if="company.contact_number">({{ company.contact_number }})</span>
                                    </span>
                                    <span v-if="company.city?.name" class="text-muted ms-2 text-nowrap">
                                        <i class="fas fa-location-dot me-1 small"></i>{{ company.city?.name }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="companies.data.length === 0" class="text-center text-muted py-4 small">
                                No companies found.
                            </div>
                        </div>

                        <div class="table-responsive d-none d-sm-block">
                            <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>Contact Person</th>
                                    <th>Contact Number</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="company in companies.data" :key="company.id">
                                    <td>
                                        <Link :href="`/companies/${company.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ company.name }}
                                        </Link>
                                    </td>
                                    <td>{{ company.email || 'N/A' }}</td>
                                    <td>{{ company.city?.name || 'N/A' }}</td>
                                    <td>{{ company.contact_person || 'N/A' }}</td>
                                    <td>{{ company.contact_number || 'N/A' }}</td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                        <Link :href="`/companies/${company.id}/edit`" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="deleteCompany(company)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="companies.data.length === 0">
                                    <td colspan="6" class="text-center py-4">No companies found.</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <PaginationLinks :pagination="companies" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    companies: Object,
});

const form = useForm({});

const deleteCompany = async (company) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this company?

Company : ${company.name || '—'}
Email   : ${company.email || '—'}
City    : ${company.city?.name || '—'}`
    );

    if (!remark) return;

    router.delete(`/companies/${company.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

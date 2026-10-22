<template>
    <AdminLayout title="Truck Types">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Truck Types List</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="showBulkUpload = true" v-if="can('create truck_types')">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--upload" aria-hidden="true"></span>
                            Bulk Upload
                        </button>
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="openModal()" v-if="can('create truck_types')">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus ms-1 ms-sm-0" aria-hidden="true"></span>
                            <span class="d-none d-sm-block">Create</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-success d-block d-sm-none"
                            :title="showFilters ? 'Hide Filters' : 'Show Filters'" @click="showFilters = !showFilters">
                            <i class="fas fa-filter p-1"></i>
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters v-if="showFilters"
                    panel-id="truck-types-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col col-12 col-sm-auto">
                        <label class="form-label" for="truck_types_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="truck_types_search"
                            placeholder="Name"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="d-sm-none">
                        <div v-for="(type, index) in truckTypes.data" :key="type.id" class="px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-truncate d-flex align-items-center">
                                    <span class="  me-2 flex-shrink-0">{{ (truckTypes.total ??
                                        truckTypes.data.length) - ((truckTypes.from ?? 1) - 1) - index }}</span>
                                    <Link :href="`/truck-types/${type.id}`"
                                        class="fw-bold text-decoration-none text-truncate">
                                        {{ type.name }}
                                    </Link>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-2"
                                    v-if="can('delete truck_types') || can('edit truck_types')">
                                    <button v-if="can('edit truck_types')" type="button"
                                        class="btn btn-sm btn-edit py-0 px-2" title="Edit Truck Type"
                                        @click="editType(type)">
                                        <i class="fas fa-edit small"></i>
                                    </button>
                                    <button v-if="can('delete truck_types')" type="button"
                                        class="btn btn-sm btn-danger py-0 px-2" title="Delete Truck Type"
                                        @click="deleteType(type)">
                                        <i class="fas fa-trash small"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="truckTypes.data.length === 0" class="text-center text-muted py-4 small">
                            No truck types found for selected filters.
                        </div>
                    </div>

                    <div class="table-responsive d-none d-sm-block">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th class="text-end" v-if="can('delete truck_types') || can('edit truck_types')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="type in truckTypes.data" :key="type.id">
                                    <td>{{ type.id }}</td>
                                    <td>
                                        <Link :href="`/truck-types/${type.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ type.name }}
                                        </Link>
                                    </td>
                                    <td class="text-end" v-if="can('delete truck_types') || can('edit truck_types')">
                                        <div class="table-actions">
                                        <button type="button" class="btn btn-edit btn-sm" @click="editType(type)">
                                            <i class="fas fa-edit" v-if="can('edit truck_types')"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="deleteType(type)">
                                            <i class="fas fa-trash" v-if="can('delete truck_types')"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="truckTypes.data.length === 0">
                                    <td colspan="3" class="text-center text-muted py-4">No truck types found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="truckTypes" />
            </div>
        </div>

        <div class="modal fade" id="truckTypeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Truck Type' : 'Add New Truck Type' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                            </div>
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

        <BulkUploadModal
            v-model:show="showBulkUpload"
            title="Bulk Upload Truck Types"
            upload-url="/truck-types/bulk-upload"
            template-download-url="/truck-types/bulk-upload/template"
            template-file-name="truck_types_bulk_upload_template.csv"
            :columns="bulkUploadColumns"
            :sample-data="bulkUploadSampleData"
            @success="onBulkUploadSuccess"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import BulkUploadModal from '@/Components/BulkUploadModal.vue';
import { useResponsiveIndexFilters } from '@/composables/useResponsiveIndexFilters';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const { showFilters } = useResponsiveIndexFilters();

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};
const props = defineProps({
    truckTypes: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/truck-types');

const form = useForm({
    id: null,
    name: '',
});

const editMode = ref(false);
const showBulkUpload = ref(false);

const bulkUploadColumns = [
    {
        name: 'name',
        label: 'Name',
        required: true,
        description: 'Truck Type Name (e.g. 14 Ft Open Body, 32 Ft Container)',
    },
];


let modal = null;

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('truckTypeModal'));

    const editId = new URLSearchParams(window.location.search).get('edit');
    const editTarget = editId ? props.truckTypes.data.find((type) => String(type.id) === editId) : null;

    if (editTarget) {
        editType(editTarget);
    }
});

const openModal = () => {
    editMode.value = false;
    form.reset();
    modal.show();
};

const editType = (type) => {
    editMode.value = true;
    form.id = type.id;
    form.name = type.name;
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/truck-types/${form.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/truck-types', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteType = async (truckType) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this truck type?

Truck Type : ${truckType.name}`
    );

    if (!remark) return;

    router.delete(`/truck-types/${truckType.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

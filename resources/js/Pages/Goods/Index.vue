<template>
    <AdminLayout title="Goods">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Goods List</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="showBulkUpload = true" v-if="can('create goods')">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--upload" aria-hidden="true"></span>
                            Bulk Upload
                        </button>
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="openModal()" v-if="can('create goods')">
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
                    panel-id="goods-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col col-12 col-sm-auto">
                        <label class="form-label" for="goods_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="goods_search"
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
                        <div v-for="(item, index) in items.data" :key="item.id" class="px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-truncate d-flex align-items-center">
                                    <span class="  me-2 flex-shrink-0">{{ (items.total ??
                                        items.data.length) - ((items.from ?? 1) - 1) - index }}</span>
                                    <Link :href="`/goods/${item.id}`"
                                        class="fw-bold text-decoration-none text-truncate">
                                        {{ item.name }}
                                    </Link>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-2"
                                    v-if="can('edit goods') || can('delete goods')">
                                    <button v-if="can('edit goods')" type="button" class="btn btn-sm btn-edit py-0 px-2"
                                        title="Edit Good" @click="editItem(item)">
                                        <i class="fas fa-edit small"></i>
                                    </button>
                                    <button v-if="can('delete goods')" type="button"
                                        class="btn btn-sm btn-danger py-0 px-2" title="Delete Good"
                                        @click="deleteItem(item)">
                                        <i class="fas fa-trash small"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="items.data.length === 0" class="text-center text-muted py-4 small">
                            No goods found for selected filters.
                        </div>
                    </div>

                    <div class="table-responsive d-none d-sm-block">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr No.</th>
                                    <th>Name</th>
                                    <th class="text-end" v-if="can('edit goods') || can('delete goods')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in items.data" :key="item.id">
                                    <td>{{ (items.from ?? 1) + index }}</td>
                                    <td>
                                        <Link :href="`/goods/${item.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ item.name }}
                                        </Link>
                                    </td>
                                    <td class="text-end" v-if="can('edit goods') || can('delete goods')">
                                        <div class="table-actions">
                                        <button type="button" class="btn btn-edit btn-sm" @click="editItem(item)" v-if="can('edit goods')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="deleteItem(item)" v-if="can('delete goods')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="items.data.length === 0">
                                    <td colspan="3" class="text-center text-muted py-4">No goods found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="items" />
            </div>
        </div>

        <div class="modal fade" id="modal_goods" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add New' }}</h5>
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
            title="Bulk Upload Goods"
            upload-url="/goods/bulk-upload"
            template-download-url="/goods/bulk-upload/template"
            template-file-name="goods_bulk_upload_template.csv"
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
    items: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/goods');

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
        description: 'Goods Name',
    },
];



let modal = null;

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_goods'));

    const editId = new URLSearchParams(window.location.search).get('edit');
    const editTarget = editId ? props.items.data.find((item) => String(item.id) === editId) : null;

    if (editTarget) {
        editItem(editTarget);
    }
});

const openModal = () => {
    editMode.value = false;
    form.reset();
    modal.show();
};

const editItem = (item) => {
    editMode.value = true;
    form.id = item.id;
    form.name = item.name;
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/goods/${form.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/goods', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = async (good) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this item?

Item : ${good.name}`
    );

    if (!remark) return;

    router.delete(`/goods/${good.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};
</script>

<template>
    <AdminLayout title="Goods">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Goods List</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="openModal()">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="goods-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
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
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.name }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-edit btn-sm me-2" @click="editItem(item)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="deleteItem(item.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';

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
let modal = null;

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_goods'));
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

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this item?')) {
        router.delete(`/goods/${id}`);
    }
};
</script>

<template>
    <AdminLayout title="Truck Types">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Truck Types List</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="openModal()">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="truck-types-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
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
                                <tr v-for="type in truckTypes.data" :key="type.id">
                                    <td>{{ type.id }}</td>
                                    <td>
                                        <Link :href="`/truck-types/${type.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ type.name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-edit btn-sm me-2" @click="editType(type)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="deleteType(type.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';

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

const deleteType = (id) => {
    if (confirm('Are you sure you want to delete this truck type?')) {
        router.delete(`/truck-types/${id}`);
    }
};
</script>

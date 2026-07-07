<template>
    <AdminLayout title="Goods">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Goods List</h3>
                        <div class="card-tools">
                            <button class="btn btn-primary btn-sm" @click="openModal()">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.name }}</td>
                                    <td>
                                        <button class="btn btn-edit btn-sm me-2" @click="editItem(item)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" @click="deleteItem(item.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="items" />
                </div>
            </div>
        </div>

        <!-- Modal -->
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

const props = defineProps({
    items: Object,
});

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
            onSuccess: () => closeModal()
        });
    } else {
        form.post('/goods', {
            onSuccess: () => closeModal()
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this item?')) {
        router.delete(`/goods/${id}`);
    }
};
</script>
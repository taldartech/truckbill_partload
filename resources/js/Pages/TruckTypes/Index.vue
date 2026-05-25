<template>
    <AdminLayout title="Truck Types">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Truck Types List</h3>
                        <div class="card-tools">
                            <button class="btn btn-primary btn-sm" @click="openModal()">
                                <i class="fas fa-plus"></i> Add New Truck Type
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
                                <tr v-for="type in truckTypes.data" :key="type.id">
                                    <td>{{ type.id }}</td>
                                    <td>{{ type.name }}</td>
                                    <td>
                                        <button class="btn btn-warning btn-sm me-2" @click="editType(type)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" @click="deleteType(type.id)">
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
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    truckTypes: Object,
});

const form = useForm({
    id: null,
    name: '',
});

const editMode = ref(false);
let modal = null;

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('truckTypeModal'));
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
            onSuccess: () => closeModal()
        });
    } else {
        form.post('/truck-types', {
            onSuccess: () => closeModal()
        });
    }
};

const deleteType = (id) => {
    if (confirm('Are you sure you want to delete this truck type?')) {
        router.delete(`/truck-types/${id}`);
    }
};
</script>

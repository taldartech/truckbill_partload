<template>
    <AdminLayout title="Stations">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Stations</h3>
                        <button
                            v-if="can('create stations')"
                            type="button"
                            class="btn btn-primary btn-sm ms-auto"
                            @click="openModal()"
                        >
                            <i class="fas fa-plus"></i> Add New Station
                        </button>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="station in stations.data" :key="station.id">
                                    <td>{{ station.id }}</td>
                                    <td>{{ station.name || 'N/A' }}</td>
                                    <td>{{ station.city?.name || 'N/A' }}</td>
                                    <td>{{ station.state?.name || 'N/A' }}</td>
                                    <td class="text-end">
                                        <button
                                            v-if="can('edit stations')"
                                            type="button"
                                            class="btn btn-edit btn-sm me-2"
                                            @click="editItem(station)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            v-if="can('delete stations')"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteItem(station.id)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="stations.data.length === 0">
                                    <td colspan="5" class="text-center py-4">No records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="stations" />
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_stations" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Station' : 'Add New Station' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label for="station_name">Station Name *</label>
                                <input
                                    id="station_name"
                                    type="text"
                                    class="form-control"
                                    v-model="form.name"
                                    placeholder="Enter Station Name"
                                    required
                                >
                                <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>State *</label>
                                        <SearchableSelect
                                            v-model="form.state_id"
                                            :options="stateOptions"
                                            master="state"
                                            placeholder="Select State"
                                            @created="(record) => appendMasterOption(stateOptions, record)"
                                        />
                                        <div v-if="form.errors.state_id" class="text-danger small mt-1">{{ form.errors.state_id }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>City *</label>
                                        <SearchableSelect
                                            v-model="form.city_id"
                                            :options="filteredCities"
                                            master="city"
                                            :quick-add-context="{ state_id: form.state_id }"
                                            placeholder="Select City"
                                            :disabled="!form.state_id"
                                            @created="(record) => appendMasterOption(cityOptions, record)"
                                        />
                                        <div v-if="form.errors.city_id" class="text-danger small mt-1">{{ form.errors.city_id }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : (editMode ? 'Update' : 'Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';

const props = defineProps({
    stations: Object,
    states: Array,
    cities: Array,
});

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });

const form = useForm({
    id: null,
    name: '',
    state_id: '',
    city_id: '',
});

const editMode = ref(false);
let modal = null;

const filteredCities = computed(() => {
    if (!form.state_id) {
        return [];
    }

    return cityOptions.value.filter((city) => String(city.state_id) === String(form.state_id));
});

watch(() => form.state_id, () => {
    if (!form.city_id) {
        return;
    }

    const cityExists = filteredCities.value.some((city) => String(city.id) === String(form.city_id));
    if (!cityExists) {
        form.city_id = '';
    }
});

onMounted(() => {
    modal = new bootstrap.Modal(document.getElementById('modal_stations'));
});

const openModal = () => {
    editMode.value = false;
    form.clearErrors();
    form.reset();
    modal.show();
};

const editItem = (station) => {
    editMode.value = true;
    form.clearErrors();
    form.id = station.id;
    form.name = station.name || '';
    form.state_id = station.state_id || station.state?.id || '';
    form.city_id = station.city_id || station.city?.id || '';
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/stations/${form.id}`, {
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post('/stations', {
        onSuccess: () => closeModal(),
    });
};

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        router.delete(`/stations/${id}`);
    }
};
</script>

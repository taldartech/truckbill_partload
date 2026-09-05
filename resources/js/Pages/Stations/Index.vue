<template>
    <AdminLayout title="Stations">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Manage Stations</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create stations')"
                            type="button"
                            class="btn btn-sm app-index-header-btn"
                            @click="openModal()"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="stations-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="stations_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="stations_search"
                            placeholder="Station, city, state"
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
                                    <th>City</th>
                                    <th>State</th>
                                    <th class="text-end" v-if="can('delete stations') || can('edit stations')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="station in stations.data" :key="station.id">
                                    <td>{{ station.id }}</td>
                                    <td>
                                        <Link :href="`/stations/${station.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ station.name || 'N/A' }}
                                        </Link>
                                    </td>
                                    <td>{{ station.city?.name || 'N/A' }}</td>
                                    <td>{{ station.state?.name || 'N/A' }}</td>
                                    <td class="text-end" v-if="can('delete stations') || can('edit stations')">
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
                                            @click="deleteItem(station)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="stations.data.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">No stations found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="stations" />
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

                            <div v-if="canSetDefaultAgent" class="form-group mb-0">
                                <label>Default Agent</label>
                                <SearchableSelect
                                    v-model="form.default_agent_id"
                                    :options="agentOptions"
                                    master="agent"
                                    placeholder="Select Default Agent"
                                    @created="(record) => appendMasterOption(agentOptions, record)"
                                />
                                <div class="form-text">Used as Delivery At when this station is selected as To Station on a bilty.</div>
                                <div v-if="form.errors.default_agent_id" class="text-danger small mt-1">{{ form.errors.default_agent_id }}</div>
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const props = defineProps({
    stations: Object,
    states: Array,
    cities: Array,
    agents: { type: Array, default: () => [] },
    can_set_default_agent: { type: Boolean, default: false },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/stations');

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
const canSetDefaultAgent = computed(() => !!props.can_set_default_agent);

const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
const agentOptions = ref([...(props.agents || [])]);
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });
watch(() => props.agents, (v) => { agentOptions.value = [...(v || [])]; });

const form = useForm({
    id: null,
    name: '',
    state_id: '',
    city_id: '',
    default_agent_id: '',
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
    form.default_agent_id = station.default_agent_id || '';
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

const deleteItem = async (station) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this station?

Station : ${station.name}`
    );

    if (!remark) return;

    router.delete(`/stations/${station.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const editId = Number(params.get('edit') || 0);

    if (!editId) {
        return;
    }

    const row = props.stations.data.find((station) => station.id === editId);

    if (row) {
        editItem(row);
    }

    window.history.replaceState({}, '', window.location.pathname);
});
</script>

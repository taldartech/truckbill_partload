<template>
    <AdminLayout title="Consignees / Customers">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Manage Parties (Consignees/Customers)</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button
                            v-if="can('create parties')"
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
                    panel-id="parties-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="parties_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="parties_search"
                            placeholder="Name, GST, mobile"
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
                                    <th>GST Number</th>
                                    <th>Mobile</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="party in parties.data" :key="party.id">
                                    <td>{{ party.id }}</td>
                                    <td>
                                        <Link :href="`/parties/${party.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ party.name }}
                                        </Link>
                                    </td>
                                    <td>{{ party.gst_number || 'N/A' }}</td>
                                    <td>{{ party.mobile || 'N/A' }}</td>
                                    <td class="text-end">
                                        <button
                                            v-if="can('edit parties')"
                                            type="button"
                                            class="btn btn-edit btn-sm me-2"
                                            @click="editItem(party)"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            v-if="can('delete parties')"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteParty(party.id)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="parties.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No parties found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="parties" />
            </div>
        </div>

        <div class="modal fade" id="modal_parties" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Party' : 'Add New Party' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Group</label>
                                    <SearchableSelect
                                        v-model="form.group_id"
                                        :options="groupOptions"
                                        master="group"
                                        placeholder="No Group"
                                        @created="(record) => appendMasterOption(groupOptions, record)"
                                    />
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Party Name *</label>
                                <input type="text" class="form-control" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Address *</label>
                                <input type="text" class="form-control" v-model="form.address" required>
                                <div v-if="form.errors.address" class="text-danger mt-1 small">{{ form.errors.address }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        v-model="form.state_id"
                                        :options="stateOptions"
                                        master="state"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
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
                                    <div v-if="form.errors.city_id" class="text-danger mt-1 small">{{ form.errors.city_id }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>Mobile *</label>
                                    <input type="text" class="form-control" v-model="form.mobile" required maxlength="10">
                                    <div v-if="form.errors.mobile" class="text-danger mt-1 small">{{ form.errors.mobile }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                    <div v-if="form.errors.email" class="text-danger mt-1 small">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>GST Number</label>
                                    <input type="text" class="form-control" v-model="form.gst_number">
                                    <div v-if="form.errors.gst_number" class="text-danger mt-1 small">{{ form.errors.gst_number }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>Bilty Charge (Fixed)</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.bilty_charge">
                                    <div v-if="form.errors.bilty_charge" class="text-danger mt-1 small">{{ form.errors.bilty_charge }}</div>
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';

const props = defineProps({
    parties: Object,
    groups: {
        type: Array,
        default: () => [],
    },
    cities: {
        type: Array,
        default: () => [],
    },
    states: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/parties');

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const groupOptions = ref([...(props.groups || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.groups, (v) => { groupOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });

const form = useForm({
    id: null,
    group_id: '',
    name: '',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    email: '',
    gst_number: '',
    bilty_charge: '',
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
    modal = new bootstrap.Modal(document.getElementById('modal_parties'));
});

const openModal = () => {
    editMode.value = false;
    form.clearErrors();
    form.reset();
    modal.show();
};

const editItem = (party) => {
    editMode.value = true;
    form.clearErrors();
    form.id = party.id;
    form.group_id = party.group_id || party.group?.id || '';
    form.name = party.name || '';
    form.address = party.address || '';
    form.state_id = party.state_id || party.state?.id || '';
    form.city_id = party.city_id || party.city?.id || '';
    form.mobile = party.mobile || '';
    form.email = party.email || '';
    form.gst_number = party.gst_number || '';
    form.bilty_charge = party.bilty_charge ?? '';
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/parties/${form.id}`, {
            onSuccess: () => closeModal(),
        });

        return;
    }

    form.post('/parties', {
        onSuccess: () => closeModal(),
    });
};

const deleteParty = (id) => {
    if (confirm('Are you sure you want to delete this party?')) {
        router.delete(`/parties/${id}`);
    }
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const editId = Number(params.get('edit') || 0);

    if (!editId) {
        return;
    }

    const row = props.parties.data.find((party) => party.id === editId);

    if (row) {
        editItem(row);
    }

    window.history.replaceState({}, '', window.location.pathname);
});
</script>

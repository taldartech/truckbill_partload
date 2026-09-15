<template>
    <AdminLayout title="Agents">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Manage Agents</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button v-if="can('create agents')" type="button" class="btn btn-sm app-index-header-btn"
                            @click="showBulkUpload = true">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--upload"
                                aria-hidden="true"></span>
                            Bulk Upload
                        </button>
                        <button v-if="can('create agents')" type="button" class="btn btn-sm app-index-header-btn"
                            @click="openModal()">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus"
                                aria-hidden="true"></span>
                            Create
                        </button>
                    </div>
                </div>

                <AppIndexCollapsibleFilters panel-id="agents-filters-panel" :loading="searching" @search="applyFilters"
                    @reset="resetFilters">
                    <div class="filter-search-col">
                        <label class="form-label" for="agents_search">Search</label>
                        <AppIndexSearchInput v-model="filters.search" input-id="agents_search"
                            placeholder="Name, mobile, GST" max-width="100%" size="md" :disabled="searching"
                            @keydown.enter="applyFilters" />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th class="text-end" v-if="can('delete agents') || can('edit agents')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="agent in agents.data" :key="agent.id">
                                    <td>{{ agent.id }}</td>
                                    <td>
                                        <Link :href="`/agents/${agent.id}`"
                                            class="text-decoration-none fw-bold text-brand-primary">
                                            {{ agent.name }}
                                        </Link>
                                    </td>
                                    <td>{{ agent.mobile || 'N/A' }}</td>
                                    <td>{{ agent.city?.name || agent.city || 'N/A' }}</td>
                                    <td class="text-end" v-if="can('delete agents')">
                                        <button v-if="can('edit agents')" type="button" class="btn btn-edit btn-sm me-2"
                                            @click="editItem(agent)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-if="can('delete agents')" type="button" class="btn btn-sm btn-danger"
                                            @click="deleteItem(agent)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="agents.data.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">No agents found for selected
                                        filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="agents" />
            </div>
        </div>

        <div class="modal fade" id="modal_agents" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Agent' : 'Add New Agent' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label for="agent_name">Agent Name *</label>
                                <input type="text" class="form-control" id="agent_name" v-model="form.name" required>
                                <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="agent_address">Address *</label>
                                <input type="text" class="form-control" id="agent_address" v-model="form.address"
                                    required>
                                <div v-if="form.errors.address" class="text-danger mt-1 small">{{ form.errors.address }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect v-model="form.state_id" :options="stateOptions" master="state"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)" />
                                    <div v-if="form.errors.state_id" class="text-danger mt-1 small">{{
                                        form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>City *</label>
                                    <SearchableSelect v-model="form.city_id" :options="filteredCities" master="city"
                                        :quick-add-context="{ state_id: form.state_id }" placeholder="Select City"
                                        :disabled="!form.state_id"
                                        @created="(record) => appendMasterOption(cityOptions, record)" />
                                    <div v-if="form.errors.city_id" class="text-danger mt-1 small">{{
                                        form.errors.city_id }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="agent_mobile">Mobile *</label>
                                    <input type="text" class="form-control" id="agent_mobile" v-model="form.mobile"
                                        required maxlength="10">
                                    <div v-if="form.errors.mobile" class="text-danger mt-1 small">{{ form.errors.mobile
                                    }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="agent_email">Email</label>
                                    <input type="email" class="form-control" id="agent_email" v-model="form.email">
                                    <div v-if="form.errors.email" class="text-danger mt-1 small">{{ form.errors.email }}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="agent_gst_no">GST Number</label>
                                    <input type="text" class="form-control" id="agent_gst_no" v-model="form.gst_no">
                                    <div v-if="form.errors.gst_no" class="text-danger mt-1 small">{{ form.errors.gst_no }}
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="agent_transport_id">Transporter Id</label>
                                    <input type="text" class="form-control" id="agent_transport_id"
                                        v-model="form.transport_id">
                                    <div v-if="form.errors.transport_id" class="text-danger mt-1 small">{{
                                        form.errors.transport_id }}</div>
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

        <BulkUploadModal v-model:show="showBulkUpload" title="Bulk Upload Agents" upload-url="/agents/bulk-upload"
            template-download-url="/agents/bulk-upload/template" template-file-name="agents_bulk_upload_template.csv"
            :columns="bulkUploadColumns" />
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
import BulkUploadModal from '@/Components/BulkUploadModal.vue';

const props = defineProps({
    agents: Object,
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

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/agents');

const page = usePage();
const can = (permission) => page.props.auth.is_admin || page.props.auth.permissions.includes(permission);

const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });

const form = useForm({
    id: null,
    name: '',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    email: '',
    gst_no: '',
    transport_id: '',
});

const editMode = ref(false);
const showBulkUpload = ref(false);

const bulkUploadColumns = [
    {
        name: 'name',
        label: 'Agent Name',
        required: true,
        description: 'Unique name of the agent / agency',
    },
    {
        name: 'address',
        label: 'Address',
        required: true,
        description: 'Full address of the agent',
    },
    {
        name: 'state',
        label: 'State',
        required: true,
        description: 'State name or state code (e.g. Rajasthan or RJ)',
    },
    {
        name: 'city',
        label: 'City',
        required: true,
        description: 'Existing city name in master records (e.g. Udaipur)',
    },
    {
        name: 'mobile',
        label: 'Mobile',
        required: true,
        description: '10-digit Indian mobile number starting with 6-9',
    },
    {
        name: 'email',
        label: 'Email',
        required: false,
        description: 'Optional valid email address',
    },
    {
        name: 'gst_no',
        label: 'GST Number',
        required: false,
        description: 'Optional 15-character GSTIN',
    },
    {
        name: 'transport_id',
        label: 'Transporter ID',
        required: false,
        description: 'Optional 15-character Transporter ID',
    },
];


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
    modal = new bootstrap.Modal(document.getElementById('modal_agents'));
});

const openModal = () => {
    editMode.value = false;
    form.clearErrors();
    form.reset();
    modal.show();
};

const editItem = (agent) => {
    editMode.value = true;
    form.clearErrors();
    form.id = agent.id;
    form.name = agent.name || '';
    form.address = agent.address || '';
    form.state_id = agent.state_id || agent.state?.id || '';
    form.city_id = agent.city_id || agent.city?.id || '';
    form.mobile = agent.mobile || '';
    form.email = agent.email || '';
    form.gst_no = agent.gst_no || '';
    form.transport_id = agent.transport_id || '';
    modal.show();
};

const closeModal = () => {
    modal.hide();
};

const submit = () => {
    if (editMode.value) {
        form.put(`/agents/${form.id}`, {
            onSuccess: () => closeModal(),
        });

        return;
    }

    form.post('/agents', {
        onSuccess: () => closeModal(),
    });
};

const deleteItem = async (agent) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this agent?

Agent : ${agent.name}`
    );

    if (!remark) return;

    router.delete(`/agents/${agent.id}`, {
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

    const row = props.agents.data.find((agent) => agent.id === editId);

    if (row) {
        editItem(row);
    }

    window.history.replaceState({}, '', window.location.pathname);
});
</script>

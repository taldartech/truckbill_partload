<template>
    <div
        class="modal fade master-quick-add-modal"
        ref="modalEl"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-focus="false"
    >
        <div class="modal-dialog modal-dialog-centered" :class="{ 'modal-lg': isWideForm }">
            <div class="modal-content">
                <form @submit.prevent="submit">
                    <div class="modal-header py-2">
                        <h5 class="modal-title">
                            Add {{ title }}
                            <span v-if="nestingDepth > 0" class="text-muted small fw-normal ms-1">(nested)</span>
                        </h5>
                        <button type="button" class="btn-close" @click="close"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loading" class="text-center text-muted py-3">Loading...</div>
                        <div v-else-if="loadError" class="text-danger small">{{ loadError }}</div>
                        <template v-else>
                            <!-- Simple name masters -->
                            <div v-if="isNameOnlyMaster" class="mb-0">
                                <label class="form-label small mb-1">{{ title }} Name *</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                            </div>

                            <!-- Country -->
                            <template v-else-if="master === 'country'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Country Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small mb-1">Code</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.code" maxlength="10">
                                    <div v-if="fieldError('code')" class="text-danger small">{{ fieldError('code') }}</div>
                                </div>
                            </template>

                            <!-- State -->
                            <template v-else-if="master === 'state'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Country *</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="form.country_id"
                                        :options="lookups.countries"
                                        master="country"
                                        :quick-add-context="{ formField: 'country_id' }"
                                        placeholder="Select Country"
                                        @created="(record) => appendMasterOption(lookups.countries, record)"
                                    />
                                    <div v-if="fieldError('country_id')" class="text-danger small">{{ fieldError('country_id') }}</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">State Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small mb-1">Code</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.code" maxlength="10">
                                </div>
                            </template>

                            <!-- City -->
                            <template v-else-if="master === 'city'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">State *</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="form.state_id"
                                        :options="lookups.states"
                                        master="state"
                                        :quick-add-context="{ formField: 'state_id' }"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(lookups.states, record)"
                                    />
                                    <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small mb-1">City Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                            </template>

                            <!-- Party -->
                            <template v-else-if="master === 'party'">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Group</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.group_id"
                                            :options="lookups.groups"
                                            master="group"
                                            :quick-add-context="{ formField: 'group_id' }"
                                            placeholder="No Group"
                                            @created="(record) => appendMasterOption(lookups.groups, record)"
                                        />
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Party Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Address *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.address" required>
                                    <div v-if="fieldError('address')" class="text-danger small">{{ fieldError('address') }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">State *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.state_id"
                                            :options="lookups.states"
                                            master="state"
                                            :quick-add-context="{ formField: 'state_id' }"
                                            placeholder="Select State"
                                            @created="(record) => appendMasterOption(lookups.states, record)"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            master="city"
                                            :quick-add-context="{ state_id: form.state_id, formField: 'city_id' }"
                                            placeholder="Select City"
                                            :disabled="!form.state_id || citiesLoading"
                                            @created="(record) => appendMasterOption(cities, record)"
                                        />
                                        <div v-if="fieldError('city_id')" class="text-danger small">{{ fieldError('city_id') }}</div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Mobile *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.mobile" required maxlength="10">
                                        <div v-if="fieldError('mobile')" class="text-danger small">{{ fieldError('mobile') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">GST Number</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.gst_number">
                                        <div v-if="fieldError('gst_number')" class="text-danger small">{{ fieldError('gst_number') }}</div>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small mb-1">Email</label>
                                    <input type="email" class="form-control form-control-sm" v-model="form.email">
                                    <div v-if="fieldError('email')" class="text-danger small">{{ fieldError('email') }}</div>
                                </div>
                            </template>

                            <!-- Station -->
                            <template v-else-if="master === 'station'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Station Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">State *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.state_id"
                                            :options="lookups.states"
                                            master="state"
                                            :quick-add-context="{ formField: 'state_id' }"
                                            placeholder="Select State"
                                            @created="(record) => appendMasterOption(lookups.states, record)"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            master="city"
                                            :quick-add-context="{ state_id: form.state_id, formField: 'city_id' }"
                                            placeholder="Select City"
                                            :disabled="!form.state_id || citiesLoading"
                                            @created="(record) => appendMasterOption(cities, record)"
                                        />
                                        <div v-if="fieldError('city_id')" class="text-danger small">{{ fieldError('city_id') }}</div>
                                    </div>
                                </div>
                                <div v-if="lookups.canSetDefaultAgent" class="mb-0">
                                    <label class="form-label small mb-1">Default Agent</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="form.default_agent_id"
                                        :options="lookups.agents"
                                        master="agent"
                                        :quick-add-context="{ formField: 'default_agent_id' }"
                                        placeholder="Select Default Agent"
                                        @created="(record) => appendMasterOption(lookups.agents, record)"
                                    />
                                    <div v-if="fieldError('default_agent_id')" class="text-danger small">{{ fieldError('default_agent_id') }}</div>
                                </div>
                            </template>

                            <!-- Agent / Vendor -->
                            <template v-else-if="master === 'agent' || master === 'vendor'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">{{ title }} Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.name" required>
                                    <div v-if="fieldError('name')" class="text-danger small">{{ fieldError('name') }}</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Address *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.address" required>
                                    <div v-if="fieldError('address')" class="text-danger small">{{ fieldError('address') }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">State *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.state_id"
                                            :options="lookups.states"
                                            master="state"
                                            :quick-add-context="{ formField: 'state_id' }"
                                            placeholder="Select State"
                                            @created="(record) => appendMasterOption(lookups.states, record)"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            master="city"
                                            :quick-add-context="{ state_id: form.state_id, formField: 'city_id' }"
                                            placeholder="Select City"
                                            :disabled="!form.state_id || citiesLoading"
                                            @created="(record) => appendMasterOption(cities, record)"
                                        />
                                        <div v-if="fieldError('city_id')" class="text-danger small">{{ fieldError('city_id') }}</div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Mobile *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.mobile" required maxlength="10">
                                        <div v-if="fieldError('mobile')" class="text-danger small">{{ fieldError('mobile') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Email</label>
                                        <input type="email" class="form-control form-control-sm" v-model="form.email">
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small mb-1">GST Number</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        v-model="form[master === 'agent' ? 'gst_no' : 'gst_number']"
                                    >
                                    <div
                                        v-if="fieldError(master === 'agent' ? 'gst_no' : 'gst_number')"
                                        class="text-danger small"
                                    >
                                        {{ fieldError(master === 'agent' ? 'gst_no' : 'gst_number') }}
                                    </div>
                                </div>
                            </template>

                            <!-- Truck -->
                            <template v-else-if="master === 'truck'">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Truck Number *</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm text-uppercase"
                                        v-model="form.truck_number"
                                        required
                                        @input="form.truck_number = String($event.target.value || '').toUpperCase()"
                                    >
                                    <div v-if="fieldError('truck_number')" class="text-danger small">{{ fieldError('truck_number') }}</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Truck Type</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="form.truck_type_id"
                                        :options="lookups.truckTypes"
                                        master="truck_type"
                                        :quick-add-context="{ formField: 'truck_type_id' }"
                                        placeholder="Select Type"
                                        @created="(record) => appendMasterOption(lookups.truckTypes, record)"
                                    />
                                </div>
                                <div class="row mb-0">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Owner Name</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.owner_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Owner Mobile</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.owner_mobile" maxlength="10">
                                        <div v-if="fieldError('owner_mobile')" class="text-danger small">{{ fieldError('owner_mobile') }}</div>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" @click="close">
                            {{ nestingDepth > 0 ? 'Back' : 'Cancel' }}
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="saving || loading || !!loadError">
                            Save {{ title }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    MASTER_TITLES,
    appendMasterOption,
    useMasterQuickAdd,
} from '@/composables/useMasterQuickAdd';
import http from '@/utils/http';

const {
    isOpen,
    openRequestId,
    openSession,
    sessionStack,
    nestingDepth,
    activeMaster,
    activeContext,
    closeQuickAdd,
    closeAllQuickAdd,
    notifyCreated,
} = useMasterQuickAdd();

const modalEl = ref(null);
let modal = null;
let suppressCloseNotify = false;
let lastStackDepth = 0;

const master = ref(null);
const context = ref({});
const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const errors = ref({});
const cities = ref([]);
const citiesLoading = ref(false);

const lookups = reactive({
    countries: [],
    states: [],
    groups: [],
    truckTypes: [],
    agents: [],
    canSetDefaultAgent: false,
});

const form = reactive({});

const NAME_ONLY = ['good', 'package_type', 'truck_type', 'group'];

const title = computed(() => MASTER_TITLES[master.value] || 'Record');
const isNameOnlyMaster = computed(() => NAME_ONLY.includes(master.value));
const isWideForm = computed(() => ['party', 'agent', 'vendor'].includes(master.value));

const ENDPOINTS = {
    party: { store: '/parties/quick', formData: '/parties/quick/form-data', key: 'party' },
    station: { store: '/stations/quick', formData: '/stations/quick/form-data', key: 'station' },
    agent: { store: '/agents/quick', formData: '/agents/quick/form-data', key: 'agent' },
    vendor: { store: '/vendors/quick', formData: '/vendors/quick/form-data', key: 'vendor' },
    good: { store: '/goods/quick', key: 'good' },
    package_type: { store: '/package-types/quick', key: 'package_type' },
    truck_type: { store: '/truck-types/quick', key: 'truck_type' },
    group: { store: '/groups/quick', key: 'group' },
    truck: { store: '/trucks/quick', formData: '/trucks/quick/form-data', key: 'truck' },
    country: { store: '/countries/quick', key: 'country' },
    state: { store: '/states/quick', formData: '/states/quick/form-data', key: 'state' },
    city: { store: '/cities/quick', formData: '/cities/quick/form-data', key: 'city' },
};

const LOOKUP_KEYS_BY_MASTER = {
    country: 'countries',
    state: 'states',
    group: 'groups',
    agent: 'agents',
    truck_type: 'truckTypes',
};

const emptyFormFor = (type, ctx = {}) => {
    switch (type) {
        case 'party':
            return { group_id: '', name: '', address: '', state_id: '', city_id: '', mobile: '', email: '', gst_number: '' };
        case 'station':
            return { name: '', state_id: '', city_id: '', default_agent_id: '' };
        case 'agent':
            return { name: '', address: '', state_id: '', city_id: '', mobile: '', email: '', gst_no: '' };
        case 'vendor':
            return { name: '', address: '', state_id: '', city_id: '', mobile: '', email: '', gst_number: '' };
        case 'truck':
            return { truck_number: '', truck_type_id: '', owner_name: '', owner_mobile: '' };
        case 'country':
            return { name: '', code: '' };
        case 'state':
            return { country_id: ctx.country_id || '', name: '', code: '' };
        case 'city':
            return { state_id: ctx.state_id || '', name: '' };
        default:
            return { name: '' };
    }
};

const cloneLookups = () => ({
    countries: [...lookups.countries],
    states: [...lookups.states],
    groups: [...lookups.groups],
    truckTypes: [...lookups.truckTypes],
    agents: [...lookups.agents],
    canSetDefaultAgent: lookups.canSetDefaultAgent,
});

const captureSnapshot = () => ({
    master: master.value,
    context: { ...context.value },
    form: { ...form },
    cities: [...cities.value],
    lookups: cloneLookups(),
    errors: { ...errors.value },
    loadError: loadError.value,
});

const applySnapshot = async (snapshot) => {
    master.value = snapshot.master;
    context.value = { ...(snapshot.context || {}) };
    errors.value = { ...(snapshot.errors || {}) };
    loadError.value = snapshot.loadError || '';
    cities.value = [...(snapshot.cities || [])];

    Object.keys(form).forEach((key) => delete form[key]);
    Object.assign(form, snapshot.form || {});

    const snapLookups = snapshot.lookups || {};
    lookups.countries = [...(snapLookups.countries || [])];
    lookups.states = [...(snapLookups.states || [])];
    lookups.groups = [...(snapLookups.groups || [])];
    lookups.truckTypes = [...(snapLookups.truckTypes || [])];
    lookups.agents = [...(snapLookups.agents || [])];
    lookups.canSetDefaultAgent = !!snapLookups.canSetDefaultAgent;

    if (['party', 'station', 'agent', 'vendor'].includes(master.value) && form.state_id && cities.value.length === 0) {
        await loadCities(form.state_id);
    }
};

const applyCreatedToParentSnapshot = (record) => {
    if (sessionStack.value.length === 0 || !record) {
        return;
    }

    const parent = sessionStack.value[sessionStack.value.length - 1];
    const snapshot = parent?._snapshot;

    if (!snapshot) {
        return;
    }

    const createdMaster = openSession.value?.master;
    const formField = openSession.value?.context?.formField;

    if (createdMaster === 'city') {
        appendMasterOption(snapshot.cities, record);
    } else {
        const lookupKey = LOOKUP_KEYS_BY_MASTER[createdMaster];
        if (lookupKey && snapshot.lookups) {
            appendMasterOption(snapshot.lookups[lookupKey], record);
        }
    }

    if (formField) {
        snapshot.form[formField] = record.id;
    }

    // Changing state clears city on party/station/agent/vendor forms
    if (formField === 'state_id' && Object.prototype.hasOwnProperty.call(snapshot.form, 'city_id')) {
        snapshot.form.city_id = '';
        snapshot.cities = [];
    }
};

const resetForm = () => {
    errors.value = {};
    cities.value = [];
    Object.keys(form).forEach((key) => delete form[key]);
    Object.assign(form, emptyFormFor(master.value, context.value));
};

const fieldError = (field) => errors.value[field]?.[0] || '';

const focusFirstField = async () => {
    await nextTick();

    if (loading.value || loadError.value) {
        return;
    }

    const body = modalEl.value?.querySelector('.modal-body');
    if (!body) {
        return;
    }

    const firstControl = body.querySelector(
        'input.form-control:not([disabled]):not([type="hidden"]), .searchable-select__trigger:not([disabled])'
    );

    firstControl?.focus();
};

const loadCities = async (stateId) => {
    if (!stateId) {
        cities.value = [];
        return;
    }

    citiesLoading.value = true;
    try {
        const { data } = await http.get(`/cities/by-state/${stateId}`);
        cities.value = Array.isArray(data) ? data : [];
    } catch {
        cities.value = [];
    } finally {
        citiesLoading.value = false;
    }
};

const loadFormData = async () => {
    const config = ENDPOINTS[master.value];
    loadError.value = '';

    if (!config?.formData) {
        return;
    }

    loading.value = true;
    try {
        const { data } = await http.get(config.formData);
        lookups.countries = Array.isArray(data?.countries) ? data.countries : [];
        lookups.states = Array.isArray(data?.states) ? data.states : [];
        lookups.groups = Array.isArray(data?.groups) ? data.groups : [];
        lookups.truckTypes = Array.isArray(data?.truckTypes)
            ? data.truckTypes
            : (Array.isArray(data?.truck_types) ? data.truck_types : []);
        lookups.agents = Array.isArray(data?.agents) ? data.agents : [];
        lookups.canSetDefaultAgent = !!data?.can_set_default_agent;
    } catch (error) {
        loadError.value = error.response?.data?.message || 'Unable to load form data.';
    } finally {
        loading.value = false;
    }
};

const showModal = () => {
    suppressCloseNotify = false;
    modal?.show();
};

const close = () => {
    const stillOpen = closeQuickAdd();

    if (!stillOpen) {
        suppressCloseNotify = true;
        modal?.hide();
    }
};

const submit = async () => {
    const config = ENDPOINTS[master.value];
    if (!config) return;

    saving.value = true;
    errors.value = {};

    try {
        const payload = { ...form };
        const { data } = await http.post(config.store, payload);
        const record = data[config.key];

        applyCreatedToParentSnapshot(record);
        const stillOpen = notifyCreated(record);

        if (!stillOpen) {
            suppressCloseNotify = true;
            modal?.hide();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            loadError.value = error.response?.data?.message || 'Unable to save.';
        }
    } finally {
        saving.value = false;
    }
};

watch(() => form.state_id, async (stateId, previous) => {
    if (!['party', 'station', 'agent', 'vendor'].includes(master.value)) {
        return;
    }
    if (String(stateId) === String(previous)) return;
    form.city_id = '';
    await loadCities(stateId);
});

watch(openRequestId, async () => {
    if (!isOpen.value) {
        master.value = null;
        lastStackDepth = 0;
        return;
    }

    const nextMaster = activeMaster.value;
    const nextContext = { ...(activeContext.value || {}) };
    const stackDepth = sessionStack.value.length;

    if (!nextMaster) {
        return;
    }

    // Nesting deeper: preserve the parent form before switching
    if (stackDepth > lastStackDepth && master.value) {
        const parentSession = sessionStack.value[sessionStack.value.length - 1];
        if (parentSession) {
            parentSession._snapshot = captureSnapshot();
        }
    }

    // Returning to a parent session: restore its in-progress form
    if (stackDepth < lastStackDepth && openSession.value?._snapshot) {
        await applySnapshot(openSession.value._snapshot);
        delete openSession.value._snapshot;
        lastStackDepth = stackDepth;
        await focusFirstField();
        return;
    }

    lastStackDepth = stackDepth;
    master.value = nextMaster;
    context.value = nextContext;
    resetForm();
    await loadFormData();

    if (master.value === 'city' && form.state_id) {
        const state = lookups.states.find((s) => String(s.id) === String(form.state_id));
        if (state?.country_id) {
            form.country_id = state.country_id;
        }
    }

    showModal();
    await focusFirstField();
});

watch(loading, async (isLoading) => {
    if (!isLoading && isOpen.value) {
        await focusFirstField();
    }
});

watch(isOpen, (open) => {
    if (!open && modal) {
        suppressCloseNotify = true;
        modal.hide();
        lastStackDepth = 0;
    }
});

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && modalEl.value) {
        modal = new bootstrap.Modal(modalEl.value);
        modalEl.value.addEventListener('shown.bs.modal', () => {
            focusFirstField();
        });
        modalEl.value.addEventListener('hidden.bs.modal', () => {
            if (!suppressCloseNotify) {
                closeAllQuickAdd();
            }
            suppressCloseNotify = false;
        });
    }
});

onUnmounted(() => {
    modal?.dispose();
});
</script>

<style>
/* Above SearchableSelect dropdowns (≤ ~1090) so F3 modal is always usable */
.master-quick-add-modal {
    z-index: 2000 !important;
}

body:has(.master-quick-add-modal.show) .modal-backdrop {
    z-index: 1990 !important;
}

/* Nested open still uses one modal; bump dropdowns above it via SearchableSelect panelZIndex */
</style>

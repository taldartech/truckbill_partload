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
                        <h5 class="modal-title">Add {{ title }}</h5>
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
                                        placeholder="Select Country"
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
                                        placeholder="Select State"
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
                                            placeholder="No Group"
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
                                            placeholder="Select State"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            :placeholder="citiesLoading ? 'Loading cities...' : 'Select City'"
                                            :disabled="!form.state_id || citiesLoading"
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
                                <div class="row mb-0">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">State *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.state_id"
                                            :options="lookups.states"
                                            placeholder="Select State"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            :placeholder="citiesLoading ? 'Loading cities...' : 'Select City'"
                                            :disabled="!form.state_id || citiesLoading"
                                        />
                                        <div v-if="fieldError('city_id')" class="text-danger small">{{ fieldError('city_id') }}</div>
                                    </div>
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
                                            placeholder="Select State"
                                        />
                                        <div v-if="fieldError('state_id')" class="text-danger small">{{ fieldError('state_id') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">City *</label>
                                        <SearchableSelect
                                            :small="true"
                                            v-model="form.city_id"
                                            :options="cities"
                                            :placeholder="citiesLoading ? 'Loading cities...' : 'Select City'"
                                            :disabled="!form.state_id || citiesLoading"
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
                                        placeholder="Select Type"
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
                        <button type="button" class="btn btn-secondary btn-sm" @click="close">Cancel</button>
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
    useMasterQuickAdd,
} from '@/composables/useMasterQuickAdd';
import http from '@/utils/http';

const {
    isOpen,
    openRequestId,
    activeMaster,
    activeContext,
    closeQuickAdd,
    notifyCreated,
} = useMasterQuickAdd();

const modalEl = ref(null);
let modal = null;
let suppressCloseNotify = false;

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

const emptyFormFor = (type, ctx = {}) => {
    switch (type) {
        case 'party':
            return { group_id: '', name: '', address: '', state_id: '', city_id: '', mobile: '', email: '', gst_number: '' };
        case 'station':
            return { name: '', state_id: '', city_id: '' };
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
    suppressCloseNotify = true;
    modal?.hide();
    closeQuickAdd();
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
        notifyCreated(record);
        suppressCloseNotify = true;
        modal?.hide();
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
        return;
    }

    const nextMaster = activeMaster.value;
    const nextContext = { ...(activeContext.value || {}) };

    if (!nextMaster) {
        return;
    }

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
                closeQuickAdd();
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
</style>

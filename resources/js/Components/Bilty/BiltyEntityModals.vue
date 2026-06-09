<template>
    <div>
        <div class="modal fade" ref="viewModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title">{{ viewTitle }}</h5>
                        <button type="button" class="btn-close" @click="hideViewModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="viewLoading" class="text-center text-muted py-3">Loading...</div>
                        <div v-else-if="viewError" class="text-danger small">{{ viewError }}</div>
                        <dl v-else class="row mb-0 small">
                            <template v-for="row in viewRows" :key="row.label">
                                <dt class="col-sm-4 text-muted">{{ row.label }}</dt>
                                <dd class="col-sm-8">{{ row.value || '—' }}</dd>
                            </template>
                        </dl>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" @click="hideViewModal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" ref="partyModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form @submit.prevent="submitParty">
                        <div class="modal-header py-2">
                            <h5 class="modal-title">Add Party</h5>
                            <button type="button" class="btn-close" @click="hidePartyModal"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="partyFormLoading" class="text-center text-muted py-3">Loading...</div>
                            <div v-else-if="partyFormLoadError" class="text-danger small">{{ partyFormLoadError }}</div>
                            <template v-else>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Group</label>
                                    <select class="form-select form-select-sm" v-model="partyForm.group_id">
                                        <option value="">No Group</option>
                                        <option v-for="group in partyFormData.groups" :key="group.id" :value="group.id">
                                            {{ group.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">Party Name *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="partyForm.name" required>
                                    <div v-if="partyFieldError('name')" class="text-danger small">{{ partyFieldError('name') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Type</label>
                                    <select class="form-select form-select-sm" v-model="partyForm.type">
                                        <option value="Both">Both</option>
                                        <option value="Consignor">Consignor</option>
                                        <option value="Consignee">Consignee</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Address</label>
                                <input type="text" class="form-control form-control-sm" v-model="partyForm.address">
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">State</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="partyForm.state_id"
                                        :options="partyFormData.states"
                                        placeholder="Select State"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">City</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="partyForm.city_id"
                                        :options="partyCities"
                                        :placeholder="partyCitiesLoading ? 'Loading cities...' : 'Select City'"
                                        :disabled="!partyForm.state_id || partyCitiesLoading"
                                    />
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Mobile</label>
                                    <input type="text" class="form-control form-control-sm" v-model="partyForm.mobile">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">GST Number</label>
                                    <input type="text" class="form-control form-control-sm" v-model="partyForm.gst_number">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small mb-1">Email</label>
                                <input type="email" class="form-control form-control-sm" v-model="partyForm.email">
                                <div v-if="partyFieldError('email')" class="text-danger small">{{ partyFieldError('email') }}</div>
                            </div>
                            </template>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="hidePartyModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="partySaving || partyFormLoading || !!partyFormLoadError">Save Party</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" ref="stationModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form @submit.prevent="submitStation">
                        <div class="modal-header py-2">
                            <h5 class="modal-title">Add Station</h5>
                            <button type="button" class="btn-close" @click="hideStationModal"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="stationFormLoading" class="text-center text-muted py-3">Loading...</div>
                            <div v-else-if="stationFormLoadError" class="text-danger small">{{ stationFormLoadError }}</div>
                            <template v-else>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Station Name *</label>
                                <input type="text" class="form-control form-control-sm" v-model="stationForm.name" required>
                                <div v-if="stationFieldError('name')" class="text-danger small">{{ stationFieldError('name') }}</div>
                            </div>
                            <div class="row mb-0">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">State</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="stationForm.state_id"
                                        :options="stationFormData.states"
                                        placeholder="Select State"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">City</label>
                                    <SearchableSelect
                                        :small="true"
                                        v-model="stationForm.city_id"
                                        :options="stationCities"
                                        :placeholder="stationCitiesLoading ? 'Loading cities...' : 'Select City'"
                                        :disabled="!stationForm.state_id || stationCitiesLoading"
                                    />
                                </div>
                            </div>
                            </template>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="hideStationModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="stationSaving || stationFormLoading || !!stationFormLoadError">Save Station</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import http from '@/utils/http';

const emit = defineEmits(['party-created', 'station-created']);

const partyFormData = ref({
    loaded: false,
    groups: [],
    states: [],
});
const stationFormData = ref({
    loaded: false,
    states: [],
});
const partyCities = ref([]);
const stationCities = ref([]);
const partyCitiesLoading = ref(false);
const stationCitiesLoading = ref(false);
const partyFormLoading = ref(false);
const stationFormLoading = ref(false);
const partyFormLoadError = ref('');
const stationFormLoadError = ref('');

const viewModalEl = ref(null);
const partyModalEl = ref(null);
const stationModalEl = ref(null);
let viewModal = null;
let partyModal = null;
let stationModal = null;

const viewTitle = ref('');
const viewRows = ref([]);
const viewLoading = ref(false);
const viewError = ref('');

const partyTargetField = ref('');
const stationTargetField = ref('');
const partySaving = ref(false);
const stationSaving = ref(false);
const partyErrors = ref({});
const stationErrors = ref({});

const partyForm = reactive({
    group_id: '',
    name: '',
    type: 'Both',
    address: '',
    state_id: '',
    city_id: '',
    mobile: '',
    email: '',
    gst_number: '',
});

const stationForm = reactive({
    name: '',
    state_id: '',
    city_id: '',
});

const fetchCitiesByState = async (stateId) => {
    const { data } = await http.get(`/cities/by-state/${stateId}`);
    return data;
};

const loadPartyCities = async (stateId) => {
    if (!stateId) {
        partyCities.value = [];
        return;
    }

    partyCitiesLoading.value = true;
    try {
        partyCities.value = await fetchCitiesByState(stateId);
    } catch {
        partyCities.value = [];
    } finally {
        partyCitiesLoading.value = false;
    }
};

const loadStationCities = async (stateId) => {
    if (!stateId) {
        stationCities.value = [];
        return;
    }

    stationCitiesLoading.value = true;
    try {
        stationCities.value = await fetchCitiesByState(stateId);
    } catch {
        stationCities.value = [];
    } finally {
        stationCitiesLoading.value = false;
    }
};

watch(() => partyForm.state_id, async (stateId, previousStateId) => {
    if (String(stateId) === String(previousStateId)) return;
    partyForm.city_id = '';
    await loadPartyCities(stateId);
});

watch(() => stationForm.state_id, async (stateId, previousStateId) => {
    if (String(stateId) === String(previousStateId)) return;
    stationForm.city_id = '';
    await loadStationCities(stateId);
});

onMounted(() => {
    if (typeof bootstrap !== 'undefined') {
        viewModal = new bootstrap.Modal(viewModalEl.value);
        partyModal = new bootstrap.Modal(partyModalEl.value);
        stationModal = new bootstrap.Modal(stationModalEl.value);
    }
});

const loadPartyFormData = async () => {
    if (partyFormData.value.loaded) return;

    partyFormLoading.value = true;
    partyFormLoadError.value = '';

    try {
        const { data } = await http.get('/parties/quick/form-data');
        partyFormData.value = {
            loaded: true,
            groups: data.groups || [],
            states: data.states || [],
        };
    } catch (error) {
        partyFormLoadError.value = error.response?.data?.message || 'Unable to load party form data.';
    } finally {
        partyFormLoading.value = false;
    }
};

const loadStationFormData = async () => {
    if (stationFormData.value.loaded) return;

    stationFormLoading.value = true;
    stationFormLoadError.value = '';

    try {
        const { data } = await http.get('/stations/quick/form-data');
        stationFormData.value = {
            loaded: true,
            states: data.states || [],
        };
    } catch (error) {
        stationFormLoadError.value = error.response?.data?.message || 'Unable to load station form data.';
    } finally {
        stationFormLoading.value = false;
    }
};

const resetPartyForm = () => {
    partyErrors.value = {};
    partyCities.value = [];
    Object.assign(partyForm, {
        group_id: '',
        name: '',
        type: 'Both',
        address: '',
        state_id: '',
        city_id: '',
        mobile: '',
        email: '',
        gst_number: '',
    });
};

const resetStationForm = () => {
    stationErrors.value = {};
    stationCities.value = [];
    Object.assign(stationForm, {
        name: '',
        state_id: '',
        city_id: '',
    });
};

const partyFieldError = (field) => partyErrors.value[field]?.[0] || '';
const stationFieldError = (field) => stationErrors.value[field]?.[0] || '';

const showViewModal = async (title, url) => {
    viewTitle.value = title;
    viewRows.value = [];
    viewError.value = '';
    viewLoading.value = true;
    viewModal?.show();

    try {
        const { data } = await http.get(url);
        viewRows.value = data.rows || [];
    } catch (error) {
        viewError.value = error.response?.data?.message || 'Unable to load details.';
    } finally {
        viewLoading.value = false;
    }
};

const viewParty = (partyId) => {
    if (!partyId) return;
    showViewModal('Party Details', `/parties/${partyId}/quick`);
};

const viewStation = (stationId) => {
    if (!stationId) return;
    showViewModal('Station Details', `/stations/${stationId}/quick`);
};

const openPartyAdd = async ({ targetField = '' } = {}) => {
    partyTargetField.value = targetField;
    resetPartyForm();
    partyModal?.show();
    await loadPartyFormData();
};

const openStationAdd = async ({ targetField = '' } = {}) => {
    stationTargetField.value = targetField;
    resetStationForm();
    stationModal?.show();
    await loadStationFormData();
};

const hideViewModal = () => viewModal?.hide();
const hidePartyModal = () => partyModal?.hide();
const hideStationModal = () => stationModal?.hide();

const submitParty = async () => {
    partySaving.value = true;
    partyErrors.value = {};

    try {
        const { data } = await http.post('/parties/quick', { ...partyForm });
        emit('party-created', { party: data.party, targetField: partyTargetField.value });
        hidePartyModal();
    } catch (error) {
        if (error.response?.status === 422) {
            partyErrors.value = error.response.data.errors || {};
        }
    } finally {
        partySaving.value = false;
    }
};

const submitStation = async () => {
    stationSaving.value = true;
    stationErrors.value = {};

    try {
        const { data } = await http.post('/stations/quick', { ...stationForm });
        emit('station-created', { station: data.station, targetField: stationTargetField.value });
        hideStationModal();
    } catch (error) {
        if (error.response?.status === 422) {
            stationErrors.value = error.response.data.errors || {};
        }
    } finally {
        stationSaving.value = false;
    }
};

defineExpose({
    viewParty,
    viewStation,
    openPartyAdd,
    openStationAdd,
});
</script>

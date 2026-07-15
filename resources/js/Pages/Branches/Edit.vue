<template>
    <AdminLayout title="Edit Branch">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Branch Details</h3>
                    </div>
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="card-body">
                            <h5 class="text-primary border-bottom pb-2 mb-3">Branch Identity</h5>
                            <div v-if="$page.props.auth.is_admin" class="form-group mb-3">
                                <label for="company_id">Company *</label>
                                <select class="form-control" id="company_id" v-model="form.company_id" required>
                                    <option value="">Select Company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.company_id" class="text-danger mt-1 small">{{ form.errors.company_id }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="name">Branch Name *</label>
                                    <input type="text" class="form-control" id="name" v-model="form.name" required>
                                    <div v-if="form.errors.name" class="text-danger mt-1 small">{{ form.errors.name }}</div>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="station_id">Station *</label>
                                    <SearchableSelect
                                        id="station_id"
                                        v-model="form.station_id"
                                        :options="stationOptions"
                                        master="station"
                                        placeholder="Select Station"
                                        @created="(record) => appendMasterOption(stationOptions, record)"
                                    />
                                    <div v-if="form.errors.station_id" class="text-danger mt-1 small">{{ form.errors.station_id }}</div>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_head_office" v-model="form.is_head_office">
                                <label class="form-check-label" for="is_head_office">Head Office</label>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Location</h5>
                            <div class="form-group mb-3">
                                <label for="address">Address *</label>
                                <textarea class="form-control" id="address" v-model="form.address" rows="2" required></textarea>
                                <div v-if="form.errors.address" class="text-danger small">{{ form.errors.address }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Country *</label>
                                    <SearchableSelect
                                        v-model="form.country_id"
                                        :options="countryOptions"
                                        master="country"
                                        placeholder="Select Country"
                                        @created="(record) => appendMasterOption(countryOptions, record)"
                                    />
                                    <div v-if="form.errors.country_id" class="text-danger small">{{ form.errors.country_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>State *</label>
                                    <SearchableSelect
                                        id="state_id"
                                        v-model="form.state_id"
                                        :options="stateOptions"
                                        master="state"
                                        :quick-add-context="{ country_id: form.country_id }"
                                        placeholder="Select State"
                                        @created="(record) => appendMasterOption(stateOptions, record)"
                                    />
                                    <div v-if="form.errors.state_id" class="text-danger small">{{ form.errors.state_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
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
                                    <div v-if="form.errors.city_id" class="text-danger small">{{ form.errors.city_id }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>PIN Code *</label>
                                    <input type="text" class="form-control" v-model="form.pin_code" required>
                                    <div v-if="form.errors.pin_code" class="text-danger small">{{ form.errors.pin_code }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Contact</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Email Address *</label>
                                    <input type="email" class="form-control" v-model="form.email" required>
                                    <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Person *</label>
                                    <input type="text" class="form-control" v-model="form.contact_person" required>
                                    <div v-if="form.errors.contact_person" class="text-danger small">{{ form.errors.contact_person }}</div>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Contact Number *</label>
                                    <input type="text" class="form-control" v-model="form.contact_number" required>
                                    <div v-if="form.errors.contact_number" class="text-danger small">{{ form.errors.contact_number }}</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">GST</h5>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="has_gst" v-model="form.has_gst">
                                <label class="form-check-label" for="has_gst">Do you have GST?</label>
                            </div>
                            <div v-if="form.has_gst" class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Number *</label>
                                    <input type="text" class="form-control" v-model="form.gst_number" required>
                                    <div v-if="form.errors.gst_number" class="text-danger small">{{ form.errors.gst_number }}</div>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Type *</label>
                                    <select class="form-control" v-model="form.gst_type" required>
                                        <option value="">Select</option>
                                        <option value="Forward">Forward</option>
                                        <option value="Reverse">Reverse</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Scheme *</label>
                                    <select class="form-control" v-model="form.gst_scheme" required>
                                        <option value="">Select</option>
                                        <option value="Regular">Regular</option>
                                        <option value="Composition">Composition</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>GST Slab (%) *</label>
                                    <input type="number" step="0.01" class="form-control" v-model="form.gst_slab" required>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Bank Details</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label>Bank Name</label>
                                    <input type="text" class="form-control" v-model="form.bank_name">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>Account Number</label>
                                    <input type="text" class="form-control" v-model="form.account_number">
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label>IFSC Code</label>
                                    <input type="text" class="form-control" v-model="form.ifsc_code">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Document Initials <small class="text-muted fw-normal">(optional — leave blank for no prefix)</small></h5>
                            <div class="row">
                                <div class="col-md-3 form-group mb-3">
                                    <label>Bilty Initial</label>
                                    <input type="text" class="form-control" v-model="form.bilty_initial">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.challan_initial">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Main Challan Initial</label>
                                    <input type="text" class="form-control" v-model="form.main_challan_initial">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Invoice Initial</label>
                                    <input type="text" class="form-control" v-model="form.invoice_initial">
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label>Inward Register Initial</label>
                                    <input type="text" class="form-control" v-model="form.inward_register_initial">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Bilty Print Settings</h5>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Copy</th>
                                            <th class="text-center">Enabled</th>
                                            <th class="text-center">Show Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="copy in biltyPrintCopies" :key="copy.key">
                                            <td>{{ copy.label }}</td>
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="form.bilty_print_config.copies[copy.key].enabled"
                                                >
                                            </td>
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="form.bilty_print_config.copies[copy.key].show_amount"
                                                    :disabled="!form.bilty_print_config.copies[copy.key].enabled"
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group mb-3">
                                <label>Bilty Print Note</label>
                                <textarea
                                    class="form-control"
                                    rows="3"
                                    v-model="form.bilty_print_config.note"
                                    placeholder="Optional note printed on all bilty copies"
                                ></textarea>
                                <small class="text-muted">This note appears on every bilty copy when printing.</small>
                            </div>
                            <div class="form-check mb-3">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="ask_amount_at_print"
                                    v-model="form.bilty_print_config.ask_amount_at_print"
                                >
                                <label class="form-check-label" for="ask_amount_at_print">
                                    Ask amount at print time
                                </label>
                                <small class="text-muted d-block">
                                    When enabled, user can choose to show or hide amounts for the entire print job.
                                </small>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-3">Logo</h5>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="use_company_logo" v-model="form.use_company_logo">
                                <label class="form-check-label" for="use_company_logo">Same as company logo</label>
                            </div>
                            <div v-if="form.use_company_logo && branch.company?.company_logo" class="mb-3">
                                <img :src="branch.company.company_logo" alt="Company Logo" class="img-thumbnail" style="max-height: 80px;">
                                <small class="text-muted d-block mt-1">Using company logo</small>
                            </div>
                            <div v-if="!form.use_company_logo" class="form-group mb-3">
                                <label>Branch Logo</label>
                                <input type="file" class="form-control" @change="onLogoChange" accept="image/*">
                                <div v-if="branch.branch_logo" class="mt-2">
                                    <img :src="branch.branch_logo" alt="Current Branch Logo" class="img-thumbnail" style="max-height: 80px;">
                                    <small class="text-muted d-block mt-1">Current Branch Logo</small>
                                </div>
                                <div v-if="form.errors.branch_logo" class="text-danger small">{{ form.errors.branch_logo }}</div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/branches" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';

const props = defineProps({
    branch: Object,
    companies: Array,
    stations: Array,
    countries: Array,
    states: Array,
    cities: Array,
    defaultCountryId: [Number, String, null],
});

const stationOptions = ref([...(props.stations || [])]);
const countryOptions = ref([...(props.countries || [])]);
const stateOptions = ref([...(props.states || [])]);
const cityOptions = ref([...(props.cities || [])]);
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.countries, (v) => { countryOptions.value = [...(v || [])]; });
watch(() => props.states, (v) => { stateOptions.value = [...(v || [])]; });
watch(() => props.cities, (v) => { cityOptions.value = [...(v || [])]; });


const defaultBiltyPrintConfig = () => ({
    ask_amount_at_print: false,
    note: '',
    copies: {
        consignor: { enabled: true, show_amount: true },
        consignee: { enabled: true, show_amount: false },
        driver: { enabled: true, show_amount: false },
        office: { enabled: true, show_amount: true },
    },
});

const resolveBiltyPrintConfig = (stored) => {
    const defaults = defaultBiltyPrintConfig();

    if (!stored) {
        return defaults;
    }

    let note = stored.note ?? '';

    if (!note) {
        for (const key of Object.keys(defaults.copies)) {
            const legacyNote = stored.copies?.[key]?.note;
            if (legacyNote) {
                note = legacyNote;
                break;
            }
        }
    }

    return {
        ask_amount_at_print: !!stored.ask_amount_at_print,
        note,
        copies: Object.fromEntries(
            biltyPrintCopies.map(({ key }) => [
                key,
                {
                    enabled: stored.copies?.[key]?.enabled ?? defaults.copies[key].enabled,
                    show_amount: stored.copies?.[key]?.show_amount ?? defaults.copies[key].show_amount,
                },
            ])
        ),
    };
};

const biltyPrintCopies = [
    { key: 'consignor', label: 'Consignor Copy' },
    { key: 'consignee', label: 'Consignee Copy' },
    { key: 'driver', label: 'Driver Copy' },
    { key: 'office', label: 'Office Copy' },
];

const form = useForm({
    company_id: props.branch.company_id || '',
    station_id: props.branch.station_id || '',
    name: props.branch.name || '',
    address: props.branch.address || '',
    country_id: props.branch.country_id || props.defaultCountryId || '',
    state_id: props.branch.state_id || '',
    city_id: props.branch.city_id || '',
    pin_code: props.branch.pin_code || '',
    is_head_office: !!props.branch.is_head_office,
    has_gst: !!props.branch.has_gst,
    gst_number: props.branch.gst_number || '',
    gst_type: props.branch.gst_type || '',
    gst_scheme: props.branch.gst_scheme || '',
    gst_slab: props.branch.gst_slab || '',
    bank_name: props.branch.bank_name || '',
    account_number: props.branch.account_number || '',
    ifsc_code: props.branch.ifsc_code || '',
    contact_person: props.branch.contact_person || '',
    contact_number: props.branch.contact_number || '',
    email: props.branch.email || '',
    bilty_initial: props.branch.bilty_initial ?? '',
    challan_initial: props.branch.challan_initial ?? '',
    main_challan_initial: props.branch.main_challan_initial ?? '',
    invoice_initial: props.branch.invoice_initial ?? '',
    inward_register_initial: props.branch.inward_register_initial ?? '',
    bilty_print_config: resolveBiltyPrintConfig(props.branch.bilty_print_config),
    use_company_logo: props.branch.use_company_logo !== false,
    branch_logo: null,
});

const filteredCities = computed(() => {
    if (!form.state_id) return [];
    return cityOptions.value.filter(city => String(city.state_id) === String(form.state_id));
});

watch(() => form.state_id, () => {
    if (form.city_id) {
        const cityExists = filteredCities.value.some(city => String(city.id) === String(form.city_id));
        if (!cityExists) {
            form.city_id = '';
        }
    }
});

const onLogoChange = (event) => {
    form.branch_logo = event.target.files[0];
};

const normalizeBiltyPrintConfigPayload = (config) => ({
    ask_amount_at_print: !!config?.ask_amount_at_print,
    note: config?.note ?? '',
    copies: Object.fromEntries(
        biltyPrintCopies.map(({ key }) => [
            key,
            {
                enabled: !!config?.copies?.[key]?.enabled,
                show_amount: !!config?.copies?.[key]?.show_amount,
            },
        ])
    ),
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
        bilty_print_config: normalizeBiltyPrintConfigPayload(data.bilty_print_config),
    })).post(`/branches/${props.branch.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <AdminLayout title="Loading Report">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Route & Vehicle -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Route &amp; Vehicle</h2>
                                <p class="bilty-section__desc">Stations, truck and driver</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date</span>
                                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.date }" v-model="form.date">
                                    <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">From Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.from_station_id"
                                    :options="stationOptions"
                                    master="station"
                                    placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                                <div v-if="form.errors.from_station_id" class="text-danger small mt-1">{{ form.errors.from_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">To Station<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.to_station_id"
                                    :options="stationOptions"
                                    master="station"
                                    placeholder="Select Station"
                                    @created="(record) => appendMasterOption(stationOptions, record)"
                                />
                                <div v-if="form.errors.to_station_id" class="text-danger small mt-1">{{ form.errors.to_station_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Truck No.<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.truck_id"
                                    :options="truckOptions"
                                    master="truck"
                                    placeholder="Select Truck"
                                    @created="(record) => appendMasterOption(truckOptions, record)"
                                />
                                <div v-if="form.errors.truck_id" class="text-danger small mt-1">{{ form.errors.truck_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Driver<span class="bilty-field__req">*</span></label>
                                <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.driver_name }" v-model="form.driver_name" placeholder="Enter Driver Name">
                                <div v-if="form.errors.driver_name" class="text-danger small mt-1">{{ form.errors.driver_name }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Loaded Items -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Loaded Items</h2>
                                <p class="bilty-section__desc">Pending bilties and inward GRs, station wise</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field" style="min-width: 14rem;">
                                    <span class="bilty-id__label">Add For</span>
                                    <SearchableSelect
                                        ref="fetchStationSelect"
                                        :small="true"
                                        v-model="fetch_station_id"
                                        :options="destinationStationOptions"
                                        placeholder="Select Destination Station"
                                        open-on-enter
                                        @change="onFetchStationChange"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid" data-dynamic-add-message="Do you want to add another line?">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%" class="text-center">Sr.No.</th>
                                        <th width="18%">GR/Bilty No.<span class="text-danger">*</span></th>
                                        <th width="8%" class="text-center">Type</th>
                                        <th width="16%">Consignee</th>
                                        <th width="18%">Goods</th>
                                        <th width="12%" class="text-center">Packages</th>
                                        <th width="13%" class="text-center">Weight(Kg)</th>
                                        <th width="10%" class="text-center"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="group in stationGroups" :key="group.key">
                                    <tr v-if="group.stationName" class="table-light">
                                        <td colspan="5" class="fw-bold text-uppercase">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ group.stationName }}
                                            <span class="text-muted fw-normal small ms-1">({{ group.rows.length }})</span>
                                        </td>
                                        <td class="text-center fw-semibold">{{ group.packages }}</td>
                                        <td class="text-center fw-semibold">{{ group.weight }}</td>
                                        <td></td>
                                    </tr>
                                    <tr
                                        v-for="({ detail, index }, serial) in group.rows"
                                        :key="detail.uid"
                                        :data-detail-index="index"
                                        :data-detail-row-type="detail.row_type"
                                        class="bg-white"
                                    >
                                        <td class="text-center align-middle">{{ serial + 1 }}</td>
                                        <td>
                                            <SearchableSelect
                                                v-if="!detail.bilty_id && !detail.inward_register_detail_id"
                                                :small="true"
                                                :open-on-focus="false"
                                                v-model="detail.line_option_id"
                                                :options="lineOptionsForRow(index)"
                                                label-key="label"
                                                placeholder="Select GR/Bilty"
                                                @change="fetchLineDetails(index)"
                                            />
                                            <span v-else class="align-middle fw-semibold">
                                                {{ detail.bilty_number || detail.gr_no || biltyLabel(detail.bilty_id) || inwardLabel(detail.inward_register_detail_id) }}
                                            </span>
                                            <div v-if="form.errors['details.' + index + '.bilty_id']" class="text-danger small mt-1 text-start">{{ form.errors['details.' + index + '.bilty_id'] }}</div>
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ detail.row_type === 'bilty' ? 'Booking' : detail.row_type === 'inward' ? 'Crossing' : '--' }}
                                        </td>
                                        <td class="align-middle">{{ detail.consignee_name || '--' }}</td>
                                        <td class="align-middle text-nowrap overflow-x-auto" style="max-width: 200px; scrollbar-width: thin;">{{ detail.goods_name || '--' }}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-center bg-light" v-model="detail.no_of_packages" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-center bg-light" v-model="detail.gross_weight" readonly>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 bg-transparent" @click="removeRow(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    </template>
                                    <tr>
                                        <td colspan="8" class="bg-light p-2 text-center">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary py-0"
                                                data-dynamic-add
                                                data-dynamic-add-message="Do you want to add another line?"
                                                @click="addLineRow"
                                            >
                                                <i class="fas fa-plus"></i> Add Line
                                            </button>
                                            <div v-if="form.errors.details" class="text-danger small mt-1">{{ form.errors.details }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- 3. Summary -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">3</div>
                            <div>
                                <h2 class="bilty-section__title">Summary</h2>
                                <p class="bilty-section__desc">Totals and remarks</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--3">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Packages</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalPackages" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Total Weight</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="totalWeight" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Remarks</label>
                                <textarea class="form-control form-control-sm" rows="1" v-model="form.remark"></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { askKeyboardConfirm } from '@/composables/useKeyboardConfirm';

const fetch_station_id = ref('');
const fetchStationSelect = ref(null);
const ALL_STATIONS_VALUE = 'all';
let skipAddMorePrompt = false;

const props = defineProps({
    stations: Array,
    trucks: Array,
    bilties: Array,
    inward_details: Array,
});

const stationOptions = ref([...(props.stations || [])]);
const truckOptions = ref((props.trucks || []).map((t) => ({ ...t, name: t.name || t.truck_number })));
watch(() => props.stations, (v) => { stationOptions.value = [...(v || [])]; });
watch(() => props.trucks, (v) => { truckOptions.value = (v || []).map((t) => ({ ...t, name: t.name || t.truck_number })); });

const destinationStationOptions = computed(() => [
    { id: ALL_STATIONS_VALUE, name: 'ALL' },
    ...stationOptions.value,
]);

const inwardDetailOptions = computed(() =>
    (props.inward_details || []).map((detail) => ({
        ...detail,
        gr_no: detail.gr_no || `GR-${detail.id}`,
    }))
);

const pendingLineOptions = computed(() => {
    const biltyOptions = (props.bilties || []).map((bilty) => ({
        id: `bilty:${bilty.id}`,
        label: `${bilty.bilty_number || '—'} · Booking`,
        row_type: 'bilty',
        bilty_id: bilty.id,
        inward_register_detail_id: null,
    }));

    const inwardOptions = inwardDetailOptions.value.map((detail) => ({
        id: `inward:${detail.id}`,
        label: `${detail.gr_no} · Crossing`,
        row_type: 'inward',
        bilty_id: null,
        inward_register_detail_id: detail.id,
    }));

    return [...biltyOptions, ...inwardOptions];
});

const lineOptionsForRow = (index) => {
    const usedIds = new Set();

    form.details.forEach((detail, detailIndex) => {
        if (detailIndex === index) {
            return;
        }

        if (detail.row_type === 'bilty' && detail.bilty_id) {
            usedIds.add(`bilty:${detail.bilty_id}`);
        }

        if (detail.row_type === 'inward' && detail.inward_register_detail_id) {
            usedIds.add(`inward:${detail.inward_register_detail_id}`);
        }
    });

    return pendingLineOptions.value.filter((option) => !usedIds.has(option.id));
};

let nextRowUid = 0;

const emptyRow = () => ({
    uid: ++nextRowUid,
    row_type: '',
    line_option_id: '',
    bilty_id: '',
    bilty_number: '',
    inward_register_detail_id: '',
    gr_no: '',
    good_id: '',
    package_type_id: '',
    consignee_id: '',
    consignee_name: '',
    goods_name: '',
    to_station_id: '',
    to_station_name: '',
    no_of_packages: '',
    gross_weight: '',
});

const form = useForm({
    from_station_id: '',
    to_station_id: '',
    truck_id: '',
    driver_name: '',
    date: new Date().toISOString().substr(0, 10),
    remark: '',
    details: [emptyRow()],
});

const biltyLabel = (id) => props.bilties.find((b) => b.id === id)?.bilty_number ?? '--';
const inwardLabel = (id) => inwardDetailOptions.value.find((d) => d.id === id)?.gr_no ?? '--';

const getDetailGrid = () => document.querySelector('.table-grid');

const getDetailRowElement = (index) =>
    document.querySelector(`tr[data-detail-index="${index}"]`);

const getRowFocusAnchor = (index) => {
    const row = getDetailRowElement(index);
    if (!row) return null;
    return row.querySelector('input.searchable-select__trigger') || row.querySelector('input') || row;
};

const focusDetailRowSelect = (index) => {
    nextTick(() => {
        getDetailRowElement(index)?.querySelector('input.searchable-select__trigger')?.focus();
    });
};

const promptAddAnotherLine = (focusFrom = null) => {
    setTimeout(async () => {
        const skipContainer = getDetailGrid();
        const from = focusFrom ?? getFetchStationTrigger() ?? document.activeElement;

        const yes = await askKeyboardConfirm('Do you want to add another line?', {
            from,
            skipContainer,
        });

        if (yes) {
            addLineRow();
            focusDetailRowSelect(form.details.length - 1);
        }
    }, 100);
};

const populateBiltyRow = (detail, bilty) => {
    let sumPkg = 0;
    let sumWt = 0;
    let goodId = '';
    let packageTypeId = '';
    let goodsName = '--';

    if (bilty.bilty_details && bilty.bilty_details.length > 0) {
        const first = bilty.bilty_details[0];
        goodId = first.good_id || '';
        packageTypeId = first.package_type_id || '';
        goodsName = [...new Set(bilty.bilty_details.map((d) => d.good?.name).filter(Boolean))].join(', ') || '--';

        bilty.bilty_details.forEach((d) => {
            sumPkg += parseFloat(d.no_of_packages || 0);
            sumWt += parseFloat(d.gross_weight || 0);
        });
    }

    detail.row_type = 'bilty';
    detail.line_option_id = `bilty:${bilty.id}`;
    detail.bilty_id = bilty.id;
    detail.bilty_number = bilty.bilty_number;
    detail.inward_register_detail_id = '';
    detail.gr_no = '';
    detail.good_id = goodId;
    detail.package_type_id = packageTypeId;
    detail.consignee_id = bilty.consignee_id || '';
    detail.consignee_name = bilty.consignee?.name || 'Unknown';
    detail.goods_name = goodsName;
    detail.to_station_id = bilty.to_station_id || '';
    detail.to_station_name = bilty.to_station?.name || '';
    detail.no_of_packages = sumPkg;
    detail.gross_weight = sumWt.toFixed(2);
};

const populateInwardRow = (detail, inward) => {
    detail.row_type = 'inward';
    detail.line_option_id = `inward:${inward.id}`;
    detail.bilty_id = '';
    detail.bilty_number = '';
    detail.inward_register_detail_id = inward.id;
    detail.gr_no = inward.gr_no || `GR-${inward.id}`;
    detail.good_id = inward.good_id || '';
    detail.package_type_id = '';
    detail.consignee_id = inward.consignee_id || '';
    detail.consignee_name = inward.consignee?.name || 'Unknown';
    detail.goods_name = inward.good?.name || '--';
    detail.to_station_id = inward.to_station_id || '';
    detail.to_station_name = inward.to_station?.name || '';
    detail.no_of_packages = inward.no_of_packages || 0;
    detail.gross_weight = parseFloat(inward.weight || 0).toFixed(2);
};

const fetchLineDetails = (index) => {
    const detail = form.details[index];
    const optionId = detail.line_option_id;

    if (!optionId) {
        Object.assign(detail, emptyRow());
        return;
    }

    const option = pendingLineOptions.value.find((line) => line.id === optionId);

    if (!option) {
        Object.assign(detail, emptyRow());
        return;
    }

    if (option.row_type === 'bilty') {
        const bilty = props.bilties.find((b) => b.id === option.bilty_id);
        if (bilty) {
            populateBiltyRow(detail, bilty);
            if (!skipAddMorePrompt) {
                promptAddAnotherLine(getRowFocusAnchor(index));
            }
        }
        return;
    }

    const inward = inwardDetailOptions.value.find((d) => d.id === option.inward_register_detail_id);
    if (inward) {
        populateInwardRow(detail, inward);
        if (!skipAddMorePrompt) {
            promptAddAnotherLine(getRowFocusAnchor(index));
        }
    }
};

const fetchByStation = () => {
    if (!fetch_station_id.value) return false;

    const stationId = fetch_station_id.value;
    const isAll = stationId === ALL_STATIONS_VALUE;

    const existingBiltyIds = form.details
        .filter((d) => d.row_type === 'bilty' && d.bilty_id)
        .map((d) => d.bilty_id);

    const existingInwardIds = form.details
        .filter((d) => d.row_type === 'inward' && d.inward_register_detail_id)
        .map((d) => d.inward_register_detail_id);

    const matchingBilties = isAll
        ? props.bilties
        : props.bilties.filter((b) => b.to_station_id === stationId);

    const matchingInward = isAll
        ? inwardDetailOptions.value
        : inwardDetailOptions.value.filter((d) => d.to_station_id === stationId);

    if (matchingBilties.length === 0 && matchingInward.length === 0) {
        alert(isAll
            ? 'No pending bilties or inward entries found.'
            : 'No pending bilties or inward entries found for this station.');
        fetch_station_id.value = '';
        return false;
    }

    const biltiesToAdd = matchingBilties.filter((b) => !existingBiltyIds.includes(b.id));
    const inwardToAdd = matchingInward.filter((d) => !existingInwardIds.includes(d.id));

    if (biltiesToAdd.length === 0 && inwardToAdd.length === 0) {
        alert('All pending entries for this selection are already in the grid.');
        fetch_station_id.value = '';
        if (form.details.length === 0) addLineRow();
        return false;
    }

    if (form.details.length === 1 && !form.details[0].bilty_id && !form.details[0].inward_register_detail_id) {
        form.details.splice(0, 1);
    }

    skipAddMorePrompt = true;

    biltiesToAdd.forEach((bilty) => {
        const row = emptyRow();
        populateBiltyRow(row, bilty);
        form.details.push(row);
    });

    inwardToAdd.forEach((inward) => {
        const row = emptyRow();
        populateInwardRow(row, inward);
        form.details.push(row);
    });

    skipAddMorePrompt = false;
    return true;
};

const getFetchStationTrigger = () =>
    fetchStationSelect.value?.$el?.querySelector('input.searchable-select__trigger');

const refocusFetchStationSelect = () => {
    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }

    nextTick(() => {
        fetchStationSelect.value?.focusOpen?.();
    });
};

const onFetchStationChange = () => {
    if (!fetch_station_id.value) return;

    const success = fetchByStation();
    if (success) {
        nextTick(() => {
            fetchStationSelect.value?.focusTrigger?.();
        });
        if (fetch_station_id.value === 'all') return;
        setTimeout(async () => {
            const yes = await askKeyboardConfirm('Do you want to add lines for another station?', {
                from: getFetchStationTrigger(),
            });
            if (yes) {
                fetch_station_id.value = '';
                refocusFetchStationSelect();
            } else {
                fetch_station_id.value = '';
            }
        }, 100);
    }
};

const addLineRow = () => {
    form.details.push(emptyRow());
};

const removeRow = (index) => {
    form.details.splice(index, 1);
    if (form.details.length === 0) {
        form.details.push(emptyRow());
    }
};

const totalPackages = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.no_of_packages || 0), 0);
});

const totalWeight = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.gross_weight || 0), 0).toFixed(2);
});

const isFilledRow = (row) => Boolean(row.bilty_id || row.inward_register_detail_id);

// Unselected rows must render last: the layout's Enter handler treats the last DOM row as the "add more" row.
const stationGroups = computed(() => {
    const groups = new Map();
    const pendingRows = [];

    form.details.forEach((detail, index) => {
        if (!isFilledRow(detail)) {
            pendingRows.push({ detail, index });
            return;
        }

        const key = `station:${detail.to_station_id || 0}`;
        if (!groups.has(key)) {
            groups.set(key, { key, stationName: detail.to_station_name || 'No Station', rows: [] });
        }
        groups.get(key).rows.push({ detail, index });
    });

    const stationList = [...groups.values()]
        .sort((a, b) => a.stationName.localeCompare(b.stationName))
        .map((group) => ({
            ...group,
            packages: group.rows.reduce((sum, { detail }) => sum + parseFloat(detail.no_of_packages || 0), 0),
            weight: group.rows.reduce((sum, { detail }) => sum + parseFloat(detail.gross_weight || 0), 0).toFixed(2),
        }));

    if (pendingRows.length > 0) {
        stationList.push({ key: 'pending', stationName: '', rows: pendingRows });
    }

    return stationList;
});

const requiredHeaderFields = {
    date: 'The date field is required.',
    from_station_id: 'The from station field is required.',
    to_station_id: 'The to station field is required.',
    truck_id: 'The truck field is required.',
    driver_name: 'The driver field is required.',
};

const validateForPrint = () => {
    form.clearErrors();

    Object.entries(requiredHeaderFields).forEach(([field, message]) => {
        if (!String(form[field] ?? '').trim()) {
            form.setError(field, message);
        }
    });

    if (!form.details.some(isFilledRow)) {
        form.setError('details', 'Add at least one bilty or inward GR.');
    }

    return !form.hasErrors;
};

const submit = () => {
    if (validateForPrint()) {
        openLoadingReportPrint();
    }
};

const printFields = () => {
    const fields = {
        _token: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        date: form.date,
        from_station_id: form.from_station_id,
        to_station_id: form.to_station_id,
        truck_id: form.truck_id,
        driver_name: form.driver_name,
        remark: form.remark ?? '',
    };

    form.details.filter(isFilledRow).forEach((row, index) => {
        fields[`details[${index}][bilty_id]`] = row.row_type === 'bilty' ? row.bilty_id : '';
        fields[`details[${index}][inward_register_detail_id]`] = row.row_type === 'inward' ? row.inward_register_detail_id : '';
    });

    return fields;
};

const openLoadingReportPrint = () => {
    const printForm = document.createElement('form');
    printForm.method = 'POST';
    printForm.action = '/loading-reports/print';
    printForm.target = '_blank';
    printForm.style.display = 'none';

    Object.entries(printFields()).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        printForm.appendChild(input);
    });

    document.body.appendChild(printForm);
    printForm.submit();
    printForm.remove();
};
</script>

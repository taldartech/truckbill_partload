<template>
    <div
        ref="rootElement"
        class="dashboard-date-range"
        :class="{
            'dashboard-date-range--open': isOpen,
            'dashboard-date-range--field': panelAlign === 'start',
        }"
    >
        <label v-if="label" class="dashboard-date-range__label">{{ label }}</label>

        <button
            type="button"
            class="dashboard-date-range__trigger"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            :disabled="disabled"
            @click="toggleDropdown"
        >
            <i class="fas fa-calendar-days dashboard-date-range__icon" aria-hidden="true"></i>
            <span class="dashboard-date-range__value">{{ appliedRangeLabel }}</span>
            <i
                class="fas fa-chevron-down dashboard-date-range__chevron"
                :class="{ 'dashboard-date-range__chevron--open': isOpen }"
                aria-hidden="true"
            ></i>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="panelRef"
                class="dashboard-date-range__panel dashboard-date-range__panel--fixed"
                :class="{
                    'dashboard-date-range__panel--custom': draftPreset === 'custom',
                    'dashboard-date-range__panel--align-start': panelAlign === 'start',
                }"
                :style="panelStyle"
            >
                <div class="dashboard-date-range__presets" role="listbox" aria-label="Date range presets">
                    <button
                        v-for="preset in selectablePresets"
                        :key="preset.key"
                        type="button"
                        class="dashboard-date-range__preset"
                        :class="{ 'dashboard-date-range__preset--active': draftPreset === preset.key }"
                        role="option"
                        :aria-selected="draftPreset === preset.key"
                        @click="selectPreset(preset.key)"
                    >
                        {{ preset.label }}
                    </button>

                    <button
                        type="button"
                        class="dashboard-date-range__preset"
                        :class="{ 'dashboard-date-range__preset--active': draftPreset === 'custom' }"
                        role="option"
                        :aria-selected="draftPreset === 'custom'"
                        @click="selectPreset('custom')"
                    >
                        Custom Range
                    </button>
                </div>

                <div v-if="draftPreset === 'custom'" class="dashboard-date-range__custom">
                    <div>
                        <label class="form-label small text-muted mb-1">From</label>
                        <input
                            v-model="draftFromDate"
                            type="date"
                            class="form-control form-control-sm"
                            @change="onCustomDateChange"
                        >
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">To</label>
                        <input
                            v-model="draftToDate"
                            type="date"
                            class="form-control form-control-sm"
                            :min="draftFromDate || undefined"
                            @change="onCustomDateChange"
                        >
                    </div>
                </div>

                <div v-if="rangeError" class="text-danger small mt-2 mb-0">
                    {{ rangeError }}
                </div>

                <div v-if="draftPreset === 'custom'" class="dashboard-date-range__actions">
                    <button type="button" class="btn btn-sm btn-brand-primary px-4" @click="applyDraft">
                        Apply
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4" @click="cancelDraft">
                        Cancel
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    BILTY_LIST_DATE_PRESET_KEYS,
    DATE_RANGE_PRESETS,
    detectDateRangePreset,
    formatDateRangeLabel,
    resolveDateRangePreset,
    validateDateRange,
} from '@/utils/dateRangePresets';

const props = defineProps({
    fromDate: {
        type: String,
        required: true,
    },
    toDate: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Date',
    },
    presetKeys: {
        type: Array,
        default: () => BILTY_LIST_DATE_PRESET_KEYS,
    },
    panelAlign: {
        type: String,
        default: 'end',
        validator: (value) => ['start', 'end'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:fromDate', 'update:toDate', 'change']);

const rootElement = ref(null);
const panelRef = ref(null);
const isOpen = ref(false);
const panelStyle = ref({});

const PANEL_WIDTH = 256;
const PANEL_WIDTH_CUSTOM = 320;

const appliedFromDate = ref(props.fromDate);
const appliedToDate = ref(props.toDate);
const draftFromDate = ref(props.fromDate);
const draftToDate = ref(props.toDate);
const draftPreset = ref(detectDateRangePreset(props.fromDate, props.toDate));
const rangeError = ref('');

const selectablePresets = computed(() => DATE_RANGE_PRESETS.filter(
    (preset) => preset.key !== 'custom' && props.presetKeys.includes(preset.key),
));

const appliedRangeLabel = computed(() => {
    const presetKey = detectDateRangePreset(appliedFromDate.value, appliedToDate.value);
    const preset = DATE_RANGE_PRESETS.find((item) => item.key === presetKey);

    if (preset && preset.key !== 'custom') {
        return preset.label;
    }

    return formatDateRangeLabel(appliedFromDate.value, appliedToDate.value);
});

watch(
    () => [props.fromDate, props.toDate],
    ([fromDate, toDate]) => {
        appliedFromDate.value = fromDate;
        appliedToDate.value = toDate;

        if (!isOpen.value) {
            resetDraftFromApplied();
        }
    },
);

function resetDraftFromApplied() {
    draftFromDate.value = appliedFromDate.value;
    draftToDate.value = appliedToDate.value;
    draftPreset.value = detectDateRangePreset(appliedFromDate.value, appliedToDate.value);
    rangeError.value = '';
}

function panelWidth() {
    return draftPreset.value === 'custom' ? PANEL_WIDTH_CUSTOM : PANEL_WIDTH;
}

function updatePanelPosition() {
    const trigger = rootElement.value;

    if (!trigger || !isOpen.value) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const width = panelWidth();
    const viewportPadding = 8;
    let left = props.panelAlign === 'start' ? rect.left : rect.right - width;

    left = Math.max(viewportPadding, Math.min(left, window.innerWidth - width - viewportPadding));

    panelStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        left: `${left}px`,
        width: `${width}px`,
        zIndex: '2100',
    };
}

function closeDropdown() {
    isOpen.value = false;
}

function openDropdown() {
    if (props.disabled) {
        return;
    }

    resetDraftFromApplied();
    isOpen.value = true;

    nextTick(() => {
        updatePanelPosition();
    });
}

function toggleDropdown() {
    if (isOpen.value) {
        cancelDraft();

        return;
    }

    openDropdown();
}

function selectPreset(presetKey) {
    draftPreset.value = presetKey;
    rangeError.value = '';

    if (presetKey === 'custom') {
        nextTick(() => updatePanelPosition());

        return;
    }

    const range = resolveDateRangePreset(presetKey);
    draftFromDate.value = range.from_date;
    draftToDate.value = range.to_date;
    applyDraft();
}

function onCustomDateChange() {
    draftPreset.value = 'custom';
    rangeError.value = '';
}

function applyDraft() {
    const error = validateDateRange(draftFromDate.value, draftToDate.value);
    rangeError.value = error;

    if (error) {
        return;
    }

    appliedFromDate.value = draftFromDate.value;
    appliedToDate.value = draftToDate.value;
    draftPreset.value = detectDateRangePreset(draftFromDate.value, draftToDate.value);

    emit('update:fromDate', appliedFromDate.value);
    emit('update:toDate', appliedToDate.value);
    emit('change', {
        from_date: appliedFromDate.value,
        to_date: appliedToDate.value,
    });

    closeDropdown();
}

function cancelDraft() {
    resetDraftFromApplied();
    closeDropdown();
}

function onDocumentClick(event) {
    if (!isOpen.value) {
        return;
    }

    const target = event.target;

    if (rootElement.value?.contains(target) || panelRef.value?.contains(target)) {
        return;
    }

    cancelDraft();
}

function onDocumentKeydown(event) {
    if (event.key === 'Escape' && isOpen.value) {
        cancelDraft();
    }
}

function onViewportChange() {
    if (isOpen.value) {
        updatePanelPosition();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onDocumentKeydown);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});

watch(draftPreset, () => {
    if (isOpen.value) {
        nextTick(() => updatePanelPosition());
    }
});
</script>

<style scoped>
.dashboard-date-range {
    position: relative;
    width: 100%;
}

.dashboard-date-range__label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
}

.dashboard-date-range__trigger {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    width: 100%;
    min-height: 36px;
    padding: 0.35rem 0.75rem;
    border: 1px solid #dbe3ef;
    border-radius: 0.375rem;
    background: #fff;
    color: var(--form-input-color, #1f2937);
    text-align: left;
}

.dashboard-date-range__trigger:hover,
.dashboard-date-range__trigger:focus {
    border-color: #94a3b8;
    outline: none;
}

.dashboard-date-range__trigger:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.dashboard-date-range__icon {
    color: var(--brand-primary, #2fb36f);
    font-size: 0.95rem;
}

.dashboard-date-range__value {
    flex: 1;
    min-width: 0;
    font-size: 0.875rem;
    font-weight: var(--form-input-weight, 500);
    color: var(--form-input-color, #1f2937);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-date-range__chevron {
    color: #64748b;
    font-size: 0.75rem;
    transition: transform 0.15s ease;
}

.dashboard-date-range__chevron--open {
    transform: rotate(180deg);
}
</style>

<style>
.dashboard-date-range__panel {
    padding: 0.75rem;
    border: 1px solid #dbe3ef;
    border-radius: 0.65rem;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    box-sizing: border-box;
}

.dashboard-date-range__panel--fixed {
    z-index: 2100;
}

.dashboard-date-range__presets {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.dashboard-date-range__preset {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border: 0;
    border-radius: 0.45rem;
    background: #f8fafc;
    color: var(--brand-primary, #2fb36f);
    font-size: 0.88rem;
    font-weight: 500;
    text-align: left;
}

.dashboard-date-range__preset:hover {
    background: var(--brand-active-bg, #e8f5ee);
}

.dashboard-date-range__preset--active {
    background: var(--brand-primary, #2fb36f);
    color: #fff;
}

.dashboard-date-range__custom {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 0.65rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e2e8f0;
}

.dashboard-date-range__custom > div {
    min-width: 0;
}

.dashboard-date-range__actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.85rem;
}
</style>

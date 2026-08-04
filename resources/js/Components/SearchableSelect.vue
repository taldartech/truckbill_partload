<template>
    <div class="searchable-select ss" ref="rootEl">
        <input
            :id="id"
            ref="inputEl"
            type="text"
            class="searchable-select__trigger ss__trigger form-select"
            :class="{
                'is-invalid': error,
                'form-select-sm': small,
                'bg-light text-muted': disabled,
                'text-muted': !isOpen && !hasSelection,
            }"
            :value="inputText"
            :placeholder="placeholder"
            :disabled="disabled"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click.stop="open"
            @focus="onFocus"
            @input="onInput"
            @keydown="onKeydown"
        >

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="panelEl"
                class="searchable-select__dropdown ss__panel"
                :style="panelStyle"
                @mousedown.prevent
            >
                <div
                    ref="listEl"
                    class="ss__list"
                    role="listbox"
                    @wheel.stop
                >
                    <div v-if="visibleOptions.length === 0" class="ss__empty">
                        No results found
                    </div>

                    <div
                        v-for="(option, index) in visibleOptions"
                        :key="optionKey(option, index)"
                        role="option"
                        class="ss__option"
                        :class="{
                            'is-active': index === activeIndex,
                            'is-selected': isSelected(option) && index !== activeIndex,
                            'is-placeholder': option.__isPlaceholder,
                        }"
                        :aria-selected="isSelected(option)"
                        @mousedown.prevent="choose(option)"
                        @mouseenter="onOptionMouseEnter(index)"
                    >
                        {{ option[labelKey] }}
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, toValue, watch } from 'vue';
import { useMasterQuickAdd } from '@/composables/useMasterQuickAdd';
import {
    claimSearchableSelect,
    releaseSearchableSelect,
} from '@/utils/searchableSelectRegistry';

const props = defineProps({
    id: { type: String, default: undefined },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select an option' },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'name' },
    error: { type: Boolean, default: false },
    small: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    openOnEnter: { type: Boolean, default: false },
    openOnFocus: { type: Boolean, default: true },
    master: { type: String, default: null },
    canCreate: { type: Boolean, default: true },
    quickAddContext: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue', 'change', 'created']);

const { register, unregister, clearActive } = useMasterQuickAdd();
const fieldToken = Symbol('searchable-select');

const rootEl = ref(null);
const inputEl = ref(null);
const panelEl = ref(null);
const listEl = ref(null);

const isOpen = ref(false);
const inputText = ref('');
const activeIndex = ref(-1);
const panelStyle = ref({});

let suppressFocusOpen = false;
let isTyping = false;
let ignoreMouseHighlightUntil = 0;

const selectedItem = computed(() => (
    props.options.find((opt) => String(opt[props.valueKey]) === String(props.modelValue)) || null
));

const hasSelection = computed(() => selectedItem.value !== null);
const selectedLabel = computed(() => selectedItem.value?.[props.labelKey] ?? '');

const syncClosedInputText = () => {
    if (!isOpen.value && !isTyping) {
        inputText.value = selectedLabel.value;
    }
};

const filteredOptions = computed(() => {
    const needle = inputText.value.trim().toLowerCase();

    if (!needle || (!isTyping && selectedLabel.value && inputText.value === selectedLabel.value)) {
        return props.options;
    }

    return props.options.filter((opt) => {
        const label = opt[props.labelKey] ? String(opt[props.labelKey]) : '';

        return label.toLowerCase().includes(needle);
    });
});

const visibleOptions = computed(() => {
    const showingAll = !isTyping
        || !inputText.value.trim()
        || inputText.value === selectedLabel.value;

    if (!showingAll) {
        return filteredOptions.value;
    }

    return [
        {
            [props.valueKey]: '',
            [props.labelKey]: props.placeholder,
            __isPlaceholder: true,
        },
        ...filteredOptions.value,
    ];
});

const optionKey = (option, index) => (
    option.__isPlaceholder ? `__placeholder__-${index}` : String(option[props.valueKey])
);

const isSelected = (option) => {
    if (option.__isPlaceholder) {
        return !hasSelection.value;
    }

    return String(props.modelValue) === String(option[props.valueKey]);
};

const selectedVisibleIndex = () => visibleOptions.value.findIndex((option) => isSelected(option));

const syncMasterRegistration = () => {
    if (!props.master || props.disabled || !props.canCreate) {
        // Clear stale F3 target when this control is focused (another field's master
        // would otherwise remain active and open the wrong quick-add form).
        if (document.activeElement === inputEl.value) {
            clearActive();
        } else {
            unregister({ master: props.master, token: fieldToken });
        }

        return;
    }

    register({
        master: props.master,
        token: fieldToken,
        canCreate: props.canCreate,
        context: toValue(props.quickAddContext) || {},
        focusTrigger: () => focusTrigger(),
        onCreated: (record) => {
            emit('created', record);

            if (record && record[props.valueKey] !== undefined && record[props.valueKey] !== null) {
                emit('update:modelValue', record[props.valueKey]);
                emit('change', record[props.valueKey]);
            }
        },
    });
};

const panelZIndex = () => {
    const modal = inputEl.value?.closest('.modal');

    if (!modal) {
        return 1080;
    }

    const z = Number.parseInt(window.getComputedStyle(modal).zIndex, 10);

    return (Number.isFinite(z) ? z : 1055) + 10;
};

const positionPanel = () => {
    if (!inputEl.value) {
        return;
    }

    const rect = inputEl.value.getBoundingClientRect();
    const preferred = 280;
    const gap = 4;
    const spaceBelow = window.innerHeight - rect.bottom - gap;
    const spaceAbove = rect.top - gap;
    const openUp = spaceBelow < 160 && spaceAbove > spaceBelow;
    const listMax = Math.min(preferred, Math.max(openUp ? spaceAbove : spaceBelow, 120));

    panelStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 160)}px`,
        zIndex: panelZIndex(),
        top: openUp ? 'auto' : `${rect.bottom + gap}px`,
        bottom: openUp ? `${window.innerHeight - rect.top + gap}px` : 'auto',
        '--ss-list-max-height': `${listMax}px`,
    };
};

const optionElements = () => {
    const list = listEl.value;

    if (!list) {
        return [];
    }

    return Array.from(list.querySelectorAll('.ss__option'));
};

/**
 * Scroll only the options list so the active row stays in view.
 * Uses scrollTop (not scrollIntoView) so the page/panel never jump.
 */
const ensureActiveVisible = async () => {
    await nextTick();

    const list = listEl.value;

    if (!list || activeIndex.value < 0) {
        return;
    }

    const item = optionElements()[activeIndex.value];

    if (!item) {
        return;
    }

    // Keep list as offsetParent for option rows.
    const itemTop = item.offsetTop;
    const itemBottom = itemTop + item.offsetHeight;
    const viewTop = list.scrollTop;
    const viewBottom = viewTop + list.clientHeight;

    if (itemTop < viewTop) {
        list.scrollTop = itemTop;
    } else if (itemBottom > viewBottom) {
        list.scrollTop = itemBottom - list.clientHeight;
    }
};

const onOptionMouseEnter = (index) => {
    if (Date.now() < ignoreMouseHighlightUntil) {
        return;
    }

    activeIndex.value = index;
};

const setActiveToSelectedOrFirst = () => {
    const selected = selectedVisibleIndex();
    activeIndex.value = selected >= 0 ? selected : (visibleOptions.value.length ? 0 : -1);
};

const selectController = {
    closeFromOutside: () => {
        if (!isOpen.value) {
            return;
        }

        isOpen.value = false;
        activeIndex.value = -1;
        isTyping = false;
        inputText.value = selectedLabel.value;
    },
};

const open = async ({ selectAll = true, preserveQuery = false } = {}) => {
    if (props.disabled || isOpen.value) {
        return;
    }

    claimSearchableSelect(selectController);

    if (!preserveQuery) {
        isTyping = false;
        inputText.value = selectedLabel.value;
    }

    isOpen.value = true;
    setActiveToSelectedOrFirst();
    positionPanel();

    await nextTick();

    if (selectAll && !preserveQuery && inputEl.value && inputText.value) {
        inputEl.value.select();
    }

    await ensureActiveVisible();
};

const close = () => {
    if (!isOpen.value) {
        return;
    }

    isOpen.value = false;
    activeIndex.value = -1;
    isTyping = false;
    inputText.value = selectedLabel.value;
    releaseSearchableSelect(selectController);
};

const choose = (option) => {
    if (option.__isPlaceholder) {
        emit('update:modelValue', '');
        emit('change', '');
        inputText.value = '';
    } else {
        emit('update:modelValue', option[props.valueKey]);
        emit('change', option[props.valueKey]);
        inputText.value = option[props.labelKey] ?? '';
    }

    isTyping = false;
    isOpen.value = false;
    activeIndex.value = -1;
    releaseSearchableSelect(selectController);

    suppressFocusOpen = true;
    nextTick(() => inputEl.value?.focus({ preventScroll: true }));
};

const chooseActive = () => {
    const option = visibleOptions.value[activeIndex.value];

    if (option) {
        choose(option);
    }
};

const moveActive = async (delta) => {
    const total = visibleOptions.value.length;

    if (!total) {
        return;
    }

    if (activeIndex.value < 0) {
        activeIndex.value = delta > 0 ? 0 : total - 1;
    } else {
        activeIndex.value = (activeIndex.value + delta + total) % total;
    }

    // While keyboard-scrolling, ignore mouseenter from items sliding under the cursor.
    ignoreMouseHighlightUntil = Date.now() + 150;
    await ensureActiveVisible();
};

const onFocus = () => {
    syncMasterRegistration();

    if (props.disabled || !props.openOnFocus) {
        return;
    }

    if (suppressFocusOpen) {
        suppressFocusOpen = false;

        return;
    }

    open();
};

const onInput = (event) => {
    isTyping = true;
    inputText.value = event.target.value;

    if (!isOpen.value) {
        open({ selectAll: false, preserveQuery: true });
    } else {
        setActiveToSelectedOrFirst();
        ensureActiveVisible();
        positionPanel();
    }
};

const onKeydown = (event) => {
    if (props.disabled) {
        return;
    }

    if (event.key === 'F3' && props.master && props.canCreate) {
        syncMasterRegistration();

        return;
    }

    if (event.key === 'Tab') {
        close();

        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        close();

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        event.stopPropagation();

        if (!isOpen.value) {
            open({ selectAll: false });
        } else {
            moveActive(1);
        }

        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        event.stopPropagation();

        if (!isOpen.value) {
            open({ selectAll: false });
        } else {
            moveActive(-1);
        }

        return;
    }

    if (event.key === 'Enter') {
        if (isOpen.value) {
            event.preventDefault();
            event.stopPropagation();

            if (activeIndex.value >= 0) {
                chooseActive();
            } else if (filteredOptions.value.length === 1) {
                choose(filteredOptions.value[0]);
            }

            return;
        }

        if (props.openOnEnter) {
            event.preventDefault();
            event.stopPropagation();
            open();
        }
    }
};

const onDocumentPointerDown = (event) => {
    if (rootEl.value?.contains(event.target) || panelEl.value?.contains(event.target)) {
        return;
    }

    close();
};

watch(() => props.modelValue, () => {
    syncClosedInputText();
});

watch(() => props.options, () => {
    syncClosedInputText();
}, { deep: true });

const onWindowScroll = (event) => {
    // List scrolling should not reposition (or fight) the panel.
    if (panelEl.value && event.target instanceof Node && panelEl.value.contains(event.target)) {
        return;
    }

    positionPanel();
};

watch(isOpen, (open) => {
    if (open) {
        syncMasterRegistration();
        window.addEventListener('scroll', onWindowScroll, true);
        window.addEventListener('resize', positionPanel);
    } else {
        window.removeEventListener('scroll', onWindowScroll, true);
        window.removeEventListener('resize', positionPanel);
    }
});

watch(
    () => [props.master, props.canCreate, props.disabled, JSON.stringify(props.quickAddContext || {})],
    () => {
        if (document.activeElement === inputEl.value || isOpen.value) {
            syncMasterRegistration();
        }
    },
);

onMounted(() => {
    inputText.value = selectedLabel.value;
    document.addEventListener('mousedown', onDocumentPointerDown);
});

onUnmounted(() => {
    unregister({ master: props.master, token: fieldToken });
    releaseSearchableSelect(selectController);
    document.removeEventListener('mousedown', onDocumentPointerDown);
    window.removeEventListener('scroll', onWindowScroll, true);
    window.removeEventListener('resize', positionPanel);
});

const focusOpen = () => {
    if (props.disabled) {
        return;
    }

    inputEl.value?.focus({ preventScroll: true });
    open();
};

const focusTrigger = () => {
    if (!inputEl.value || props.disabled) {
        return;
    }

    suppressFocusOpen = true;
    inputEl.value.focus({ preventScroll: true });
};

defineExpose({ focusOpen, focusTrigger });
</script>

<style scoped>
.searchable-select,
.ss {
    position: relative;
    width: 100%;
}

.searchable-select__trigger,
.ss__trigger {
    width: 100%;
    cursor: text;
    background-color: #fff;
    border: 1px solid var(--brand-border);
    font-family: var(--app-font-family);
    font-size: var(--app-font-size-input);
    font-weight: var(--form-input-weight);
    color: var(--form-input-color);
}

.searchable-select__trigger:disabled,
.ss__trigger:disabled {
    cursor: not-allowed;
}

.searchable-select__trigger:focus,
.ss__trigger:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 0.25rem var(--brand-input-focus-shadow);
}
</style>

<style>
.ss__panel {
    display: block;
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    overflow: hidden;
    border: 1px solid var(--brand-border);
    border-radius: 0.375rem;
    background: #fff;
    box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, 0.12);
}

.ss__list {
    position: relative;
    max-height: var(--ss-list-max-height, 220px);
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.ss__empty {
    padding: 0.5rem;
    color: #6c757d;
    font-size: 0.875rem;
    text-align: center;
}

.ss__option {
    padding: 0.45rem 0.65rem;
    font-size: var(--app-font-size-input, 0.9375rem);
    line-height: 1.35;
    color: var(--form-input-color, #1f2937);
    cursor: pointer;
    user-select: none;
}

.ss__option.is-placeholder:not(.is-active) {
    color: #6c757d;
}

.ss__option.is-selected {
    background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.12);
    font-weight: 600;
}

.ss__option.is-active {
    background: var(--brand-primary) !important;
    color: #fff !important;
}

.ss__option:hover:not(.is-active) {
    background: #f8f9fa;
}
</style>

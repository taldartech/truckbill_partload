<template>
    <div class="searchable-select" ref="container">
        <input
            :id="id"
            ref="trigger"
            type="text"
            class="searchable-select__trigger form-select"
            :class="{ 'is-invalid': error, 'form-select-sm': small, 'bg-light text-muted': disabled, 'text-muted': !hasSelection }"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            readonly
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click.stop="openDropdown"
            @focus="onTriggerFocus"
            @keydown="onTriggerKeydown"
        >

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="dropdown"
                class="searchable-select__dropdown dropdown-menu show p-0 shadow-sm"
                role="listbox"
                :style="dropdownStyle"
            >
                <div class="p-2 border-bottom sticky-top bg-white">
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="searchQuery"
                        placeholder="Search..."
                        ref="searchInput"
                        tabindex="-1"
                        @keydown="onSearchKeydown"
                    >
                </div>
                <ul class="list-unstyled mb-0">
                    <li v-if="dropdownOptions.length === 0" class="p-2 text-muted small text-center">
                        No results found
                    </li>
                    <li
                        v-for="(option, index) in dropdownOptions"
                        :key="option.__isPlaceholder ? '__placeholder__' : option[valueKey]"
                        role="option"
                        :aria-selected="isOptionSelected(option)"
                        @mousedown.prevent="selectOption(option)"
                        class="p-2 dropdown-item small"
                        :class="{
                            'active bg-primary text-white': isOptionSelected(option),
                            'bg-light': highlightedIndex === index,
                            'text-muted': option.__isPlaceholder,
                        }"
                        style="cursor: pointer;"
                    >
                        {{ option[labelKey] }}
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: undefined,
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    labelKey: {
        type: String,
        default: 'name'
    },
    error: {
        type: Boolean,
        default: false
    },
    small: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const container = ref(null);
const dropdown = ref(null);
const searchInput = ref(null);
const trigger = ref(null);
const dropdownStyle = ref({});
let suppressFocusOpen = false;

const selectedItem = computed(() => {
    return props.options.find(opt => String(opt[props.valueKey]) === String(props.modelValue)) || null;
});

const hasSelection = computed(() => selectedItem.value !== null);

const displayValue = computed(() => selectedItem.value?.[props.labelKey] ?? '');

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = opt[props.labelKey] ? String(opt[props.labelKey]) : '';
        return label.toLowerCase().includes(lowerQuery);
    });
});

const dropdownOptions = computed(() => {
    const showPlaceholder = !searchQuery.value
        || props.placeholder.toLowerCase().includes(searchQuery.value.toLowerCase());

    if (!showPlaceholder) {
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

const isOptionSelected = (option) => {
    if (option.__isPlaceholder) {
        return !hasSelection.value;
    }

    return String(props.modelValue) === String(option[props.valueKey]);
};

const resolveDropdownZIndex = () => {
    const modal = trigger.value?.closest('.modal');

    if (!modal) {
        return 1050;
    }

    const modalZIndex = Number.parseInt(window.getComputedStyle(modal).zIndex, 10);

    return (Number.isFinite(modalZIndex) ? modalZIndex : 1055) + 10;
};

const updateDropdownPosition = () => {
    if (!trigger.value) return;

    const rect = trigger.value.getBoundingClientRect();
    const maxHeight = 250;
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const openUpward = spaceBelow < maxHeight && spaceAbove > spaceBelow;
    const availableHeight = openUpward ? spaceAbove - 8 : spaceBelow - 8;

    dropdownStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        zIndex: resolveDropdownZIndex(),
        maxHeight: `${Math.min(maxHeight, Math.max(availableHeight, 120))}px`,
        overflowY: 'auto',
        top: openUpward ? 'auto' : `${rect.bottom}px`,
        bottom: openUpward ? `${window.innerHeight - rect.top}px` : 'auto',
    };
};

const openDropdown = () => {
    if (props.disabled || isOpen.value) return;
    isOpen.value = true;
    highlightedIndex.value = dropdownOptions.value.length ? 0 : -1;
    searchQuery.value = '';
    nextTick(() => {
        updateDropdownPosition();
        searchInput.value?.focus();
    });
};

const onTriggerFocus = () => {
    if (props.disabled) return;

    if (suppressFocusOpen) {
        suppressFocusOpen = false;
        return;
    }

    openDropdown();
};

const closeDropdown = () => {
    isOpen.value = false;
    highlightedIndex.value = -1;
    searchQuery.value = '';
};

const selectOption = (option) => {
    if (option.__isPlaceholder) {
        emit('update:modelValue', '');
        emit('change', '');
    } else {
        emit('update:modelValue', option[props.valueKey]);
        emit('change', option[props.valueKey]);
    }
    closeDropdown();
    suppressFocusOpen = true;
    nextTick(() => {
        trigger.value?.focus();
    });
};

const selectHighlightedOption = () => {
    const option = dropdownOptions.value[highlightedIndex.value];
    if (option) {
        selectOption(option);
    }
};

const moveHighlight = (direction) => {
    if (!dropdownOptions.value.length) return;

    if (highlightedIndex.value === -1) {
        highlightedIndex.value = direction > 0 ? 0 : dropdownOptions.value.length - 1;
        return;
    }

    highlightedIndex.value = (highlightedIndex.value + direction + dropdownOptions.value.length)
        % dropdownOptions.value.length;
};

const onTriggerKeydown = (event) => {
    if (props.disabled) return;

    if (event.key === 'Tab') {
        closeDropdown();
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeDropdown();
        return;
    }

    if (event.key === ' ' || event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();

        if (!isOpen.value) {
            openDropdown();
            return;
        }

        if (event.key === 'ArrowDown') {
            moveHighlight(1);
        } else if (event.key === 'ArrowUp') {
            moveHighlight(-1);
        } else if (event.key === ' ') {
            selectHighlightedOption();
        }
    }

    if (event.key === 'Enter') {
        if (isOpen.value) {
            event.preventDefault();
            selectHighlightedOption();
        }
    }
};

const focusRelative = (element, backwards = false) => {
    const focusable = Array.from(
        document.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
    ).filter((el) => el.getClientRects().length > 0);

    const index = focusable.indexOf(element);
    if (index === -1) return;

    const next = focusable[index + (backwards ? -1 : 1)];
    next?.focus();
};

const onSearchKeydown = (event) => {
    if (event.key === 'Tab') {
        event.preventDefault();
        const backwards = event.shiftKey;
        closeDropdown();
        suppressFocusOpen = true;
        nextTick(() => {
            trigger.value?.focus();
            focusRelative(trigger.value, backwards);
        });
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeDropdown();
        suppressFocusOpen = true;
        trigger.value?.focus();
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveHighlight(1);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveHighlight(-1);
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        if (highlightedIndex.value >= 0) {
            selectHighlightedOption();
        } else if (filteredOptions.value.length === 1) {
            selectOption(filteredOptions.value[0]);
        }
    }
};

const handleClickOutside = (event) => {
    if (
        container.value?.contains(event.target) ||
        dropdown.value?.contains(event.target)
    ) {
        return;
    }

    closeDropdown();
};

watch(searchQuery, () => {
    highlightedIndex.value = dropdownOptions.value.length ? 0 : -1;
});

watch(isOpen, (open) => {
    if (open) {
        window.addEventListener('scroll', updateDropdownPosition, true);
        window.addEventListener('resize', updateDropdownPosition);
    } else {
        window.removeEventListener('scroll', updateDropdownPosition, true);
        window.removeEventListener('resize', updateDropdownPosition);
    }
});

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    window.removeEventListener('scroll', updateDropdownPosition, true);
    window.removeEventListener('resize', updateDropdownPosition);
});
</script>

<style scoped>
.searchable-select {
    position: relative;
}

.searchable-select__trigger {
    width: 100%;
    cursor: pointer;
    background-color: #fff;
    caret-color: transparent;
    border: 1px solid var(--brand-border);
}

.searchable-select__trigger:disabled {
    cursor: not-allowed;
}

.searchable-select__trigger:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 0.25rem var(--brand-input-focus-shadow);
}
</style>

<style>
.searchable-select__dropdown {
    display: block;
    border: 1px solid var(--brand-border);
    border-radius: 0.375rem;
    background-color: #fff;
    /* Above Bootstrap modal (1055) when teleported to body */
    z-index: 1065;
}

.searchable-select__dropdown .dropdown-item:active {
    background-color: #f8f9fa;
    color: #212529;
}

.searchable-select__dropdown .dropdown-item.active {
    background-color: var(--brand-primary) !important;
    color: #fff !important;
}
</style>

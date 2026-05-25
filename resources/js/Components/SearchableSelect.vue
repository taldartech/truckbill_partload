<template>
    <div class="searchable-select" ref="container">
        <!-- Displayed Input -->
        <div 
            class="form-select border-secondary shadow-none d-flex align-items-center justify-content-between" 
            :class="{ 'is-invalid': error, 'form-select-sm': small, 'bg-light text-muted': disabled }"
            @click="toggleDropdown"
            :style="{ cursor: disabled ? 'not-allowed' : 'pointer', backgroundColor: disabled ? '#e9ecef' : '#fff' }"
        >
            <span v-if="selectedItem" class="text-truncate">{{ selectedItem[labelKey] }}</span>
            <span v-else class="text-muted">{{ placeholder }}</span>
        </div>

        <!-- Dropdown Menu -->
        <div v-show="isOpen" class="dropdown-menu show w-100 p-0 shadow-sm border-secondary" style="position: absolute; z-index: 1050; max-height: 250px; overflow-y: auto;">
            <div class="p-2 border-bottom sticky-top bg-white">
                <input 
                    type="text" 
                    class="form-control form-control-sm shadow-none border-secondary" 
                    v-model="searchQuery" 
                    placeholder="Search..." 
                    ref="searchInput"
                    @keydown.esc="closeDropdown"
                >
            </div>
            <ul class="list-unstyled mb-0">
                <li v-if="filteredOptions.length === 0" class="p-2 text-muted small text-center">
                    No results found
                </li>
                <li 
                    v-for="option in filteredOptions" 
                    :key="option[valueKey]"
                    @click="selectOption(option)"
                    class="p-2 dropdown-item small"
                    :class="{ 'active bg-primary text-white': modelValue === option[valueKey] }"
                    style="cursor: pointer;"
                >
                    {{ option[labelKey] }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
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
        default: 'Select...'
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
const container = ref(null);
const searchInput = ref(null);

const selectedItem = computed(() => {
    // strict check for equality (convert to string for comparison)
    return props.options.find(opt => String(opt[props.valueKey]) === String(props.modelValue)) || null;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = opt[props.labelKey] ? String(opt[props.labelKey]) : '';
        return label.toLowerCase().includes(lowerQuery);
    });
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        nextTick(() => {
            if (searchInput.value) {
                searchInput.value.focus();
            }
        });
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    emit('change', option[props.valueKey]);
    closeDropdown();
};

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.searchable-select {
    position: relative;
}
.dropdown-item:active {
    background-color: #f8f9fa;
    color: #212529;
}
.dropdown-item.active {
    background-color: #27AE60 !important;
    color: #fff !important;
}
.dropdown-menu {
    border-radius: 0.375rem;
}
</style>

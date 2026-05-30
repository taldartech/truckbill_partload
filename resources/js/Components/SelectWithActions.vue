<template>
    <div class="d-flex align-items-center gap-1 flex-grow-1">
        <div class="flex-grow-1">
            <SearchableSelect
                :small="small"
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', $event)"
                :options="options"
                :placeholder="placeholder"
                :error="error"
                :label-key="labelKey"
            />
        </div>
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary py-0 px-1"
            :disabled="!modelValue"
            title="Quick View"
            @click="$emit('view')"
        >
            <i class="fas fa-eye"></i>
        </button>
        <button
            type="button"
            class="btn btn-sm btn-outline-primary py-0 px-1"
            title="Add New"
            @click="$emit('add')"
        >
            <i class="fas fa-plus"></i>
        </button>
    </div>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select...',
    },
    labelKey: {
        type: String,
        default: 'name',
    },
    error: {
        type: Boolean,
        default: false,
    },
    small: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue', 'view', 'add']);
</script>

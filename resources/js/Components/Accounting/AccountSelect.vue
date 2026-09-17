<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    accounts: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select account' },
    error: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    small: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const options = computed(() =>
    (props.accounts || []).map((account) => ({
        id: account.id,
        name: account.label || account.name,
    })),
);

const value = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});
</script>

<template>
    <SearchableSelect
        v-model="value"
        :options="options"
        :placeholder="placeholder"
        :error="error"
        :disabled="disabled"
        :small="small"
    />
</template>

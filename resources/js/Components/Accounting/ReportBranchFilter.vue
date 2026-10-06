<template>
    <div v-if="branches" class="col-md-2">
        <label class="form-label">Branch</label>
        <select v-model="value" class="form-select form-select-sm">
            <option value="">All branches</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
        </select>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    branches: { type: Array, default: null },
});

const emit = defineEmits(['update:modelValue']);

const value = computed({
    get: () => props.modelValue ?? '',
    set: (v) => emit('update:modelValue', v),
});
</script>

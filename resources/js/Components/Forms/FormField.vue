<template>
    <div :class="fieldWrapperClass">
        <label v-if="label" :for="fieldId" class="form-label">
            {{ label }}<span v-if="required" class="text-danger"> *</span>
        </label>
        <slot />
        <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
        <div v-else-if="hint" class="form-text">{{ hint }}</div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, default: '' },
    fieldId: { type: String, default: undefined },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    col: { type: String, default: '' },
    wrapperClass: { type: String, default: '' },
});

const fieldWrapperClass = computed(() => {
    const classes = ['app-form-field', props.compact ? 'mb-2' : 'mb-3'];

    if (props.col) {
        classes.push(props.col);
    }

    if (props.wrapperClass) {
        classes.push(props.wrapperClass);
    }

    return classes.join(' ');
});
</script>

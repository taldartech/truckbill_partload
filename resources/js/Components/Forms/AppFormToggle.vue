<template>
    <label
        class="app-form-toggle"
        :class="{ 'is-on': modelValue }"
        :for="inputId"
    >
        <input
            :id="inputId"
            type="checkbox"
            class="app-form-toggle__input"
            role="switch"
            :checked="modelValue"
            :aria-checked="modelValue"
            @change="$emit('update:modelValue', $event.target.checked)"
        >
        <span class="app-form-toggle__track" aria-hidden="true">
            <span class="app-form-toggle__thumb" />
        </span>
        <span class="app-form-toggle__text">{{ label }}</span>
    </label>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, required: true },
    inputId: { type: String, required: true },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="password-field" :class="wrapperClass">
        <label v-if="label" :for="inputId" class="form-label fw-bold text-muted small">
            {{ label }}
        </label>
        <div class="password-field__control">
            <input
                :id="inputId"
                :type="visible ? 'text' : 'password'"
                class="form-control"
                :class="inputClass"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :minlength="minlength"
                :autocomplete="autocomplete"
                @input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                type="button"
                class="password-field__toggle"
                :aria-label="visible ? 'Hide password' : 'Show password'"
                tabindex="-1"
                @click="visible = !visible"
            >
                <i class="fas" :class="visible ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>
        <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref, useId } from 'vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    minlength: {
        type: [String, Number],
        default: undefined,
    },
    autocomplete: {
        type: String,
        default: 'current-password',
    },
    inputClass: {
        type: [String, Array, Object],
        default: '',
    },
    wrapperClass: {
        type: [String, Array, Object],
        default: '',
    },
});

defineEmits(['update:modelValue']);

const inputId = useId();
const visible = ref(false);
</script>

<style scoped>
.password-field__control {
    position: relative;
}

.password-field__control .form-control {
    padding-right: 2.75rem;
}

.password-field__toggle {
    position: absolute;
    top: 50%;
    right: 0.625rem;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: #6b7280;
    line-height: 1;
}

.password-field__toggle:hover,
.password-field__toggle:focus {
    color: var(--brand-primary);
}
</style>

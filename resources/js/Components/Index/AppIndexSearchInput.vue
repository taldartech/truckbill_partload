<template>
    <div
        class="input-group app-index-search"
        :class="{ 'input-group-sm': size === 'sm' }"
        :style="wrapperStyle"
    >
        <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
        <input
            :id="inputId"
            :value="modelValue"
            type="search"
            class="form-control"
            :placeholder="placeholder"
            :aria-label="ariaLabel || placeholder"
            :disabled="disabled"
            @input="$emit('update:modelValue', $event.target.value)"
            @keydown.enter="$emit('keydown.enter', $event)"
            @search="$emit('update:modelValue', $event.target.value)"
        >
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    inputId: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search…',
    },
    ariaLabel: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '280px',
    },
    size: {
        type: String,
        default: 'sm',
        validator: (value) => ['sm', 'md'].includes(value),
    },
});

defineEmits(['update:modelValue', 'keydown.enter']);

const wrapperStyle = computed(() => (props.maxWidth ? { maxWidth: props.maxWidth } : undefined));
</script>

<style scoped>
.app-index-search {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
}

.app-index-search .input-group-text {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    height: 36px;
    min-height: 36px;
    padding: 0 0.7rem;
    line-height: 1;
    border-color: #dbe3ef;
    background: #fff;
    color: #64748b;
}

.app-index-search .form-control {
    height: 36px;
    min-height: 36px;
    border-color: #dbe3ef;
    box-shadow: none;
}

.app-index-search .form-control:focus {
    border-color: #94a3b8;
    box-shadow: none;
    z-index: 3;
}

.app-index-search .input-group-text i {
    font-size: 0.8125rem;
    line-height: 1;
}
</style>

<template>
    <div class="app-show-field-grid" :style="gridStyle">
        <div
            v-for="(field, index) in visibleFields"
            :key="`${field.label}-${index}`"
            class="app-show-field"
            :class="{ 'app-show-field--accent': field.accent }"
        >
            <span class="app-show-field__label">{{ field.label }}</span>
            <span class="app-show-field__value">{{ displayValue(field.value) }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    fields: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Number,
        default: 3,
    },
});

const visibleFields = computed(() => (Array.isArray(props.fields) ? props.fields : []));

const gridStyle = computed(() => ({
    '--app-show-columns': String(Math.max(1, props.columns)),
}));

const displayValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return value;
};
</script>

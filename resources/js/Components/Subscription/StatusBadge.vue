<template>
    <span class="badge text-capitalize" :class="badgeClass">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'none',
    },
});

const label = computed(() => props.status.replace(/_/g, ' '));

const badgeClass = computed(() => {
    switch (props.status) {
        case 'active':
        case 'trial':
            return 'bg-success';
        case 'expired':
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
});
</script>

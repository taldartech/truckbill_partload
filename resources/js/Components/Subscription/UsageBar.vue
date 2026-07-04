<template>
    <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1">
            <strong>{{ metric.label }}</strong>
            <span>{{ formatUsed }} / {{ formatMax }}</span>
        </div>
        <div class="progress" style="height: 8px;">
            <div
                class="progress-bar"
                :class="barClass"
                role="progressbar"
                :style="{ width: `${metric.percent}%` }"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    metric: {
        type: Object,
        required: true,
    },
});

const formatUsed = computed(() => {
    if (props.metric.label.toLowerCase().includes('credit')) {
        return Number(props.metric.used).toLocaleString();
    }

    return props.metric.used;
});

const formatMax = computed(() => {
    if (!props.metric.max) {
        return '—';
    }

    if (props.metric.label.toLowerCase().includes('credit')) {
        return Number(props.metric.max).toLocaleString();
    }

    return props.metric.max;
});

const barClass = computed(() => {
    const percent = props.metric.percent;

    if (props.metric.invert) {
        if (percent <= 10) return 'bg-danger';
        if (percent <= 25) return 'bg-warning';

        return 'bg-success';
    }

    if (percent >= 90) return 'bg-danger';
    if (percent >= 75) return 'bg-warning';

    return 'bg-primary';
});
</script>

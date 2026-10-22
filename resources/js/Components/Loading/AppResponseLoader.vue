
<script setup>
import { computed, onMounted, ref } from 'vue';
import TruckLoader from '@/Components/Loading/TruckLoader.vue';
import { useInertiaLoading } from '@/composables/useInertiaLoading';

const props = defineProps({
    show: {
        type: Boolean,
        default: null,
    },
    label: {
        type: String,
        default: 'Loading…',
    },
    size: {
        type: String,
        default: 'md',
    },
    trackInertia: {
        type: Boolean,
        default: false,
    },
    delayMs: {
        type: Number,
        default: 250,
    },
    overlay: {
        type: Boolean,
        default: true,
    },
});

const isClientMounted = ref(false);

onMounted(() => {
    isClientMounted.value = true;
});

const { isLoading: inertiaLoading } = useInertiaLoading({
    delayMs: props.delayMs,
    enabled: props.trackInertia,
});

const visible = computed(() => {
    if (props.show !== null) {
        return props.show;
    }

    return props.trackInertia ? inertiaLoading.value : false;
});
</script>

<template>
    <Teleport v-if="overlay && isClientMounted" to="body">
        <Transition name="truck-response-loader">
            <div
                v-if="visible"
                class="truck-response-loader truck-response-loader--overlay"
                role="alert"
                aria-live="assertive"
                aria-busy="true"
            >
                <div class="truck-response-loader__panel">
                    <TruckLoader :size="size" :label="label" animated />
                </div>
            </div>
        </Transition>
    </Teleport>

    <div
        v-else-if="visible"
        class="truck-response-loader truck-response-loader--inline"
        role="status"
        aria-live="polite"
        aria-busy="true"
    >
        <TruckLoader :size="size" :label="label" animated />
    </div>
</template>

<style scoped>
.truck-response-loader--overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: grid;
    place-items: center;
    background: color-mix(in srgb, #0f172a 28%, transparent);
    backdrop-filter: blur(2px);
}

.truck-response-loader__panel {
    padding: 1.25rem 1.5rem;
    border-radius: 1rem;
    background: #fff;
    border: 1px solid var(--brand-border, #dee2e6);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
}

.truck-response-loader--inline {
    display: flex;
    justify-content: center;
    padding: 1.5rem 0;
}

.truck-response-loader-enter-active,
.truck-response-loader-leave-active {
    transition: opacity 0.18s ease;
}

.truck-response-loader-enter-from,
.truck-response-loader-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .truck-response-loader-enter-active,
    .truck-response-loader-leave-active {
        transition: none;
    }
}
</style>


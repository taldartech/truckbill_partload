<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
    },
    label: {
        type: String,
        default: '',
    },
    showRoad: {
        type: Boolean,
        default: true,
    },
    animated: {
        type: Boolean,
        default: true,
    },
});

const uid = useId().replace(/[^a-zA-Z0-9_-]/g, '');

const rootClass = computed(() => [
    'truck-loader',
    `truck-loader--${props.size}`,
    {
        'truck-loader--animated': props.animated,
        'truck-loader--with-label': Boolean(props.label),
    },
]);
</script>
<template>
    <div :class="rootClass" role="status" :aria-label="label || 'Loading'" :aria-busy="animated">
        <div class="truck-loader__stage">
            <div v-if="showRoad" class="truck-loader__road" aria-hidden="true">
                <span /><span /><span /><span /><span /><span />
            </div>

            <div class="truck-loader__runner">
                <svg class="truck-loader__truck" xmlns="http://www.w3.org/2000/svg" viewBox="50 100 500 280"
                    aria-hidden="true">
                    <defs>
                        <linearGradient :id="`tl-g-${uid}`" x1="87.68" y1="252.97" x2="494.68" y2="252.97"
                            gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#3ca58b" />
                            <stop offset="1" stop-color="#2a9bd7" />
                        </linearGradient>
                        <linearGradient :id="`tl-g1-${uid}`" x1="223.13" y1="284.37" x2="276.04" y2="123.88"
                            gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#3ca58b" />
                            <stop offset=".78" stop-color="#35a4a3" />
                        </linearGradient>
                        <linearGradient :id="`tl-g2-${uid}`" x1="307" y1="297.05" x2="366" y2="145.32"
                            gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#37a59c" />
                            <stop offset="1" stop-color="#2f9ebf" />
                        </linearGradient>
                        <linearGradient :id="`tl-g3-${uid}`" x1="416.79" y1="285.27" x2="474.83" y2="135.36"
                            gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#33a1b1" />
                            <stop offset="1" stop-color="#2a9bd5" />
                        </linearGradient>
                        <linearGradient :id="`tl-g4-${uid}`" x1="123.56" y1="277.16" x2="525.29" y2="277.16"
                            gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#3ca58b" />
                            <stop offset="1" stop-color="#2a9bd7" />
                        </linearGradient>
                    </defs>

                    <g class="truck-loader__body">
                        <polygon fill="#1d715d"
                            points="243.04 307.23 184.13 307.23 152.66 166.41 211.56 162.46 243.04 307.23" />
                        <polygon fill="#1d715d"
                            points="333.31 306.04 274.41 306.04 238.58 160.64 297.48 154.26 333.31 306.04" />
                        <path :fill="`url(#tl-g-${uid})`"
                            d="M183.63,306.04h-9.72l-2.24-7.13,39.89-136.45-45.88,3.07-58.59,2.07s-21.43-2.24-31.96,17.2c-10.53,19.43-25.1,69.63-25.1,69.63v36.03l2.43,6.88v6.48l-6.88,2.23v33.2l8.5,4.25h36.84s2.02-43.32,32.79-44.94c0,0,23.88-3.24,33.2,7.49,0,0,16.6,11.13,15.79,37.45h192.29s1.62-28.34,16.6-37.45h-197.96ZM60.56,322.06h-10.63v-13.66h10.63c1.38,4.45,1.38,9.21,0,13.66ZM135,235.71s-.71,6.38-2.83,6.98l-56.12,16.7s-15.79,5.77-13.06-9.11l16.4-48.58s3.95-11.23,11.54-12.14h44.08v46.15Z" />
                        <polygon :fill="`url(#tl-g1-${uid})`"
                            points="297.48 154.26 238.58 160.64 184.13 307.23 243.04 307.23 297.48 154.26" />
                        <path fill="#1d715d"
                            d="M445.45,306.04l-32.91-1.71c-5.36-.28-10.73-.17-16.07.33l-14.88,1.38-46.25-158.53,58.9-5.26,51.21,163.79Z" />
                        <polygon :fill="`url(#tl-g2-${uid})`"
                            points="394.24 142.25 335.33 147.52 274.41 306.04 333.31 306.04 394.24 142.25" />
                        <path :fill="`url(#tl-g3-${uid})`"
                            d="M530.42,182l-41.52-87.84-87.84,41.52,35.97,12.88-55.44,157.49,9.42-1.17c7.72-.96,15.52-1.21,23.28-.73l31.16,1.91,48.3-137.18,36.68,13.13Z" />
                        <path :fill="`url(#tl-g4-${uid})`"
                            d="M445.45,346.59s4.94-28.46-15.71-40.54h35.22l35.22-101.15,26.85,11.74v132.78l-25.23-23.48h-49.79s2.43,17.41.81,21.86l-7.37-1.21Z" />
                    </g>

                    <g class="truck-loader__wheel truck-loader__wheel--rear">
                        <path fill="#3ca591"
                            d="M132.62,306.04c-18.33,0-33.2,14.86-33.2,33.2s14.86,33.2,33.2,33.2,33.2-14.86,33.2-33.2-14.86-33.2-33.2-33.2ZM132.62,355.43c-8.94,0-16.19-7.25-16.19-16.19s7.25-16.19,16.19-16.19,16.19,7.25,16.19,16.19-7.25,16.19-16.19,16.19Z" />
                    </g>
                    <g class="truck-loader__wheel truck-loader__wheel--front">
                        <path fill="#2fa1b0"
                            d="M407.09,306.04c-18.33,0-33.2,14.86-33.2,33.2s14.86,33.2,33.2,33.2,33.2-14.86,33.2-33.2-14.86-33.2-33.2-33.2ZM407.09,355.43c-8.94,0-16.19-7.25-16.19-16.19s7.25-16.19,16.19-16.19,16.19,7.25,16.19,16.19-7.25,16.19-16.19,16.19Z" />
                    </g>
                </svg>
            </div>
        </div>

        <p v-if="label" class="truck-loader__label">{{ label }}</p>
    </div>
</template>

<style scoped>
.truck-loader {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.truck-loader__stage {
    position: relative;
    overflow: hidden;
}

.truck-loader--sm .truck-loader__stage {
    width: 6.5rem;
    height: 2.75rem;
}

.truck-loader--md .truck-loader__stage {
    width: 10.5rem;
    height: 4.25rem;
}

.truck-loader--lg .truck-loader__stage {
    width: min(16rem, 78vw);
    height: 6.75rem;
}

.truck-loader__runner {
    position: absolute;
    top: 0;
    left: 0;
    width: 58%;
    height: 100%;
}

.truck-loader:not(.truck-loader--animated) .truck-loader__runner {
    left: 50%;
    transform: translateX(-50%);
}

.truck-loader__road {
    position: absolute;
    left: -40%;
    right: -40%;
    bottom: 12%;
    display: flex;
    gap: 0.85rem;
    opacity: 0.4;
}

.truck-loader__road span {
    display: block;
    width: 1.65rem;
    height: 0.18rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--brand-primary, #2fb36f) 55%, #94a3b8);
    flex: 0 0 auto;
}

.truck-loader__truck {
    width: 100%;
    height: 100%;
    overflow: visible;
}

.truck-loader__wheel {
    transform-box: fill-box;
    transform-origin: center;
}

.truck-loader__label {
    margin: 0;
    color: var(--brand-text-muted, #64748b);
    font-size: 0.875rem;
    font-weight: 500;
}

.truck-loader--animated .truck-loader__runner {
    animation: truck-loader-drive-across 3.2s linear infinite;
}

.truck-loader--animated .truck-loader__truck {
    animation: truck-loader-run-bob 0.45s ease-in-out infinite;
}

.truck-loader--animated .truck-loader__wheel {
    animation: truck-loader-wheel-spin 0.45s linear infinite;
}

.truck-loader--animated .truck-loader__road span {
    animation: truck-loader-road-scroll 0.55s linear infinite;
}

@keyframes truck-loader-drive-across {
    0% {
        transform: translateX(175%);
        opacity: 0;
    }

    8% {
        opacity: 1;
    }

    92% {
        opacity: 1;
    }

    100% {
        transform: translateX(-120%);
        opacity: 0;
    }
}

@keyframes truck-loader-run-bob {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-2px);
    }
}

@keyframes truck-loader-wheel-spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

@keyframes truck-loader-road-scroll {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(2.5rem);
    }
}

@media (prefers-reduced-motion: reduce) {

    .truck-loader--animated .truck-loader__runner,
    .truck-loader--animated .truck-loader__truck,
    .truck-loader--animated .truck-loader__wheel,
    .truck-loader--animated .truck-loader__road span {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }

    .truck-loader--animated .truck-loader__runner {
        left: 50%;
        transform: translateX(-50%) !important;
    }
}
</style>

<template>
    <component :is="wrapperTag" v-bind="wrapperProps" @click="handlePrint">
        <slot>{{ linkText }}</slot>
    </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    biltyId: {
        type: [Number, String],
        required: true,
    },
    variant: {
        type: String,
        default: 'button',
    },
    buttonClass: {
        type: String,
        default: 'btn btn-sm btn-outline-info',
    },
    title: {
        type: String,
        default: 'Print Bilty',
    },
    linkText: {
        type: String,
        default: '',
    },
});

const wrapperTag = computed(() => 'button');

const wrapperProps = computed(() => {
    if (props.variant === 'link') {
        return {
            type: 'button',
            class: 'btn btn-link p-0 border-0 align-baseline fw-bold text-decoration-none',
            title: props.title,
        };
    }

    return {
        type: 'button',
        class: props.buttonClass,
        title: props.title,
    };
});

const handlePrint = () => {
    window.open(`/bilties/${props.biltyId}/print`, '_blank');
};
</script>

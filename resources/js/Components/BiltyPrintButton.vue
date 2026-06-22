<template>
    <component :is="wrapperTag" v-bind="wrapperProps" @click="handlePrint">
        <slot>{{ linkText }}</slot>
    </component>

    <div class="modal fade" ref="modalEl" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Print Bilty</h5>
                    <button type="button" class="btn-close" @click="hideModal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Do you want to show freight amounts on this print?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="printWithAmount(false)">Hide Amounts</button>
                    <button type="button" class="btn btn-primary" @click="printWithAmount(true)">Show Amounts</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

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

const page = usePage();
const modalEl = ref(null);
let modal = null;

const biltyPrintConfig = computed(() => page.props.auth?.active_branch?.bilty_print_config ?? {
    ask_amount_at_print: false,
});

const wrapperTag = computed(() => (props.variant === 'link' ? 'button' : 'button'));

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

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && modalEl.value) {
        modal = new bootstrap.Modal(modalEl.value);
    }
});

const openPrintUrl = (showAmount = null) => {
    let url = `/bilties/${props.biltyId}/print`;

    if (showAmount !== null) {
        url += `?show_amount=${showAmount ? '1' : '0'}`;
    }

    window.open(url, '_blank');
};

const hideModal = () => {
    modal?.hide();
};

const printWithAmount = (showAmount) => {
    hideModal();
    openPrintUrl(showAmount);
};

const handlePrint = () => {
    if (biltyPrintConfig.value.ask_amount_at_print) {
        modal?.show();
        return;
    }

    openPrintUrl();
};
</script>

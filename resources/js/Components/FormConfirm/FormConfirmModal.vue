<template>
    <Transition name="fade">
        <div v-if="show" class="form-confirm-overlay" @click.self="onCancel">
            <div
                class="form-confirm-card"
                :class="{ 'form-confirm-card--review': isReview }"
                role="dialog"
                aria-modal="true"
                aria-labelledby="form-confirm-title"
            >
                <template v-if="isReview">
                    <button type="button" class="form-confirm-close-plain" @click="onCancel" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </template>
                <div v-else class="form-confirm-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-clipboard-check"></i>
                        <h5 id="form-confirm-title" class="m-0 fw-bold text-white">{{ title }}</h5>
                    </div>
                    <button type="button" class="form-confirm-close" @click="onCancel" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-confirm-body" :class="{ 'form-confirm-body--review': isReview }">
                    <header v-if="isReview" class="form-confirm-review-header">
                        <div class="form-confirm-review-icon" aria-hidden="true">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <h5 id="form-confirm-title" class="form-confirm-review-title">{{ title }}</h5>
                            <p v-if="subtitle" class="form-confirm-review-subtitle">{{ subtitle }}</p>
                        </div>
                    </header>
                    <slot />
                </div>
                <div class="form-confirm-footer" :class="{ 'form-confirm-footer--review': isReview }">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" @click="onCancel">
                        Cancel
                        <span class="form-confirm-kbd-group">
                            <kbd>N</kbd>
                            <kbd>Esc</kbd>
                        </span>
                    </button>
                    <button
                        v-if="showPrintButton"
                        type="button"
                        class="btn btn-sm btn-success px-3 fw-semibold"
                        :disabled="confirmDisabled"
                        @click="onConfirmPrint"
                    >
                        <i class="fas fa-print me-1"></i> Save &amp; Print
                        <span class="form-confirm-kbd-group">
                            <kbd>P</kbd>
                        </span>
                    </button>
                    <button
                        ref="confirmButtonRef"
                        type="button"
                        class="btn btn-sm btn-outline-info px-3"
                        :disabled="confirmDisabled"
                        @click="onConfirm"
                    >
                        <i class="fas fa-check me-1"></i> {{ confirmLabel }}
                        <span class="form-confirm-kbd-group">
                            <kbd>Y</kbd>
                            <kbd>Enter</kbd>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed, ref, watch, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirm Details' },
    subtitle: { type: String, default: '' },
    variant: { type: String, default: 'brand' },
    confirmLabel: { type: String, default: 'Confirm & Save' },
    showPrintButton: { type: Boolean, default: true },
    confirmDisabled: { type: Boolean, default: false },
});

const emit = defineEmits(['cancel', 'confirm', 'confirm-print']);

const confirmButtonRef = ref(null);
const isReview = computed(() => props.variant === 'review');

const onCancel = () => emit('cancel');
const onConfirm = () => {
    if (props.confirmDisabled) {
        return;
    }

    emit('confirm');
};
const onConfirmPrint = () => {
    if (props.confirmDisabled) {
        return;
    }

    emit('confirm-print');
};

const handleKeydown = (event) => {
    if (!props.show) return;

    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onCancel();
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onConfirm();
        return;
    }

    if (event.key === 'y' || event.key === 'Y') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onConfirm();
        return;
    }

    if ((event.key === 'p' || event.key === 'P') && props.showPrintButton) {
        event.preventDefault();
        event.stopImmediatePropagation();
        onConfirmPrint();
        return;
    }

    if (event.key === 'n' || event.key === 'N') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onCancel();
    }
};

watch(() => props.show, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        window.addEventListener('keydown', handleKeydown, true);
        nextTick(() => confirmButtonRef.value?.focus());
    } else {
        window.removeEventListener('keydown', handleKeydown, true);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown, true);
    if (props.show) {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.form-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1060;
}

.form-confirm-card {
    width: min(920px, 95vw);
    max-height: 92vh;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.form-confirm-card--review {
    position: relative;
    width: min(980px, 96vw);
    max-height: none;
    border: 1px solid #e2e8f0;
    overflow: visible;
}

.form-confirm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--brand-gradient);
    color: #fff !important;
}

.form-confirm-close {
    border: none;
    background: transparent;
    color: #fff;
    padding: 0;
}

.form-confirm-close-plain {
    position: absolute;
    top: 0.85rem;
    right: 0.9rem;
    z-index: 2;
    border: none;
    background: transparent;
    color: #64748b;
    padding: 0.15rem;
    line-height: 1;
}

.form-confirm-body {
    padding: 1rem;
    overflow-y: auto;
}

.form-confirm-body--review {
    padding: 0.75rem 0.9rem 0.35rem;
    max-height: none;
    overflow: visible;
}

.form-confirm-review-header {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    margin-bottom: 0.55rem;
    padding-right: 1.35rem;
}

.form-confirm-review-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    flex-shrink: 0;
    border-radius: 0.5rem;
    background: rgba(47, 179, 111, 0.12);
    color: var(--brand-primary, #2fb36f);
    font-size: 1rem;
}

.form-confirm-review-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.form-confirm-review-subtitle {
    margin: 0.1rem 0 0;
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.3;
}

.form-confirm-footer {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    background: #f8fafc;
}

.form-confirm-footer--review {
    justify-content: center;
    border-top: none;
    background: #fff;
    padding: 0.35rem 0.9rem 0.75rem;
}

.form-confirm-kbd-group {
    display: inline-flex;
    gap: 0.2rem;
    margin-left: 0.35rem;
    vertical-align: middle;
}

.form-confirm-kbd-group kbd {
    font-size: 0.65rem;
    padding: 0.1rem 0.3rem;
    border-radius: 0.2rem;
    background: rgba(0, 0, 0, 0.06);
    color: inherit;
    font-weight: 600;
}

:deep(.confirm-section-title) {
    font-weight: 700;
    text-transform: uppercase;
    color: #27ae60;
    border-left: 4px solid #27ae60;
    padding-left: 0.5rem;
    margin-bottom: 0.75rem;
}

:deep(.confirm-field-box) {
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    background: #f8fafc;
    height: 100%;
}

:deep(.confirm-field-label) {
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.15rem;
}

:deep(.confirm-field-value) {
    display: block;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
}

:deep(.confirm-total-box) {
    border: 1px dashed #27ae60;
    background: rgba(39, 174, 96, 0.05);
    border-radius: 0.5rem;
    padding: 0.75rem;
    text-align: center;
}

:deep(.confirm-total-label) {
    display: block;
    text-transform: uppercase;
    color: #27ae60;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

:deep(.confirm-total-value) {
    font-weight: 800;
    color: #27ae60;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

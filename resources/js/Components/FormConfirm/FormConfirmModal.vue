<template>
    <Transition name="fade">
        <div v-if="show" class="form-confirm-overlay" @click.self="$emit('cancel')">
            <div class="form-confirm-card">
                <div class="form-confirm-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-clipboard-check"></i>
                        <h3 class="m-0 fw-bold">{{ title }}</h3>
                    </div>
                    <button type="button" class="form-confirm-close" @click="$emit('cancel')" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-confirm-body">
                    <slot />
                </div>
                <div class="form-confirm-footer">
                    <button type="button" class="btn btn-sm btn-secondary px-3" @click="$emit('cancel')">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success px-4 fw-bold" @click="$emit('confirm')">
                        <i class="fas fa-check-circle me-1"></i> Confirm & Save
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirm Details' },
});

defineEmits(['cancel', 'confirm']);
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

.form-confirm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--brand-gradient);
    color: #fff !important  ;
}

.form-confirm-close {
    border: none;
    background: transparent;
    color: #fff;
    padding: 0;
}

.form-confirm-body {
    padding: 1rem;
    overflow-y: auto;
}

.form-confirm-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    background: #f8fafc;
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

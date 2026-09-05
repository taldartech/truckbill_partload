<template>
    <Transition name="fade">
        <div v-if="show" class="keyboard-confirm-overlay" @click.self="onNo">
            <div class="keyboard-confirm-card" role="alertdialog" aria-modal="true" :aria-labelledby="titleId">
                <div class="keyboard-confirm-body">
                    <i class="fas fa-question-circle text-primary"></i>

                    <div class="flex-grow-1">
                        <p :id="titleId" class="keyboard-confirm-message">{{ message }}</p>

                        <div v-if="showRemark" class="keyboard-confirm-remark">
                            <label for="delete-remark" class="form-label">
                                Delete Remark <span class="text-danger">*</span>
                            </label>

                            <textarea id="delete-remark" v-model="remark" class="form-control" rows="3"
                                placeholder="Enter reason for deletion..."></textarea>

                        </div>
                    </div>
                </div>

                <div class="keyboard-confirm-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" @click="onNo">
                        No
                        <span class="keyboard-confirm-kbd-group">
                            <kbd v-if="!showRemark">N</kbd>
                            <kbd>Esc</kbd>
                        </span>
                    </button>

                    <button ref="yesButtonRef" type="button" class="btn btn-sm btn-primary px-3 fw-bold" @click="onYes">
                        {{ showRemark ? 'Delete' : 'Yes' }}
                        <span v-if="!showRemark" class="keyboard-confirm-kbd-group">
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
import { ref, watch, onUnmounted, nextTick } from 'vue';
import {
    useKeyboardConfirm,
    confirmKeyboardPrompt,
    confirmKeyboardPromptWithRemark,
    cancelKeyboardPrompt,
} from '@/composables/useKeyboardConfirm';

const {
    show,
    message,
    showRemark,
    remark,
} = useKeyboardConfirm();

const titleId = `keyboard-confirm-${Math.random().toString(36).slice(2, 9)}`;
const yesButtonRef = ref(null);

const onYes = () => {
    if (showRemark.value) {
        confirmKeyboardPromptWithRemark();
    } else {
        confirmKeyboardPrompt();
    }
};

const onNo = () => cancelKeyboardPrompt();

const handleKeydown = (event) => {
    if (!show.value) return;

    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onNo();
        return;
    }

    if (showRemark.value) {
        const target = event.target;

        if (
            target instanceof HTMLInputElement ||
            target instanceof HTMLTextAreaElement ||
            target instanceof HTMLSelectElement
        ) {
            return;
        }

        return;
    }

    if (event.key === 'n' || event.key === 'N') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onNo();
        return;
    }

    if (event.key === 'y' || event.key === 'Y') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onYes();
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        event.stopImmediatePropagation();
        onYes();
    }
};

watch(show, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        window.addEventListener('keydown', handleKeydown, true);

        nextTick(() => {
            if (showRemark.value) {
                document.getElementById('delete-remark')?.focus();
            } else {
                yesButtonRef.value?.focus();
            }
        });
    } else {
        window.removeEventListener('keydown', handleKeydown, true);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown, true);

    if (show.value) {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.keyboard-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1070;
}

.keyboard-confirm-card {
    width: min(420px, 92vw);
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
    overflow: hidden;
}

.keyboard-confirm-body {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1.25rem 1.25rem 1rem;
}

.keyboard-confirm-body i {
    font-size: 1.35rem;
    margin-top: 0.1rem;
}

.keyboard-confirm-message {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
    white-space: pre-line;
    line-height: 1.45;
}

.keyboard-confirm-remark {
    margin-top: 1rem;
}

.keyboard-confirm-remark .form-label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.keyboard-confirm-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.keyboard-confirm-kbd-group {
    display: inline-flex;
    gap: 0.2rem;
    margin-left: 0.35rem;
    vertical-align: middle;
}

.keyboard-confirm-kbd-group kbd {
    font-size: 0.65rem;
    padding: 0.1rem 0.3rem;
    border-radius: 0.2rem;
    background: rgba(0, 0, 0, 0.06);
    color: inherit;
    font-weight: 600;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
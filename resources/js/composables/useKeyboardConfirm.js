import { nextTick, ref } from 'vue';
import { focusNextFocusable } from '@/utils/focusNavigation';

const show = ref(false);
const message = ref('');
let pendingResolve = null;
let pendingFocusContext = null;

export const isKeyboardConfirmOpen = ref(false);

export function askKeyboardConfirm(text, focusContext = null) {
    message.value = text;
    show.value = true;
    isKeyboardConfirmOpen.value = true;

    pendingFocusContext = {
        from: focusContext?.from ?? document.activeElement,
        skipContainer: focusContext?.skipContainer ?? null,
    };

    return new Promise((resolve) => {
        pendingResolve = resolve;
    });
}

function settle(result) {
    show.value = false;
    isKeyboardConfirmOpen.value = false;
    message.value = '';

    const resolve = pendingResolve;
    const focusContext = pendingFocusContext;
    pendingResolve = null;
    pendingFocusContext = null;

    resolve?.(result);

    if (!result && focusContext?.from instanceof HTMLElement) {
        nextTick(() => {
            focusNextFocusable(focusContext.from, focusContext.skipContainer);
        });
    }
}

export function confirmKeyboardPrompt() {
    settle(true);
}

export function cancelKeyboardPrompt() {
    settle(false);
}

export function useKeyboardConfirm() {
    return {
        show,
        message,
        confirmKeyboardPrompt,
        cancelKeyboardPrompt,
    };
}

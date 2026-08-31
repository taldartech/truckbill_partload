import { nextTick, ref } from 'vue';
import { focusNextFocusable } from '@/utils/focusNavigation';

const show = ref(false);
const message = ref('');
const showRemark = ref(false);
const remark = ref('');

let pendingResolve = null;
let pendingFocusContext = null;

export const isKeyboardConfirmOpen = ref(false);

export function askKeyboardConfirm(text, focusContext = null) {
    message.value = text;
    showRemark.value = false;
    remark.value = '';
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

export function askKeyboardConfirmWithRemark(text, focusContext = null) {
    message.value = text;
    showRemark.value = true;
    remark.value = '';
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
    showRemark.value = false;
    remark.value = '';

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

export function confirmKeyboardPromptWithRemark() {
    const value = remark.value.trim();

    if (!value) {
        return;
    }

    settle(value);
}

export function cancelKeyboardPrompt() {
    settle(false);
}

export function useKeyboardConfirm() {
    return {
        show,
        message,
        showRemark,
        remark,
        confirmKeyboardPrompt,
        confirmKeyboardPromptWithRemark,
        cancelKeyboardPrompt,
    };
}
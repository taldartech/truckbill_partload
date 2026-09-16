import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, readonly, ref } from 'vue';

/**
 * Tracks Inertia visit activity for response loaders.
 */
export function useInertiaLoading(options = {}) {
    const delayMs = options.delayMs ?? 250;
    const enabled = options.enabled ?? true;
    const isLoading = ref(false);
    let showTimeout = null;
    const removeListeners = [];

    const clearShowTimeout = () => {
        if (showTimeout) {
            clearTimeout(showTimeout);
            showTimeout = null;
        }
    };

    const start = () => {
        clearShowTimeout();
        showTimeout = setTimeout(() => {
            isLoading.value = true;
        }, delayMs);
    };

    const finish = () => {
        clearShowTimeout();
        isLoading.value = false;
    };

    onMounted(() => {
        if (!enabled) {
            return;
        }

        removeListeners.push(
            router.on('start', () => {
                start();
            }),
        );

        removeListeners.push(
            router.on('finish', () => {
                finish();
            }),
        );
    });

    onUnmounted(() => {
        clearShowTimeout();
        removeListeners.forEach((remove) => remove());
        removeListeners.length = 0;
    });

    return {
        isLoading: readonly(isLoading),
    };
}
import { ref } from 'vue';

export function useFormConfirm() {
    const showConfirm = ref(false);

    const openConfirm = () => {
        showConfirm.value = true;
    };

    const closeConfirm = () => {
        showConfirm.value = false;
    };

    const confirmAndRun = (callback) => {
        closeConfirm();
        callback();
    };

    return {
        showConfirm,
        openConfirm,
        closeConfirm,
        confirmAndRun,
    };
}

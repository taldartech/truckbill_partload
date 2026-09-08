import { onBeforeUnmount, onMounted, ref } from 'vue';

const MOBILE_BREAKPOINT = 576.98;

export function useResponsiveIndexFilters() {
    const showFilters = ref(typeof window === 'undefined' || window.innerWidth > MOBILE_BREAKPOINT);

    const handleResize = () => {
        showFilters.value = window.innerWidth > MOBILE_BREAKPOINT;
    };

    onMounted(() => window.addEventListener('resize', handleResize));
    onBeforeUnmount(() => window.removeEventListener('resize', handleResize));

    return { showFilters };
}
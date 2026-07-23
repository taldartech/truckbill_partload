import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Lightweight search filter for master index pages.
 */
export function useMasterIndexSearch(props, path) {
    const searching = ref(false);

    const filters = reactive({
        search: props.filters?.search ?? '',
    });

    watch(
        () => props.filters,
        (next) => {
            filters.search = next?.search ?? '';
        },
        { deep: true },
    );

    const applyFilters = () => {
        searching.value = true;

        const query = {};
        const search = filters.search.trim();
        if (search !== '') {
            query.search = search;
        }

        router.get(path, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                searching.value = false;
            },
        });
    };

    const resetFilters = () => {
        filters.search = '';
        applyFilters();
    };

    return {
        searching,
        filters,
        applyFilters,
        resetFilters,
    };
}

<template>
    <div :id="panelId" class="app-index-collapsible-filters">
        <div class="app-index-collapsible-filters__inner">
            <div class="app-index-filters-fields">
                <slot />
            </div>
            <div class="app-index-collapsible-filters__actions">
                <button
                    type="button"
                    class="btn btn-brand-primary btn-sm app-index-collapsible-filters__btn"
                    :disabled="loading"
                    @click="$emit('search')"
                >
                    <i class="fas fa-search" aria-hidden="true"></i>
                    Search
                </button>
                <button
                    type="button"
                    class="btn btn-back btn-sm app-index-collapsible-filters__btn"
                    :disabled="loading"
                    @click="$emit('reset')"
                >
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    panelId: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['search', 'reset']);
</script>

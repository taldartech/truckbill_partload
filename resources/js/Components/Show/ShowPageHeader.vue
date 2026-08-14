<template>
    <div class="app-show-header">
        <div class="app-show-header__main">
            <div class="app-show-header__titles">
                <h1 class="app-show-header__title">{{ title }}</h1>
                <p v-if="subtitle" class="app-show-header__subtitle">{{ subtitle }}</p>
            </div>
            <span
                v-if="badge"
                class="badge app-show-header__badge"
                :class="badgeClass"
            >
                {{ badge }}
            </span>
        </div>
        <div v-if="$slots.actions" class="app-show-header__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    badge: { type: String, default: '' },
    badgeClass: { type: String, default: 'bg-secondary' },
});
</script>

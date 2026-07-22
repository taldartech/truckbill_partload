<template>
    <section class="app-form-section" :class="{ 'app-form-section--first': first }">
        <div v-if="title" class="app-form-section__head">
            <h2 class="app-form-section__title">{{ title }}</h2>
            <span v-if="hint" class="app-form-section__hint">{{ hint }}</span>
            <slot name="title-actions" />
        </div>
        <slot />
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    hint: { type: String, default: '' },
    first: { type: Boolean, default: false },
});
</script>

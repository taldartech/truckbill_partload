<template>
    <section class="app-show-section" :class="{ 'app-show-section--first': first }">
        <div v-if="title" class="app-show-section__head">
            <div class="app-show-section__head-main">
                <h2 class="app-show-section__title">
                    {{ title }}<span v-if="identity" class="app-show-section__identity"> ({{ identity }})</span>
                </h2>
                <span v-if="hint" class="app-show-section__hint">{{ hint }}</span>
            </div>
            <div v-if="$slots['title-actions']" class="app-show-section__actions">
                <slot name="title-actions" />
            </div>
        </div>
        <div class="app-show-section__body" :class="{ 'app-show-section__body--flush': flush }">
            <slot />
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    identity: { type: String, default: '' },
    hint: { type: String, default: '' },
    first: { type: Boolean, default: false },
    flush: { type: Boolean, default: false },
});
</script>

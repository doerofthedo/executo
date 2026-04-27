<template>
    <component :is="tag" class="lex-panel lex-section-panel">
        <div v-if="title || $slots.actions" class="lex-section-panel-header">
            <h2 v-if="title" class="lex-section-panel-title">{{ title }}</h2>
            <div v-if="$slots.actions" class="lex-section-panel-actions">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </component>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    title?: string;
    tag?: string;
}>(), {
    title: undefined,
    tag: 'section',
});
</script>

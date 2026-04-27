<template>
    <component :is="tag" class="lex-panel lex-section-panel">
        <div v-if="title || $slots.actions || actionLabel" class="lex-section-panel-header">
            <h2 v-if="title" class="lex-section-panel-title">{{ title }}</h2>
            <div class="lex-section-panel-actions">
                <RouterLink
                    v-if="actionLabel && actionTo"
                    :to="actionTo"
                    class="lex-button lex-button-secondary"
                >
                    {{ actionLabel }}
                </RouterLink>
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </component>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
import type { RouteLocationRaw } from 'vue-router';

withDefaults(defineProps<{
    title?: string;
    tag?: string;
    actionLabel?: string;
    actionTo?: RouteLocationRaw;
}>(), {
    title: undefined,
    tag: 'section',
    actionLabel: undefined,
    actionTo: undefined,
});
</script>

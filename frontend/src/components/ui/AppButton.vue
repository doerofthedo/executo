<template>
    <component
        :is="to ? RouterLink : tag"
        :to="to"
        class="lex-button"
        :class="[`lex-button-${variant}`, { 'lex-button-full': full }]"
        v-bind="$attrs"
    >
        <i v-if="icon" :class="icon" aria-hidden="true" />
        <slot />
    </component>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
import type { RouteLocationRaw } from 'vue-router';

withDefaults(defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost';
    icon?: string;
    full?: boolean;
    to?: RouteLocationRaw;
    tag?: string;
}>(), {
    variant: 'secondary',
    icon: undefined,
    full: false,
    to: undefined,
    tag: 'button',
});
</script>

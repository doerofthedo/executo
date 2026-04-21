<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--lex-bg)] px-6 py-16">
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,#f8f3ec_0%,var(--lex-bg)_52%,#efe6d8_100%)]"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-60 bg-[linear-gradient(180deg,rgba(255,255,255,0.6),rgba(255,255,255,0.08))]"></div>
        <section :class="panelClasses">
            <slot />
        </section>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    panelClass?: string;
}>(), {
    panelClass: '',
});

const panelClasses = [
    'relative mx-auto w-full rounded-sm border border-[var(--lex-border)] bg-white p-8 shadow-[var(--lex-shadow)]',
    props.panelClass === '' ? 'max-w-[34rem]' : props.panelClass,
];
</script>

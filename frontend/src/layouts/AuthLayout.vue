<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--lex-bg)] px-6 py-16">
        <section :class="panelClasses">
            <slot />
        </section>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    panelClass?: string;
}>(), {
    panelClass: 'max-w-[28rem]',
});

const panelClasses = [
    'relative mx-auto w-full rounded-sm border border-[var(--lex-border)] bg-white p-8 shadow-[var(--lex-shadow)]',
    props.panelClass,
];
</script>

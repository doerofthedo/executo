<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-8"
        @click.self="$emit('cancel')"
    >
        <section class="w-full max-w-lg rounded-[1.75rem] bg-white p-8 shadow-2xl">
            <div class="space-y-3">
                <p v-if="eyebrow !== ''" class="lex-page-eyebrow">{{ eyebrow }}</p>
                <h2 class="lex-page-title text-[1.5rem]">{{ title }}</h2>
                <p class="lex-page-copy lex-page-copy-full">{{ message }}</p>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="lex-button lex-button-secondary" @click="$emit('cancel')">
                    {{ cancelLabel }}
                </button>
                <button type="button" class="lex-button lex-button-primary" :disabled="submitting" @click="$emit('confirm')">
                    {{ submitting ? pendingLabel : confirmLabel }}
                </button>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    cancelLabel: string;
    pendingLabel: string;
    submitting?: boolean;
    eyebrow?: string;
}>(), {
    submitting: false,
    eyebrow: '',
});

defineEmits<{
    cancel: [];
    confirm: [];
}>();
</script>

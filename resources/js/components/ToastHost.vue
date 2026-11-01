<script setup>
import { useUi } from '@/stores/ui';
const ui = useUi();
</script>

<template>
    <div class="pointer-events-none fixed bottom-24 left-1/2 z-[80] flex -translate-x-1/2 flex-col items-center gap-2 md:bottom-8" role="status" aria-live="polite">
        <TransitionGroup enter-active-class="transition duration-200" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div v-for="t in ui.toasts" :key="t.id"
                 class="max-w-[90vw] rounded-full border border-gold-500/50 bg-ink-900 px-5 py-2.5 text-sm text-gold-200 shadow-xl">
                {{ t.text }}
            </div>
        </TransitionGroup>
    </div>
</template>

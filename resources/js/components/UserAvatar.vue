<script setup>
import { computed } from 'vue';
import { initials as makeInitials } from '@/lib/format';

const props = defineProps({
    user: { type: Object, default: null },
    size: { type: String, default: 'md' },
    online: Boolean,
});

const sizes = { xs: 'h-9 w-9 text-xs', sm: 'h-10 w-10 text-sm', md: 'h-11 w-11 text-sm', lg: 'h-16 w-16 text-lg', xl: 'h-28 w-28 text-3xl' };

// Works with API users (avatar_url) and with real-time payloads (raw avatar path)
const src = computed(() => props.user?.avatar_url || (props.user?.avatar ? '/storage/' + props.user.avatar : null));
const initials = computed(() => makeInitials(props.user?.name || '?'));
</script>

<template>
    <span class="relative inline-grid shrink-0 place-items-center rounded-full bg-gold-grad font-bold text-ink-950 ring-2 ring-gold-500/70 ring-offset-2 ring-offset-ink-950" :class="sizes[size]">
        <img v-if="src" :src="src" alt="" class="h-full w-full rounded-full object-cover">
        <template v-else>{{ initials }}</template>
        <span v-if="online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-ink-900 bg-emerald-400" title="Online"></span>
    </span>
</template>

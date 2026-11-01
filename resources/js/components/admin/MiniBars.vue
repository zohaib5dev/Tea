<script setup>
import { computed } from 'vue';

const props = defineProps({ title: String, series: { type: Array, default: () => [] } });

const max = computed(() => Math.max(1, ...props.series.map((d) => d.count)));
const total = computed(() => props.series.reduce((n, d) => n + d.count, 0));
const label = (d) => new Date(d + 'T00:00:00').toLocaleDateString([], { month: 'short', day: 'numeric' });
</script>

<template>
    <div class="card p-5">
        <div class="flex items-baseline justify-between">
            <h3 class="font-display text-2xl font-bold text-gold-300">{{ title }}</h3>
            <span class="text-xs text-ink-300">last {{ series.length }} days: <b class="text-ink-50">{{ total }}</b></span>
        </div>

        <div class="mt-4 flex h-32 items-end gap-1" role="img" :aria-label="`${title}, ${total} in the last ${series.length} days`">
            <div v-for="d in series" :key="d.date" class="group relative flex h-full flex-1 items-end" :title="`${label(d.date)}: ${d.count}`">
                <div class="w-full rounded-t bg-gold-grad transition group-hover:opacity-80" :style="{ height: Math.max(d.count ? 6 : 2, (d.count / max) * 100) + '%' }" :class="d.count ? '' : 'opacity-25'"></div>
            </div>
        </div>

        <div v-if="series.length" class="mt-2 flex justify-between text-[11px] text-ink-300">
            <span>{{ label(series[0].date) }}</span><span>{{ label(series[series.length - 1].date) }}</span>
        </div>
    </div>
</template>

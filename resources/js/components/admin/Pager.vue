<script setup>
defineProps({ page: Number, lastPage: Number, total: Number });
const emit = defineEmits(['change']);
</script>

<template>
    <div v-if="lastPage > 1" class="mt-4 flex items-center justify-between gap-3">
        <button class="btn-ghost" :disabled="page <= 1" @click="emit('change', page - 1)">Previous</button>
        <span class="text-sm text-ink-300">Page {{ page }} of {{ lastPage }} <span class="hidden sm:inline">({{ total }} total)</span></span>
        <button class="btn-ghost" :disabled="page >= lastPage" @click="emit('change', page + 1)">Next</button>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import AppIcon from '@/components/AppIcon.vue';
import PostCard from '@/components/PostCard.vue';

const route = useRoute();
const router = useRouter();
const ui = useUi();
const post = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        post.value = (await http.get(`/posts/${route.params.id}`)).data;
    } catch (e) {
        ui.toast(errorText(e, 'This post is not available.'));
        router.replace('/feed');
    }
    loading.value = false;
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6">
        <RouterLink to="/feed" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-300 transition hover:text-gold-300">
            <AppIcon name="back" class="h-4 w-4" /> Back to feed
        </RouterLink>

        <div v-if="loading" class="card px-6 py-14 text-center text-ink-300">Loading…</div>
        <PostCard v-else-if="post" :post="post" single @deleted="router.replace('/feed')" />
    </div>
</template>

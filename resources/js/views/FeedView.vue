<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import AppIcon from '@/components/AppIcon.vue';
import PostCard from '@/components/PostCard.vue';
import PostComposer from '@/components/PostComposer.vue';
import SidebarFriends from '@/components/SidebarFriends.vue';

const ui = useUi();
const posts = ref([]);
const nextPage = ref(1);
const loading = ref(false);
const firstLoad = ref(true);
const composer = ref(null);
const sentinel = ref(null);
let observer = null;

async function loadMore() {
    if (loading.value || !nextPage.value) return;
    loading.value = true;
    try {
        const { data } = await http.get('/feed', { params: { page: nextPage.value } });
        posts.value.push(...data.data);
        nextPage.value = data.next_page;
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
    firstLoad.value = false;
}

onMounted(async () => {
    await loadMore();
    // Infinite scroll: load the next page when the bottom comes near
    observer = new IntersectionObserver((entries) => entries[0].isIntersecting && loadMore(), { rootMargin: '500px' });
    observer.observe(sentinel.value);
});
onBeforeUnmount(() => observer?.disconnect());

const onPosted = (post) => posts.value.unshift(post);
const onDeleted = (id) => { posts.value = posts.value.filter((p) => p.id !== id); };
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <section class="flex flex-col gap-6" aria-label="Posts">
            <div v-if="firstLoad" class="card px-6 py-14 text-center text-ink-300">Loading your feed…</div>

            <div v-else-if="!posts.length" class="card px-6 py-14 text-center">
                <p class="font-display text-3xl font-bold text-gold-300">Your feed is quiet</p>
                <p class="mt-2 text-ink-300">Create your first post, or add friends to see what they share.</p>
                <RouterLink to="/friends" class="btn-gold mt-6">Find friends</RouterLink>
            </div>

            <PostCard v-for="p in posts" :key="p.id" :post="p" @deleted="onDeleted" />

            <div ref="sentinel" class="py-4 text-center text-sm text-ink-300">
                <span v-if="loading && !firstLoad">Loading more…</span>
            </div>
        </section>

        <SidebarFriends />
    </div>

    <!-- Floating "New post" button -->
    <button type="button"
            class="fixed bottom-24 right-5 z-40 flex h-14 items-center justify-center gap-2 rounded-full bg-gold-grad px-4 font-bold text-ink-950 shadow-xl shadow-gold-500/30 transition hover:-translate-y-1 hover:shadow-gold-500/50 active:scale-95 md:bottom-8 md:right-8 md:px-6"
            aria-label="Create a new post" @click="composer.open()">
        <AppIcon name="edit" class="h-6 w-6" />
        <span class="hidden text-sm md:inline">New post</span>
    </button>

    <PostComposer ref="composer" @posted="onPosted" />
</template>

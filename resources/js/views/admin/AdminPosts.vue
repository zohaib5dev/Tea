<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { timeAgo } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import Pager from '@/components/admin/Pager.vue';

const ui = useUi();
const posts = ref([]);
const meta = reactive({ page: 1, last_page: 1, total: 0 });
const search = ref('');
const loading = ref(true);

async function load(page = 1) {
    loading.value = true;
    try {
        const { data } = await http.get('/admin/posts', { params: { page, search: search.value || undefined } });
        posts.value = data.data;
        Object.assign(meta, { page: data.page, last_page: data.last_page, total: data.total });
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
}

let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 300); });
onMounted(() => load());

async function remove(p) {
    if (!confirm('Delete this post? Its likes and comments disappear with it.')) return;
    try {
        await http.delete(`/admin/posts/${p.id}`);
        posts.value = posts.value.filter((x) => x.id !== p.id);
        meta.total--;
        ui.toast('Post deleted.');
    } catch (e) { ui.toast(errorText(e)); }
}

const visibility = { public: 'Public', friends: 'Friends', private: 'Only author' };
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-4xl font-bold sm:text-5xl">Posts</h1>
                <p class="mt-1 text-ink-300">{{ meta.total }} posts, newest first</p>
            </div>
            <input v-model="search" type="search" placeholder="Search text or author" aria-label="Search posts" class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-72">
        </div>

        <p v-if="loading && !posts.length" class="card px-6 py-14 text-center text-ink-300">Loading…</p>
        <p v-else-if="!posts.length" class="card px-6 py-14 text-center text-ink-300">No posts match.</p>

        <article v-for="p in posts" :key="p.id" class="card flex gap-4 p-4">
            <a v-if="p.media_url" :href="p.media_url" target="_blank" rel="noopener" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-ink-700 bg-ink-800">
                <img :src="p.media_url" alt="" loading="lazy" class="h-full w-full object-cover">
            </a>
            <div v-else class="grid h-24 w-24 shrink-0 place-items-center rounded-xl border border-ink-700 bg-ink-800 text-ink-300"><AppIcon name="chat" class="h-6 w-6" /></div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 text-xs text-ink-300">
                    <UserAvatar :user="p.user" size="xs" class="!h-6 !w-6 !text-[10px] !ring-1 !ring-offset-1" />
                    <RouterLink :to="`/users/${p.user.id}`" class="text-sm font-bold text-ink-50 hover:text-gold-300">{{ p.user.name }}</RouterLink>
                    <span>{{ timeAgo(p.created_at) }}</span>
                    <span class="rounded-full border border-ink-700 px-2 py-0.5">{{ visibility[p.visibility] }}</span>
                    <span>{{ p.likes_count }} likes, {{ p.comments_count }} comments</span>
                </div>
                <p class="mt-1.5 line-clamp-3 whitespace-pre-line break-words text-sm">{{ p.body || 'Photo only' }}</p>
            </div>

            <div class="flex shrink-0 flex-col items-end gap-2">
                <RouterLink :to="`/posts/${p.id}`" class="btn-ghost !px-3 !py-1 !text-xs">Open</RouterLink>
                <button class="rounded-full border border-red-400/40 px-3 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-950/40" @click="remove(p)">Delete</button>
            </div>
        </article>

        <Pager :page="meta.page" :last-page="meta.last_page" :total="meta.total" @change="load" />
    </div>
</template>

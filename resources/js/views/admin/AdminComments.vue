<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { timeAgo } from '@/lib/format';
import UserAvatar from '@/components/UserAvatar.vue';
import Pager from '@/components/admin/Pager.vue';

const ui = useUi();
const comments = ref([]);
const meta = reactive({ page: 1, last_page: 1, total: 0 });
const search = ref('');
const loading = ref(true);

async function load(page = 1) {
    loading.value = true;
    try {
        const { data } = await http.get('/admin/comments', { params: { page, search: search.value || undefined } });
        comments.value = data.data;
        Object.assign(meta, { page: data.page, last_page: data.last_page, total: data.total });
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
}

let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 300); });
onMounted(() => load());

async function remove(c) {
    if (!confirm('Delete this comment?')) return;
    try {
        await http.delete(`/admin/comments/${c.id}`);
        comments.value = comments.value.filter((x) => x.id !== c.id);
        meta.total--;
        ui.toast('Comment deleted.');
    } catch (e) { ui.toast(errorText(e)); }
}
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-4xl font-bold sm:text-5xl">Comments</h1>
                <p class="mt-1 text-ink-300">{{ meta.total }} comments, newest first</p>
            </div>
            <input v-model="search" type="search" placeholder="Search text or author" aria-label="Search comments" class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-72">
        </div>

        <p v-if="loading && !comments.length" class="card px-6 py-14 text-center text-ink-300">Loading…</p>
        <p v-else-if="!comments.length" class="card px-6 py-14 text-center text-ink-300">No comments match.</p>

        <article v-for="c in comments" :key="c.id" class="card flex items-start gap-4 p-4">
            <UserAvatar :user="c.user" size="sm" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 text-xs text-ink-300">
                    <RouterLink :to="`/users/${c.user.id}`" class="text-sm font-bold text-ink-50 hover:text-gold-300">{{ c.user.name }}</RouterLink>
                    <span>{{ timeAgo(c.created_at) }}</span>
                    <span v-if="c.is_reply" class="rounded-full border border-ink-700 px-2 py-0.5">Reply</span>
                </div>
                <p class="mt-1 whitespace-pre-line break-words text-sm">{{ c.body }}</p>
                <RouterLink v-if="c.post" :to="`/posts/${c.post.id}`" class="mt-1.5 inline-block text-xs text-ink-300 hover:text-gold-300">On post: {{ c.post.snippet }}</RouterLink>
            </div>
            <button class="shrink-0 rounded-full border border-red-400/40 px-3 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-950/40" @click="remove(c)">Delete</button>
        </article>

        <Pager :page="meta.page" :last-page="meta.last_page" :total="meta.total" @change="load" />
    </div>
</template>

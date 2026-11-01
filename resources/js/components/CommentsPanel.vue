<script setup>
import { onMounted, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import CommentItem from '@/components/CommentItem.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const props = defineProps({ post: Object });
const auth = useAuth();
const ui = useUi();

const comments = ref([]);
const loading = ref(true);
const failed = ref(false);
const body = ref('');
const busy = ref(false);

async function load() {
    loading.value = true; failed.value = false;
    try {
        const { data } = await http.get(`/posts/${props.post.id}/comments`);
        comments.value = data.data;
        props.post.comments_count = data.count;
    } catch { failed.value = true; }
    loading.value = false;
}

async function submit() {
    if (!body.value.trim()) return;
    busy.value = true;
    try {
        const { data } = await http.post(`/posts/${props.post.id}/comments`, { body: body.value });
        comments.value.unshift(data.comment);
        props.post.comments_count = data.count;
        body.value = '';
    } catch (e) { ui.toast(errorText(e)); }
    busy.value = false;
}

const remove = (id) => { comments.value = comments.value.filter((c) => c.id !== id); };

onMounted(load);
</script>

<template>
    <div class="border-t border-ink-700 px-5 py-4">
        <form class="mb-4 flex items-center gap-3" @submit.prevent="submit">
            <UserAvatar :user="auth.user" size="xs" />
            <input v-model="body" required maxlength="1000" autocomplete="off" class="input !rounded-full !py-2.5 text-sm" placeholder="Write a comment">
            <button class="btn-gold !px-4 !py-2" :disabled="busy">Send</button>
        </form>

        <p v-if="loading" class="text-sm text-ink-300">Loading comments…</p>
        <p v-else-if="failed" class="text-sm text-red-300">Could not load comments. <button class="underline" @click="load">Try again</button></p>
        <p v-else-if="!comments.length" class="text-sm text-ink-300">No comments yet. Be the first.</p>

        <div v-else class="space-y-5">
            <CommentItem v-for="c in comments" :key="c.id" :comment="c" :post="post" @removed="remove" />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { firstName, timeAgo } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const props = defineProps({ comment: Object, post: Object, depth: { type: Number, default: 0 } });
const emit = defineEmits(['removed']);
const ui = useUi();

const replying = ref(false);
const replyBody = ref('');
const busy = ref(false);

async function sendReply() {
    if (!replyBody.value.trim()) return;
    busy.value = true;
    try {
        const { data } = await http.post(`/posts/${props.post.id}/comments`, { body: replyBody.value, parent_id: props.comment.id });
        props.comment.replies.push(data.comment);
        props.post.comments_count = data.count;
        replyBody.value = '';
        replying.value = false;
    } catch (e) { ui.toast(errorText(e)); }
    busy.value = false;
}

async function remove() {
    if (!confirm('Delete this comment?')) return;
    try {
        const { data } = await http.delete(`/comments/${props.comment.id}`);
        props.post.comments_count = data.count;
        emit('removed', props.comment.id);
    } catch (e) { ui.toast(errorText(e)); }
}

function removeReply(id) {
    props.comment.replies = props.comment.replies.filter((r) => r.id !== id);
}
</script>

<template>
    <div class="flex gap-3">
        <RouterLink :to="`/users/${comment.user.id}`"><UserAvatar :user="comment.user" size="xs" /></RouterLink>
        <div class="min-w-0 flex-1">
            <div class="rounded-2xl rounded-tl-md bg-ink-800 px-4 py-3">
                <RouterLink :to="`/users/${comment.user.id}`" class="text-sm font-bold hover:text-gold-300">{{ comment.user.name }}</RouterLink>
                <p class="mt-0.5 whitespace-pre-line break-words text-sm leading-relaxed">{{ comment.body }}</p>
            </div>

            <div class="mt-1 flex items-center gap-4 px-2 text-xs text-ink-300">
                <span>{{ timeAgo(comment.created_at) }}</span>
                <button v-if="depth === 0" type="button" class="inline-flex items-center gap-1 font-semibold text-gold-400 hover:text-gold-300" @click="replying = !replying">
                    <AppIcon name="reply" class="h-3.5 w-3.5" /> Reply
                </button>
                <button v-if="comment.can_delete" type="button" class="font-semibold hover:text-red-400" @click="remove">Delete</button>
            </div>

            <form v-if="replying" class="mt-2 flex gap-2 px-2" @submit.prevent="sendReply">
                <input v-model="replyBody" required maxlength="1000" autocomplete="off" class="input !py-2 text-sm" :placeholder="`Reply to ${firstName(comment.user.name)}`">
                <button class="btn-gold !px-4 !py-2" :disabled="busy">Send</button>
            </form>

            <div v-if="depth === 0 && comment.replies.length" class="mt-3 space-y-4 border-l border-ink-700 pl-4">
                <CommentItem v-for="r in comment.replies" :key="r.id" :comment="r" :post="post" :depth="1" @removed="removeReply" />
            </div>
        </div>
    </div>
</template>

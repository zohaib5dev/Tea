<script setup>
import { reactive, ref, nextTick } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { timeAgo } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import CommentsPanel from '@/components/CommentsPanel.vue';

const props = defineProps({ post: Object, single: Boolean });
const emit = defineEmits(['deleted']);
const ui = useUi();

const showComments = ref(props.single);     // opened automatically on the single post page
const commentsLoaded = ref(props.single);
const shareLabel = ref('Share');

// ---------- like ----------
async function like() {
    const p = props.post;
    const was = p.liked;
    p.liked = !was;
    p.likes_count = Math.max(0, p.likes_count + (was ? -1 : 1));
    try {
        const { data } = await http.post(`/posts/${p.id}/like`);
        p.liked = data.liked;
        p.likes_count = data.count;
    } catch {
        p.liked = was;
        p.likes_count = Math.max(0, p.likes_count + (was ? 1 : -1));
    }
}

function toggleComments() {
    showComments.value = !showComments.value;
    commentsLoaded.value = true;
}

async function share() {
    const url = `${location.origin}/posts/${props.post.id}`;
    if (navigator.share) { try { await navigator.share({ url }); } catch { /* cancelled */ } return; }
    try {
        await navigator.clipboard.writeText(url);
        shareLabel.value = 'Link copied';
        setTimeout(() => (shareLabel.value = 'Share'), 1800);
    } catch { /* clipboard blocked */ }
}

// ---------- delete ----------
async function remove() {
    if (!confirm('Delete this post?')) return;
    try {
        await http.delete(`/posts/${props.post.id}`);
        ui.toast('Post deleted.');
        emit('deleted', props.post.id);
    } catch (e) { ui.toast(errorText(e)); }
}

// ---------- inline edit ----------
const editing = ref(false);
const saving = ref(false);
const editError = ref('');
const textarea = ref(null);
const form = reactive({ body: '', visibility: 'friends', file: null, preview: null, removeMedia: false });

function grow() {
    const ta = textarea.value;
    if (!ta) return;
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 480) + 'px';
}

async function startEdit() {
    form.body = props.post.body || '';
    form.visibility = props.post.visibility;
    form.file = null;
    form.removeMedia = false;
    form.preview = props.post.media_url;
    editError.value = '';
    editing.value = true;
    await nextTick();
    grow();
    textarea.value?.focus();
}

function pickFile(e) {
    const f = e.target.files[0];
    if (!f) return;
    if (f.size > 4 * 1024 * 1024) { ui.toast('Please choose a photo under 4 MB.'); e.target.value = ''; return; }
    form.file = f;
    form.removeMedia = false;
    form.preview = URL.createObjectURL(f);
}

function dropPhoto() {
    form.file = null;
    form.preview = null;
    form.removeMedia = true;
}

async function save() {
    editError.value = '';
    if (!form.body.trim() && !form.preview) { editError.value = 'A post needs some text or a photo.'; return; }

    const fd = new FormData();
    fd.append('_method', 'PATCH');                       // multipart uploads need POST + method override
    fd.append('body', form.body);
    fd.append('visibility', form.visibility);
    fd.append('remove_media', form.removeMedia ? '1' : '0');
    if (form.file) fd.append('media', form.file);

    saving.value = true;
    try {
        const { data } = await http.post(`/posts/${props.post.id}`, fd);
        props.post.body = data.body;
        props.post.media_url = data.media_url;
        props.post.visibility = data.visibility;
        props.post.edited = true;
        editing.value = false;
        ui.toast('Post updated.');
    } catch (e) { editError.value = errorText(e, 'Could not save your changes.'); }
    saving.value = false;
}

const visibilityLabel = { public: 'Public', private: 'Only me' };
</script>

<template>
    <article class="card overflow-hidden">
        <!-- header -->
        <div class="flex items-center gap-3 p-5 pb-0">
            <RouterLink :to="`/users/${post.user.id}`"><UserAvatar :user="post.user" /></RouterLink>
            <div class="min-w-0 flex-1">
                <RouterLink :to="`/users/${post.user.id}`" class="font-bold hover:text-gold-300">{{ post.user.name }}</RouterLink>
                <p class="text-xs text-ink-300">
                    <RouterLink :to="`/posts/${post.id}`" class="hover:text-gold-300 hover:underline" title="Open post">{{ timeAgo(post.created_at) }}</RouterLink>
                    <span v-if="post.visibility !== 'friends'" class="ml-1 rounded-full border border-ink-700 px-2 py-0.5">{{ visibilityLabel[post.visibility] }}</span>
                    <span v-if="post.edited" class="ml-1 italic">edited</span>
                </p>
            </div>
            <div v-if="post.mine" class="flex items-center gap-1">
                <button type="button" class="rounded-full p-2 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Edit post" title="Edit post" @click="startEdit">
                    <AppIcon name="edit" class="h-4 w-4" />
                </button>
                <button type="button" class="rounded-full p-2 text-ink-300 transition hover:bg-ink-800 hover:text-red-400" aria-label="Delete post" title="Delete post" @click="remove">
                    <AppIcon name="trash" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- content -->
        <div v-if="!editing">
            <p v-if="post.body" class="max-w-[62ch] whitespace-pre-line break-words px-5 pt-4 leading-relaxed">{{ post.body }}</p>
            <a v-if="post.media_url" :href="post.media_url" target="_blank" rel="noopener" class="mx-5 mt-4 block overflow-hidden rounded-xl border border-ink-700">
                <img :src="post.media_url" :alt="`Photo shared by ${post.user.name}`" loading="lazy" class="max-h-[460px] w-full object-cover">
            </a>
        </div>

        <!-- inline editor -->
        <form v-else class="px-5 pt-4" @submit.prevent="save" @keydown.esc="editing = false" @keydown.ctrl.enter="save" @keydown.meta.enter="save">
            <div class="rounded-2xl border border-gold-500/40 bg-ink-950/40 p-4">
                <textarea ref="textarea" v-model="form.body" rows="3" maxlength="5000" class="input resize-none overflow-hidden" placeholder="What's on your mind?" @input="grow"></textarea>
                <p class="mt-1 text-right text-xs text-ink-300">{{ form.body.length }} / 5000</p>

                <div v-if="form.preview" class="relative mt-3">
                    <img :src="form.preview" alt="Photo preview" class="max-h-72 w-full rounded-xl border border-ink-700 object-cover">
                    <button type="button" class="absolute right-2 top-2 rounded-full bg-ink-950/80 p-1.5 hover:text-gold-300" aria-label="Remove photo" @click="dropPhoto">
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
                            <AppIcon name="image" class="h-4 w-4" /> {{ form.preview ? 'Change photo' : 'Add photo' }}
                            <input type="file" accept="image/*" class="hidden" @change="pickFile">
                        </label>
                        <select v-model="form.visibility" class="rounded-xl border border-ink-700 bg-ink-800 px-3 py-2 text-sm text-ink-200 focus:border-gold-500 focus:outline-none" aria-label="Who can see this">
                            <option value="friends">Friends</option>
                            <option value="public">Public</option>
                            <option value="private">Only me</option>
                        </select>
                    </div>
                    <span class="text-xs text-ink-300">Esc to cancel, Ctrl+Enter to save</span>
                </div>
            </div>
            <p v-if="editError" class="mt-2 text-sm text-red-300" role="alert">{{ editError }}</p>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" class="btn-ghost" @click="editing = false">Cancel</button>
                <button class="btn-gold" :disabled="saving">{{ saving ? 'Saving…' : 'Save changes' }}</button>
            </div>
        </form>

        <!-- counts -->
        <div class="flex gap-4 px-5 pt-4 text-sm text-ink-300">
            <span>{{ post.likes_count }} likes</span>
            <span>{{ post.comments_count }} comments</span>
        </div>

        <!-- actions -->
        <div class="mx-3 mt-3 grid grid-cols-3 border-t border-ink-700 py-1.5">
            <button type="button" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold transition hover:bg-ink-800 hover:text-gold-300"
                    :class="post.liked ? 'liked' : 'text-ink-300'" @click="like">
                <AppIcon name="heart" /> Like
            </button>
            <button type="button" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300"
                    :aria-expanded="showComments" @click="toggleComments">
                <AppIcon name="chat" /> Comment
            </button>
            <button type="button" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" @click="share">
                <AppIcon name="share" /> {{ shareLabel }}
            </button>
        </div>

        <!-- comments open under the post and load once -->
        <CommentsPanel v-if="commentsLoaded" v-show="showComments" :post="post" />
    </article>
</template>

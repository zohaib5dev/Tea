<script setup>
import { reactive, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import { firstName } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const emit = defineEmits(['posted']);
const auth = useAuth();
const ui = useUi();

const dialog = ref(null);
const textarea = ref(null);
const busy = ref(false);
const error = ref('');
const form = reactive({ body: '', visibility: 'friends', file: null, preview: null });

function open() {
    dialog.value.showModal();
    textarea.value?.focus();
}
defineExpose({ open });

function close() { dialog.value.close(); }

function pickFile(e) {
    const f = e.target.files[0];
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) { ui.toast('Please choose a photo under 5 MB.'); e.target.value = ''; return; }
    form.file = f;
    form.preview = URL.createObjectURL(f);
}

function clearFile() { form.file = null; form.preview = null; }

async function submit() {
    error.value = '';
    if (!form.body.trim() && !form.file) { error.value = 'Write something or add a photo first.'; return; }

    const fd = new FormData();
    fd.append('body', form.body);
    fd.append('visibility', form.visibility);
    if (form.file) fd.append('media', form.file);

    busy.value = true;
    try {
        const { data } = await http.post('/posts', fd);
        emit('posted', data);
        form.body = ''; form.file = null; form.preview = null;
        close();
        ui.toast('Your post is live.');
    } catch (e) { error.value = errorText(e, 'Could not publish your post.'); }
    busy.value = false;
}
</script>

<template>
    <dialog ref="dialog" class="w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-ink-700 bg-ink-900 p-0 backdrop:bg-black/70 backdrop:backdrop-blur-sm"
            @click="(e) => e.target === dialog && close()">
        <form @submit.prevent="submit">
            <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                <h2 class="font-display text-2xl font-bold text-gold-300">Create post</h2>
                <button type="button" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300" aria-label="Close" @click="close"><AppIcon name="x" class="h-5 w-5" /></button>
            </div>

            <div class="space-y-4 p-5">
                <div class="flex items-center gap-3">
                    <UserAvatar :user="auth.user" size="sm" />
                    <div>
                        <p class="text-sm font-bold">{{ auth.user.name }}</p>
                        <select v-model="form.visibility" class="mt-0.5 rounded-lg border border-ink-700 bg-ink-800 px-2 py-1 text-xs text-ink-200 focus:border-gold-500 focus:outline-none" aria-label="Who can see this">
                            <option value="friends">Friends</option>
                            <option value="public">Public</option>
                            <option value="private">Only me</option>
                        </select>
                    </div>
                </div>

                <textarea ref="textarea" v-model="form.body" rows="5" maxlength="5000" class="input resize-none" :placeholder="`What's on your mind, ${firstName(auth.user.name)}?`"></textarea>

                <div v-if="form.preview" class="relative">
                    <img :src="form.preview" alt="Selected photo" class="max-h-56 w-full rounded-xl border border-ink-700 object-cover">
                    <button type="button" class="absolute right-2 top-2 rounded-full bg-ink-950/80 p-1.5 hover:text-gold-300" aria-label="Remove photo" @click="clearFile"><AppIcon name="x" class="h-4 w-4" /></button>
                </div>

                <p v-if="error" class="text-sm text-red-300" role="alert">{{ error }}</p>
            </div>

            <div class="flex items-center justify-between border-t border-ink-700 px-5 py-4">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
                    <AppIcon name="image" class="h-4 w-4" /> Add photo
                    <input type="file" accept="image/*" class="hidden" @change="pickFile">
                </label>
                <button class="btn-gold" :disabled="busy">{{ busy ? 'Posting…' : 'Post' }}</button>
            </div>
        </form>
    </dialog>
</template>

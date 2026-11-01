<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http, { errorText } from '@/lib/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import { useFriendActions } from '@/composables/friends';
import AppIcon from '@/components/AppIcon.vue';
import PostCard from '@/components/PostCard.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const ui = useUi();
const actions = useFriendActions();

const id = Number(route.params.id);
const isMe = id === auth.user.id;

const profile = ref(null);          // { user, friendship, friends_count, posts_count, photos_count }
const posts = ref([]);
const photos = ref([]);
const nextPage = ref(1);
const loadingPosts = ref(false);
const tab = ref(location.hash === '#photos' ? 'photos' : 'posts');
const photosLoaded = ref(false);

async function loadProfile() {
    try { profile.value = (await http.get(`/users/${id}`)).data; }
    catch (e) { ui.toast(errorText(e, 'This profile is not available.')); router.replace('/feed'); }
}

async function loadPosts() {
    if (loadingPosts.value || !nextPage.value) return;
    loadingPosts.value = true;
    try {
        const { data } = await http.get(`/users/${id}/posts`, { params: { page: nextPage.value } });
        posts.value.push(...data.data);
        nextPage.value = data.next_page;
    } catch (e) { ui.toast(errorText(e)); }
    loadingPosts.value = false;
}

async function loadPhotos() {
    if (photosLoaded.value) return;
    photos.value = (await http.get(`/users/${id}/photos`)).data;
    photosLoaded.value = true;
}

function openTab(name) {
    tab.value = name;
    history.replaceState(null, '', name === 'photos' ? '#photos' : location.pathname);
    if (name === 'photos') loadPhotos();
}

onMounted(async () => {
    await Promise.all([loadProfile(), loadPosts()]);
    if (tab.value === 'photos') loadPhotos();
});

const user = computed(() => profile.value?.user);
const friendship = computed(() => profile.value?.friendship);

// ----- friend buttons -----
async function act(fn) { if (await fn()) loadProfile(); }
const addFriend = () => act(() => actions.add(id));
const cancelRequest = () => act(() => actions.remove(id));
const unfriend = () => { if (confirm('Remove this friend?')) act(() => actions.remove(id)); };
const accept = () => act(() => actions.accept(friendship.value.id));
const decline = () => act(() => actions.decline(friendship.value.id));

// ----- cover / avatar upload -----
async function uploadPhoto(type, e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 4 * 1024 * 1024) { ui.toast('Please choose a photo under 4 MB.'); e.target.value = ''; return; }

    const fd = new FormData();
    fd.append('type', type);
    fd.append('photo', file);
    ui.toast('Uploading…');

    try {
        const { data } = await http.post('/profile/photo', fd);
        if (type === 'cover') profile.value.user.cover_url = data.url;
        else { profile.value.user.avatar_url = data.url; auth.user.avatar_url = data.url; }
        ui.toast(type === 'cover' ? 'Cover photo updated.' : 'Profile picture updated.');
    } catch (err) { ui.toast(errorText(err, 'Upload failed.')); }
    e.target.value = '';
}

// ----- edit name and bio -----
const editDialog = ref(null);
const edit = reactive({ name: '', bio: '', error: '', busy: false });

function openEdit() {
    edit.name = user.value.name; edit.bio = user.value.bio || ''; edit.error = '';
    editDialog.value.showModal();
}

async function saveEdit() {
    edit.busy = true; edit.error = '';
    try {
        const { data } = await http.patch('/profile', { name: edit.name, bio: edit.bio });
        profile.value.user.name = data.name;
        profile.value.user.bio = data.bio;
        auth.user.name = data.name;
        editDialog.value.close();
        ui.toast('Profile updated.');
    } catch (e) { edit.error = errorText(e, 'Could not save.'); }
    edit.busy = false;
}

const tabClass = (name) => (tab.value === name ? 'bg-gold-grad text-ink-950 shadow' : 'text-ink-300 hover:bg-ink-800');
</script>

<template>
    <div v-if="!profile" class="card mx-auto max-w-3xl px-6 py-14 text-center text-ink-300">Loading profile…</div>

    <div v-else class="mx-auto max-w-5xl space-y-6">
        <!-- header -->
        <section class="card overflow-hidden">
            <div class="relative h-44 bg-gradient-to-br from-gold-800 via-ink-800 to-ink-900 bg-cover bg-center sm:h-60"
                 :style="user.cover_url ? { backgroundImage: `url('${user.cover_url}')` } : null">
                <div class="absolute inset-0 bg-gradient-to-t from-ink-900/60 to-transparent"></div>
                <label v-if="isMe" class="absolute right-4 top-4 flex cursor-pointer items-center gap-2 rounded-full border border-ink-700 bg-ink-950/70 px-4 py-2 text-xs font-semibold backdrop-blur transition hover:border-gold-500 hover:text-gold-300">
                    <AppIcon name="camera" class="h-4 w-4" /> Change cover
                    <input type="file" accept="image/*" class="hidden" @change="uploadPhoto('cover', $event)">
                </label>
            </div>

            <div class="px-6 pb-6 sm:px-8">
                <div class="-mt-16 flex flex-wrap items-end justify-between gap-4">
                    <div class="relative">
                        <UserAvatar :user="user" size="xl" :online="user.is_online" />
                        <label v-if="isMe" class="absolute bottom-1 right-1 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-gold-grad text-ink-950 shadow-lg ring-2 ring-ink-900 transition hover:scale-105" title="Change profile picture">
                            <AppIcon name="camera" class="h-4 w-4" />
                            <input type="file" accept="image/*" class="hidden" @change="uploadPhoto('avatar', $event)">
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-4">
                        <button v-if="isMe" class="btn-ghost" @click="openEdit"><AppIcon name="edit" class="h-4 w-4" /> Edit profile</button>

                        <button v-else-if="!friendship" class="btn-gold" @click="addFriend"><AppIcon name="plus" class="h-4 w-4" /> Add friend</button>

                        <template v-else-if="friendship.status === 'accepted'">
                            <button class="btn-gold" @click="actions.message(id)"><AppIcon name="chat" class="h-4 w-4" /> Message</button>
                            <button class="btn-ghost" @click="unfriend">Unfriend</button>
                        </template>

                        <template v-else-if="friendship.status === 'pending' && friendship.i_sent">
                            <span class="rounded-full border border-ink-700 px-4 py-2 text-sm text-ink-300">Request sent</span>
                            <button class="btn-ghost" @click="cancelRequest">Cancel</button>
                        </template>

                        <template v-else-if="friendship.status === 'pending'">
                            <button class="btn-gold" @click="accept"><AppIcon name="check" class="h-4 w-4" /> Accept</button>
                            <button class="btn-ghost" @click="decline">Decline</button>
                        </template>
                    </div>
                </div>

                <h1 class="mt-4 font-display text-4xl font-bold sm:text-5xl">{{ user.name }}</h1>
                <p v-if="user.bio" class="mt-1 max-w-prose text-ink-200">{{ user.bio }}</p>

                <dl class="mt-6 grid max-w-sm grid-cols-3 gap-3">
                    <div v-for="s in [['Friends', profile.friends_count], ['Posts', profile.posts_count], ['Photos', profile.photos_count]]" :key="s[0]"
                         class="rounded-2xl border border-ink-700 bg-ink-950/40 px-4 py-3 text-center">
                        <dd class="font-display text-3xl font-bold text-gold-300">{{ s[1] }}</dd>
                        <dt class="text-xs text-ink-300">{{ s[0] }}</dt>
                    </div>
                </dl>
            </div>
        </section>

        <!-- tabs -->
        <section aria-label="Profile content">
            <div class="card mb-6 grid grid-cols-2 gap-1 p-1.5" role="tablist">
                <button role="tab" class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tabClass('posts')" @click="openTab('posts')">
                    <AppIcon name="chat" class="h-4 w-4" /> Posts <span class="rounded-full bg-ink-950/20 px-2 py-0.5 text-xs">{{ profile.posts_count }}</span>
                </button>
                <button role="tab" class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tabClass('photos')" @click="openTab('photos')">
                    <AppIcon name="image" class="h-4 w-4" /> Photos <span class="rounded-full bg-ink-950/20 px-2 py-0.5 text-xs">{{ profile.photos_count }}</span>
                </button>
            </div>

            <!-- every post -->
            <div v-show="tab === 'posts'" class="mx-auto max-w-5xl space-y-6">
                <div v-if="!posts.length && !loadingPosts" class="card px-6 py-14 text-center">
                    <p class="font-display text-3xl font-bold text-gold-300">Nothing to show yet</p>
                    <p class="mt-1 text-ink-300">{{ isMe ? 'Share your first post from the feed.' : 'Posts you can see will appear here.' }}</p>
                    <RouterLink v-if="isMe" to="/feed" class="btn-gold mt-6">Go to feed</RouterLink>
                </div>
                <PostCard v-for="p in posts" :key="p.id" :post="p" @deleted="(pid) => (posts = posts.filter((x) => x.id !== pid))" />
                <div v-if="nextPage" class="text-center">
                    <button class="btn-ghost" :disabled="loadingPosts" @click="loadPosts">{{ loadingPosts ? 'Loading…' : 'Load more posts' }}</button>
                </div>
            </div>

            <!-- only the pictures -->
            <div v-show="tab === 'photos'">
                <div v-if="photosLoaded && !photos.length" class="card px-6 py-14 text-center">
                    <p class="font-display text-3xl font-bold text-gold-300">No photos yet</p>
                    <p class="mt-1 text-ink-300">{{ isMe ? 'Pictures you add to your posts will appear here.' : 'Photos you can see will appear here.' }}</p>
                </div>
                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <RouterLink v-for="ph in photos" :key="ph.id" :to="`/posts/${ph.id}`" class="group relative block aspect-square overflow-hidden rounded-2xl border border-ink-700 bg-ink-800" aria-label="Open the post with this photo">
                        <img :src="ph.url" alt="Photo from a post" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink-950/60 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                    </RouterLink>
                </div>
            </div>
        </section>
    </div>

    <!-- edit profile dialog -->
    <dialog v-if="isMe" ref="editDialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-ink-700 bg-ink-900 p-0 backdrop:bg-black/70 backdrop:backdrop-blur-sm"
            @click="(e) => e.target === editDialog && editDialog.close()">
        <form @submit.prevent="saveEdit">
            <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                <h2 class="font-display text-2xl font-bold text-gold-300">Edit profile</h2>
                <button type="button" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300" aria-label="Close" @click="editDialog.close()"><AppIcon name="x" class="h-5 w-5" /></button>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label for="edit-name" class="mb-1.5 block text-sm font-semibold text-ink-200">Name</label>
                    <input id="edit-name" v-model="edit.name" required maxlength="255" class="input">
                </div>
                <div>
                    <label for="edit-bio" class="mb-1.5 block text-sm font-semibold text-ink-200">Bio</label>
                    <textarea id="edit-bio" v-model="edit.bio" rows="3" maxlength="255" class="input resize-none" placeholder="Tell people a little about you"></textarea>
                </div>
                <p v-if="edit.error" class="text-sm text-red-300" role="alert">{{ edit.error }}</p>
            </div>
            <div class="flex justify-end gap-2 border-t border-ink-700 px-5 py-4">
                <button type="button" class="btn-ghost" @click="editDialog.close()">Cancel</button>
                <button class="btn-gold" :disabled="edit.busy">{{ edit.busy ? 'Saving…' : 'Save' }}</button>
            </div>
        </form>
    </dialog>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import http, { errorText } from '@/lib/http';
import { useInbox } from '@/stores/inbox';
import { useUi } from '@/stores/ui';
import { useFriendActions } from '@/composables/friends';
import { shortAgo } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const inbox = useInbox();
const ui = useUi();
const router = useRouter();
const actions = useFriendActions();

const search = ref('');
const loading = ref(!inbox.loaded);
const group = reactive({ open: false, title: '', ids: [], busy: false });

onMounted(async () => {
    try { await inbox.load(true); } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
});

const shown = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? inbox.friends.filter((f) => f.name.toLowerCase().includes(q)) : inbox.friends;
});

const open = (f) => (f.conversation_id ? router.push(`/messages/${f.conversation_id}`) : actions.message(f.id));

async function createGroup() {
    group.busy = true;
    try {
        const { data } = await http.post('/conversations', { title: group.title, user_ids: group.ids });
        router.push(`/messages/${data.conversation_id}`);
    } catch (e) { ui.toast(errorText(e)); }
    group.busy = false;
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section aria-label="Your conversations">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <h1 class="font-display text-4xl font-bold">Messages</h1>
                    <span v-if="inbox.totalUnread" class="rounded-full bg-gold-grad px-3 py-1 text-xs font-bold text-ink-950">{{ inbox.totalUnread }} new</span>
                </div>
                <input v-if="inbox.friends.length" v-model="search" type="search" placeholder="Search friends" aria-label="Search friends" class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-64">
            </div>

            <div v-if="loading" class="card px-6 py-14 text-center text-ink-300">Loading…</div>

            <div v-else-if="!inbox.friends.length" class="card px-6 py-14 text-center">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><AppIcon name="chat" class="h-7 w-7" /></div>
                <p class="font-display text-3xl font-bold text-gold-300">No one to message yet</p>
                <p class="mx-auto mt-2 max-w-sm text-ink-300">Add friends first, then start a conversation here.</p>
                <RouterLink to="/friends" class="btn-gold mt-6">Find friends</RouterLink>
            </div>

            <template v-else>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button v-for="f in shown" :key="f.id" type="button"
                            class="card flex h-full w-full items-center gap-3 px-4 py-3.5 text-left transition hover:-translate-y-0.5 hover:border-gold-500/50"
                            :class="f.unread ? '!border-gold-500/60 !bg-gold-900/20' : ''" @click="open(f)">
                        <UserAvatar :user="f" :online="f.is_online" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="truncate font-bold">{{ f.name }}</p>
                                <span v-if="f.last_message_at" class="shrink-0 text-xs" :class="f.unread ? 'font-semibold text-gold-300' : 'text-ink-300'">{{ shortAgo(f.last_message_at) }}</span>
                            </div>
                            <p class="truncate text-sm" :class="f.unread ? 'font-semibold text-ink-50' : 'text-ink-300'">{{ f.preview || 'No messages yet. Say hello.' }}</p>
                        </div>
                        <span v-if="f.unread" class="grid h-6 min-w-6 shrink-0 place-items-center rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950">{{ f.unread > 99 ? '99+' : f.unread }}</span>
                    </button>
                </div>
                <p v-if="!shown.length" class="card mt-4 px-6 py-10 text-center text-ink-300">No friends match your search.</p>
            </template>
        </section>

        <aside class="space-y-6">
            <section v-if="inbox.groups.length" class="card overflow-hidden" aria-label="Group chats">
                <div class="border-b border-ink-700 bg-ink-800/50 px-5 py-4"><h2 class="font-display text-2xl font-bold text-gold-300">Group chats</h2></div>
                <div class="divide-y divide-ink-700">
                    <RouterLink v-for="g in inbox.groups" :key="g.id" :to="`/messages/${g.id}`" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-ink-800">
                        <UserAvatar :user="{ name: g.title, avatar_url: g.avatar_url }" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ g.title }}</p>
                            <p class="truncate text-xs" :class="g.unread ? 'font-semibold text-ink-50' : 'text-ink-300'">{{ g.preview || 'No messages yet' }}</p>
                        </div>
                        <span v-if="g.unread" class="grid h-5 min-w-5 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950">{{ g.unread }}</span>
                    </RouterLink>
                </div>
            </section>

            <section v-if="inbox.friends.length > 1" class="card p-5">
                <button type="button" class="flex w-full items-center justify-between font-display text-2xl font-bold text-gold-300" @click="group.open = !group.open">
                    New group chat <AppIcon name="plus" class="h-5 w-5 transition" :class="group.open ? 'rotate-45' : ''" />
                </button>
                <form v-if="group.open" class="mt-4 space-y-3" @submit.prevent="createGroup">
                    <input v-model="group.title" required maxlength="100" class="input" placeholder="Group name">
                    <div class="max-h-48 space-y-1 overflow-y-auto">
                        <label v-for="f in inbox.friends" :key="f.id" class="flex cursor-pointer items-center gap-3 rounded-xl p-2 hover:bg-ink-800">
                            <input v-model="group.ids" type="checkbox" :value="f.id" class="h-4 w-4 accent-[#d4a63a]">
                            <span class="text-sm">{{ f.name }}</span>
                        </label>
                    </div>
                    <button class="btn-gold w-full" :disabled="group.busy || !group.ids.length">{{ group.busy ? 'Creating…' : 'Create group' }}</button>
                </form>
            </section>
        </aside>
    </div>
</template>

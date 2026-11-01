<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useInbox } from '@/stores/inbox';
import { useFriendActions } from '@/composables/friends';
import { shortAgo } from '@/lib/format';
import UserAvatar from '@/components/UserAvatar.vue';

const props = defineProps({ activeId: Number, startOnGroups: Boolean });
const inbox = useInbox();
const router = useRouter();
const actions = useFriendActions();

const tab = ref(props.startOnGroups ? 'groups' : 'friends');
const search = ref('');

const q = computed(() => search.value.trim().toLowerCase());
const friends = computed(() => inbox.friends.filter((f) => f.name.toLowerCase().includes(q.value)));
const groups = computed(() => inbox.groups.filter((g) => g.title.toLowerCase().includes(q.value)));

const open = (f) => (f.conversation_id ? router.push(`/messages/${f.conversation_id}`) : actions.message(f.id));
const tabClass = (name) => (tab.value === name ? 'bg-ink-700 text-gold-300 shadow-sm' : 'text-ink-300');
</script>

<template>
    <aside class="card hidden min-h-0 flex-col overflow-hidden lg:flex" aria-label="Conversations">
        <div class="border-b border-ink-700 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-2xl font-bold text-gold-300">Messages</h2>
                <RouterLink to="/messages" class="text-xs font-semibold text-ink-300 hover:text-gold-300">All &amp; new group</RouterLink>
            </div>
            <input v-model="search" type="search" placeholder="Search" aria-label="Search conversations" class="input !rounded-full !py-2 text-sm">

            <div class="mt-3 grid grid-cols-2 gap-1 rounded-xl bg-ink-800/70 p-1 ring-1 ring-ink-700/50" role="tablist">
                <button type="button" role="tab" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition" :class="tabClass('friends')" @click="tab = 'friends'">
                    Friends <span v-if="inbox.friendsUnread" class="rounded-full bg-gold-grad px-1.5 text-[10px] font-bold text-ink-950">{{ inbox.friendsUnread }}</span>
                </button>
                <button type="button" role="tab" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition" :class="tabClass('groups')" @click="tab = 'groups'">
                    Groups <span v-if="inbox.groupsUnread" class="rounded-full bg-gold-grad px-1.5 text-[10px] font-bold text-ink-950">{{ inbox.groupsUnread }}</span>
                </button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <!-- friends -->
            <div v-if="tab === 'friends'" class="divide-y divide-ink-700/60">
                <button v-for="f in friends" :key="f.id" type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-ink-800"
                        :class="f.conversation_id === activeId ? 'bg-ink-800' : f.unread ? 'bg-gold-900/10' : ''"
                        :aria-current="f.conversation_id === activeId ? 'true' : undefined" @click="open(f)">
                    <UserAvatar :user="f" size="sm" :online="f.is_online" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-bold">{{ f.name }}</p>
                            <span class="shrink-0 text-[11px]" :class="f.unread ? 'font-semibold text-gold-300' : 'text-ink-300'">{{ shortAgo(f.last_message_at) }}</span>
                        </div>
                        <p class="truncate text-xs" :class="f.unread ? 'font-semibold text-ink-50' : 'text-ink-300'">{{ f.preview || 'No messages yet' }}</p>
                    </div>
                    <span v-if="f.unread" class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950">{{ f.unread > 99 ? '99+' : f.unread }}</span>
                </button>
                <p v-if="!friends.length" class="px-5 py-10 text-center text-sm text-ink-300">
                    {{ q ? 'Nothing matches your search.' : 'No friends yet.' }}
                    <RouterLink v-if="!q" to="/friends" class="font-semibold text-gold-300 hover:underline">Find friends</RouterLink>
                </p>
            </div>

            <!-- groups -->
            <div v-else class="divide-y divide-ink-700/60">
                <RouterLink v-for="g in groups" :key="g.id" :to="`/messages/${g.id}`"
                            class="flex items-center gap-3 px-4 py-3 transition hover:bg-ink-800"
                            :class="g.id === activeId ? 'bg-ink-800' : g.unread ? 'bg-gold-900/10' : ''">
                    <UserAvatar :user="{ name: g.title, avatar_url: g.avatar_url }" size="sm" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-bold">{{ g.title }}</p>
                            <span class="shrink-0 text-[11px]" :class="g.unread ? 'font-semibold text-gold-300' : 'text-ink-300'">{{ shortAgo(g.last_message_at) }}</span>
                        </div>
                        <p class="truncate text-xs" :class="g.unread ? 'font-semibold text-ink-50' : 'text-ink-300'">{{ g.preview || 'No messages yet' }}</p>
                    </div>
                    <span v-if="g.unread" class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950">{{ g.unread }}</span>
                </RouterLink>
                <p v-if="!groups.length" class="px-5 py-10 text-center text-sm text-ink-300">
                    {{ q ? 'Nothing matches your search.' : 'No group chats yet.' }}
                    <RouterLink v-if="!q" to="/messages" class="font-semibold text-gold-300 hover:underline">Create one</RouterLink>
                </p>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { useFriendActions } from '@/composables/friends';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const ui = useUi();
const actions = useFriendActions();

const tab = ref('friends');
const friends = ref([]);
const requests = ref([]);
const suggestions = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await http.get('/sidebar');
        friends.value = data.friends;
        requests.value = data.requests;
        suggestions.value = data.suggestions;
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
});

async function accept(r) { if (await actions.accept(r.id)) requests.value = requests.value.filter((x) => x.id !== r.id); }
async function decline(r) { if (await actions.decline(r.id)) requests.value = requests.value.filter((x) => x.id !== r.id); }

const added = ref(new Set());
async function add(u) { if (await actions.add(u.id)) added.value = new Set(added.value).add(u.id); }

const tabClass = (name) => (tab.value === name ? 'bg-ink-700 text-gold-300 shadow-sm ring-1 ring-gold-300/20' : 'text-ink-300 hover:text-white');
</script>

<template>
    <aside class="hidden lg:sticky lg:top-24 lg:flex lg:max-h-[calc(100vh-7rem)] lg:flex-col lg:gap-6 lg:self-start lg:overflow-y-auto" aria-label="Friends">
        <div class="card p-5">
            <div class="mb-4 grid grid-cols-3 gap-1 rounded-xl bg-ink-800/70 p-1 ring-1 ring-ink-700/50" role="tablist">
                <button type="button" role="tab" class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition" :class="tabClass('friends')" @click="tab = 'friends'">
                    Friends <span class="rounded-full bg-ink-900/60 px-1.5 py-0.5 text-[10px] font-bold">{{ friends.length }}</span>
                </button>
                <button type="button" role="tab" class="relative flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition" :class="tabClass('requests')" @click="tab = 'requests'">
                    Requests <span class="rounded-full bg-ink-900/60 px-1.5 py-0.5 text-[10px] font-bold">{{ requests.length }}</span>
                    <span v-if="requests.length" class="absolute right-1.5 top-1.5 h-1.5 w-1.5 animate-pulse rounded-full bg-gold-300"></span>
                </button>
                <button type="button" role="tab" class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition" :class="tabClass('discover')" @click="tab = 'discover'">Discover</button>
            </div>

            <p v-if="loading" class="py-6 text-center text-sm text-ink-300">Loading…</p>

            <!-- Friends -->
            <div v-else-if="tab === 'friends'">
                <div v-if="friends.length" class="grid grid-cols-2 gap-2">
                    <div v-for="f in friends" :key="f.id" class="group flex flex-col items-center gap-2 rounded-xl border border-ink-700/60 bg-ink-800/40 p-3 text-center transition hover:border-gold-300/30 hover:bg-ink-800">
                        <RouterLink :to="`/users/${f.id}`" class="flex w-full flex-col items-center gap-2">
                            <span class="relative">
                                <UserAvatar :user="f" :online="f.is_online" />
                                <span v-if="f.unread" class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-gold-grad px-1 text-[10px] font-bold text-ink-950 ring-2 ring-ink-900">{{ f.unread > 99 ? '99+' : f.unread }}</span>
                            </span>
                            <span class="w-full">
                                <span class="block truncate text-xs font-semibold text-white">{{ f.name }}</span>
                                <span class="mt-0.5 flex items-center justify-center gap-1 text-[10px] text-ink-300">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="f.is_online ? 'bg-emerald-400' : 'bg-ink-300'"></span>
                                    {{ f.is_online ? 'Online' : 'Offline' }}
                                </span>
                            </span>
                        </RouterLink>
                        <button class="btn-gold w-full !py-1 !text-[10px] transition" :class="f.unread ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'" @click="actions.message(f.id)">
                            <AppIcon name="chat" class="h-3 w-3" /> {{ f.unread ? f.unread + ' new' : 'Message' }}
                        </button>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-ink-700 py-8 text-center">
                    <p class="text-sm font-semibold text-white">No friends yet</p>
                    <p class="text-xs text-ink-300">Discover people in the Discover tab.</p>
                </div>
            </div>

            <!-- Requests -->
            <div v-else-if="tab === 'requests'">
                <div v-if="requests.length" class="space-y-2">
                    <div v-for="r in requests" :key="r.id" class="flex items-center gap-3 rounded-xl border border-ink-700/60 bg-ink-800/40 p-2.5 transition hover:border-gold-300/30 hover:bg-ink-800">
                        <RouterLink :to="`/users/${r.user.id}`" class="shrink-0"><UserAvatar :user="r.user" size="sm" :online="r.user.is_online" /></RouterLink>
                        <RouterLink :to="`/users/${r.user.id}`" class="min-w-0 flex-1 truncate text-sm font-semibold text-white hover:text-gold-300">{{ r.user.name }}</RouterLink>
                        <div class="flex shrink-0 gap-1.5">
                            <button class="grid h-8 w-8 place-items-center rounded-lg bg-gold-300 text-ink-900 transition hover:bg-gold-200" title="Accept" @click="accept(r)"><AppIcon name="check" class="h-4 w-4" /></button>
                            <button class="grid h-8 w-8 place-items-center rounded-lg border border-ink-700 bg-ink-800 text-ink-300 transition hover:border-red-400/40 hover:text-red-400" title="Decline" @click="decline(r)"><AppIcon name="x" class="h-4 w-4" /></button>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-ink-700 py-8 text-center">
                    <p class="text-sm font-semibold text-white">All caught up</p>
                    <p class="text-xs text-ink-300">No pending requests right now.</p>
                </div>
            </div>

            <!-- Discover -->
            <div v-else>
                <div v-if="suggestions.length" class="grid grid-cols-2 gap-2">
                    <div v-for="u in suggestions" :key="u.id" class="group flex flex-col items-center gap-2 rounded-xl border border-ink-700/60 bg-ink-800/40 p-3 text-center transition hover:border-gold-300/30 hover:bg-ink-800">
                        <RouterLink :to="`/users/${u.id}`" class="flex flex-col items-center gap-2">
                            <UserAvatar :user="u" />
                            <span class="w-full">
                                <span class="block truncate text-xs font-semibold text-white">{{ u.name }}</span>
                                <span class="text-[10px] text-ink-300">{{ u.mutual_friends_count || 0 }} mutual</span>
                            </span>
                        </RouterLink>
                        <button class="btn-gold w-full !py-1 !text-[10px] transition" :class="added.has(u.id) ? 'pointer-events-none opacity-60' : 'opacity-0 group-hover:opacity-100'" @click="add(u)">
                            {{ added.has(u.id) ? 'Requested' : 'Add friend' }}
                        </button>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-ink-700 py-8 text-center">
                    <p class="text-sm font-semibold text-white">No suggestions</p>
                    <p class="text-xs text-ink-300">Check back later for new people.</p>
                </div>
            </div>
        </div>
    </aside>
</template>

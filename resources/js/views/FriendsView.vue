<script setup>
import { computed, onMounted, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { useFriendActions } from '@/composables/friends';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const ui = useUi();
const actions = useFriendActions();

const loading = ref(true);
const friends = ref([]);
const incoming = ref([]);
const outgoing = ref([]);
const suggestions = ref([]);
const search = ref('');
const done = ref({});          // requests answered: id -> text
const added = ref(new Set());

onMounted(async () => {
    try {
        const { data } = await http.get('/friends');
        friends.value = data.friends; incoming.value = data.incoming;
        outgoing.value = data.outgoing; suggestions.value = data.suggestions;
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
});

const onlineCount = computed(() => friends.value.filter((f) => f.is_online).length);
const shown = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? friends.value.filter((f) => f.name.toLowerCase().includes(q)) : friends.value;
});

async function unfriend(f) {
    if (!confirm('Remove this friend?')) return;
    if (await actions.remove(f.id)) friends.value = friends.value.filter((x) => x.id !== f.id);
}
async function accept(r) { if (await actions.accept(r.id)) done.value = { ...done.value, [r.id]: 'You are now friends.' }; }
async function decline(r) { if (await actions.decline(r.id)) incoming.value = incoming.value.filter((x) => x.id !== r.id); }
async function cancel(r) { if (await actions.remove(r.user.id)) outgoing.value = outgoing.value.filter((x) => x.id !== r.id); }
async function add(u) { if (await actions.add(u.id)) added.value = new Set(added.value).add(u.id); }
</script>

<template>
    <div v-if="loading" class="card px-6 py-14 text-center text-ink-300">Loading friends…</div>

    <div v-else class="space-y-6">
        <!-- header with stats -->
        <section class="card relative overflow-hidden p-6 sm:p-8">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(600px_220px_at_100%_0%,rgb(var(--gold-500)/.2),transparent)]"></div>
            <div class="relative flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-400">Your circle</p>
                    <h1 class="mt-1 font-display text-4xl font-bold sm:text-5xl">Friends</h1>
                    <p class="mt-2 max-w-md text-ink-200">Keep up with the people who matter, find new ones, and answer requests.</p>
                </div>
                <dl class="flex gap-3">
                    <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                        <dd class="font-display text-3xl font-bold text-gold-300">{{ friends.length }}</dd><dt class="text-xs text-ink-300">Friends</dt>
                    </div>
                    <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                        <dd class="flex items-center justify-center gap-1.5 font-display text-3xl font-bold text-gold-300">
                            <span v-if="onlineCount" class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>{{ onlineCount }}
                        </dd><dt class="text-xs text-ink-300">Online</dt>
                    </div>
                    <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                        <dd class="font-display text-3xl font-bold text-gold-300">{{ incoming.length }}</dd><dt class="text-xs text-ink-300">Requests</dt>
                    </div>
                </dl>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-10">
                <!-- friends -->
                <section aria-label="Your friends">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-display text-3xl font-bold">All friends</h2>
                        <input v-if="friends.length" v-model="search" type="search" placeholder="Search friends" aria-label="Search friends" class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-64">
                    </div>

                    <div v-if="!friends.length" class="card px-6 py-14 text-center">
                        <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><AppIcon name="users" class="h-7 w-7" /></div>
                        <p class="font-display text-3xl font-bold text-gold-300">No friends yet</p>
                        <p class="mx-auto mt-2 max-w-sm text-ink-300">Add people from the suggestions below and they will show up here.</p>
                    </div>

                    <template v-else>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div v-for="f in shown" :key="f.id" class="card overflow-hidden transition hover:-translate-y-0.5 hover:border-gold-500/50" :class="f.unread ? '!border-gold-500/60' : ''">
                                <div class="px-4 py-5 text-center">
                                    <RouterLink :to="`/users/${f.id}`" class="inline-block">
                                        <span class="relative inline-block">
                                            <UserAvatar :user="f" size="lg" :online="f.is_online" />
                                            <span v-if="f.unread" class="absolute -right-2 -top-2 grid h-6 min-w-6 place-items-center rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950 ring-2 ring-ink-900">{{ f.unread > 99 ? '99+' : f.unread }}</span>
                                        </span>
                                    </RouterLink>
                                    <RouterLink :to="`/users/${f.id}`" class="mt-2 block truncate font-bold hover:text-gold-300">{{ f.name }}</RouterLink>
                                    <p class="mt-0.5 text-xs" :class="f.is_online ? 'text-emerald-400' : 'text-ink-300'">{{ f.is_online ? 'Online now' : 'Offline' }}</p>
                                    <p v-if="f.unread" class="mt-1 text-xs font-semibold text-gold-300">{{ f.unread }} new {{ f.unread === 1 ? 'message' : 'messages' }}</p>
                                    <div class="mt-4 flex gap-2">
                                        <button class="btn-gold flex-1 !px-3 !py-2 !text-xs" @click="actions.message(f.id)"><AppIcon name="chat" class="h-4 w-4" /> {{ f.unread ? 'Reply' : 'Message' }}</button>
                                        <button class="btn-ghost !p-2" :aria-label="`Unfriend ${f.name}`" title="Unfriend" @click="unfriend(f)"><AppIcon name="x" class="h-4 w-4" /></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-if="!shown.length" class="card mt-4 px-6 py-10 text-center text-ink-300">No friends match your search.</p>
                    </template>
                </section>

                <!-- suggestions -->
                <section aria-label="People you may know">
                    <div class="mb-4">
                        <h2 class="font-display text-3xl font-bold">People you may know</h2>
                        <p class="text-sm text-ink-300">Suggested from your mutual friends.</p>
                    </div>
                    <div v-if="!suggestions.length" class="card px-6 py-10 text-center text-ink-300">No suggestions right now. Check back soon.</div>
                    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div v-for="u in suggestions" :key="u.id" class="card flex flex-col items-center p-5 text-center transition hover:-translate-y-0.5 hover:border-gold-500/50">
                            <RouterLink :to="`/users/${u.id}`"><UserAvatar :user="u" size="lg" /></RouterLink>
                            <RouterLink :to="`/users/${u.id}`" class="mt-3 block max-w-full truncate font-bold hover:text-gold-300">{{ u.name }}</RouterLink>
                            <p class="mt-0.5 text-xs text-ink-300">{{ u.mutual_friends_count ? `${u.mutual_friends_count} mutual ${u.mutual_friends_count === 1 ? 'friend' : 'friends'}` : 'New to your circle' }}</p>
                            <button class="btn-ghost mt-4 w-full !py-2 !text-xs" :class="added.has(u.id) ? 'pointer-events-none opacity-60' : ''" @click="add(u)">
                                <AppIcon name="plus" class="h-4 w-4" /> {{ added.has(u.id) ? 'Requested' : 'Add friend' }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <!-- requests -->
            <aside :class="incoming.length ? 'order-first lg:order-none' : ''">
                <section class="card overflow-hidden" aria-label="Friend requests">
                    <div class="flex items-center justify-between border-b border-ink-700 bg-ink-800/50 px-5 py-4">
                        <h2 class="font-display text-2xl font-bold text-gold-300">Requests</h2>
                        <span v-if="incoming.length" class="rounded-full bg-gold-grad px-2.5 py-0.5 text-xs font-bold text-ink-950">{{ incoming.length }}</span>
                    </div>

                    <div class="p-3">
                        <template v-if="incoming.length">
                            <div v-for="r in incoming" :key="r.id" class="flex flex-wrap items-center gap-3 rounded-xl p-2 transition hover:bg-ink-800">
                                <p v-if="done[r.id]" class="w-full p-2 text-sm text-gold-300">{{ done[r.id] }}</p>
                                <template v-else>
                                    <RouterLink :to="`/users/${r.user.id}`"><UserAvatar :user="r.user" size="sm" /></RouterLink>
                                    <RouterLink :to="`/users/${r.user.id}`" class="min-w-0 flex-1 truncate text-sm font-semibold hover:text-gold-300">{{ r.user.name }}</RouterLink>
                                    <div class="flex gap-2">
                                        <button class="btn-gold !px-3.5 !py-1.5 !text-xs" @click="accept(r)">Accept</button>
                                        <button class="btn-ghost !px-3.5 !py-1.5 !text-xs" @click="decline(r)">Decline</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <div v-else class="px-4 py-8 text-center">
                            <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full border border-ink-700 text-gold-400"><AppIcon name="check" class="h-5 w-5" /></div>
                            <p class="font-semibold">All caught up</p>
                            <p class="text-sm text-ink-300">No new requests right now.</p>
                        </div>
                    </div>

                    <div v-if="outgoing.length" class="border-t border-ink-700 p-3">
                        <h3 class="px-2 pb-2 pt-1 text-xs font-bold uppercase tracking-wider text-ink-300">Sent by you</h3>
                        <div v-for="r in outgoing" :key="r.id" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-ink-800">
                            <RouterLink :to="`/users/${r.user.id}`"><UserAvatar :user="r.user" size="sm" /></RouterLink>
                            <div class="min-w-0 flex-1">
                                <RouterLink :to="`/users/${r.user.id}`" class="block truncate text-sm font-semibold hover:text-gold-300">{{ r.user.name }}</RouterLink>
                                <p class="text-xs text-ink-300">Pending</p>
                            </div>
                            <button class="btn-ghost !px-3 !py-1.5 !text-xs" @click="cancel(r)">Cancel</button>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

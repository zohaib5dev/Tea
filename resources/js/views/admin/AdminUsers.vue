<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import http, { errorText } from '@/lib/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import { timeAgo } from '@/lib/format';
import UserAvatar from '@/components/UserAvatar.vue';
import Pager from '@/components/admin/Pager.vue';

const auth = useAuth();
const ui = useUi();

const users = ref([]);
const meta = reactive({ page: 1, last_page: 1, total: 0 });
const search = ref('');
const filter = ref('all');
const loading = ref(true);

const filters = [['all', 'All'], ['online', 'Online'], ['admins', 'Admins'], ['banned', 'Suspended']];

async function load(page = 1) {
    loading.value = true;
    try {
        const { data } = await http.get('/admin/users', { params: { page, search: search.value || undefined, filter: filter.value } });
        users.value = data.data;
        Object.assign(meta, { page: data.page, last_page: data.last_page, total: data.total });
    } catch (e) { ui.toast(errorText(e)); }
    loading.value = false;
}

let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 300); });
watch(filter, () => load(1));
onMounted(() => load());

// Replace one row with the updated user from the server
const update = (u) => { const i = users.value.findIndex((x) => x.id === u.id); if (i !== -1) users.value[i] = { ...users.value[i], ...u }; };
const isSelf = (u) => u.id === auth.user.id;

async function run(request, doneText) {
    try { const { data } = await request(); if (data?.id) update(data); ui.toast(doneText); return true; }
    catch (e) { ui.toast(errorText(e)); return false; }
}

// ----- suspend with a reason -----
const dialog = ref(null);
const ban = reactive({ user: null, reason: '', busy: false });

function askBan(u) { ban.user = u; ban.reason = ''; dialog.value.showModal(); }

async function confirmBan() {
    ban.busy = true;
    if (await run(() => http.post(`/admin/users/${ban.user.id}/ban`, { reason: ban.reason }), `${ban.user.name} is suspended.`)) dialog.value.close();
    ban.busy = false;
}

const unban = (u) => run(() => http.post(`/admin/users/${u.id}/unban`), `${u.name} can log in again.`);
const toggleAdmin = (u) => run(() => http.post(`/admin/users/${u.id}/admin`, { value: !u.is_admin }), u.is_admin ? 'Admin rights removed.' : `${u.name} is now an admin.`);

async function remove(u) {
    if (!confirm(`Permanently delete ${u.name} and everything they posted? This cannot be undone.`)) return;
    try {
        await http.delete(`/admin/users/${u.id}`);
        users.value = users.value.filter((x) => x.id !== u.id);
        meta.total--;
        ui.toast('User deleted.');
    } catch (e) { ui.toast(errorText(e)); }
}
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-4xl font-bold sm:text-5xl">Users</h1>
                <p class="mt-1 text-ink-300">{{ meta.total }} {{ meta.total === 1 ? 'person' : 'people' }}</p>
            </div>
            <input v-model="search" type="search" placeholder="Search name or email" aria-label="Search users" class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-72">
        </div>

        <div class="card inline-flex gap-1 p-1.5" role="tablist">
            <button v-for="f in filters" :key="f[0]" role="tab" class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="filter === f[0] ? 'bg-gold-grad text-ink-950 shadow' : 'text-ink-300 hover:bg-ink-800'" @click="filter = f[0]">{{ f[1] }}</button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-ink-700 bg-ink-800/50 text-xs uppercase tracking-wider text-ink-300">
                        <tr><th class="px-5 py-3">User</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Joined</th><th class="px-3 py-3">Posts</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        <tr v-for="u in users" :key="u.id" class="transition hover:bg-ink-800/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <UserAvatar :user="u" size="sm" :online="u.is_online" />
                                    <div class="min-w-0">
                                        <RouterLink :to="`/users/${u.id}`" class="block truncate font-bold hover:text-gold-300">{{ u.name }}</RouterLink>
                                        <p class="truncate text-xs text-ink-300">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-if="u.is_admin" class="rounded-full bg-gold-grad px-2.5 py-0.5 text-xs font-bold text-ink-950">Admin</span>
                                    <span v-if="u.banned" class="rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-bold text-red-300" :title="u.ban_reason || ''">Suspended</span>
                                    <span v-else-if="u.is_online" class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-bold text-emerald-300">Online</span>
                                    <span v-else-if="!u.is_admin" class="text-xs text-ink-300">Active</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs text-ink-300">{{ timeAgo(u.created_at) }}</td>
                            <td class="px-3 py-3">{{ u.posts_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    <template v-if="isSelf(u)"><span class="text-xs text-ink-300">That is you</span></template>
                                    <template v-else>
                                        <button class="btn-ghost !px-3 !py-1 !text-xs" @click="toggleAdmin(u)">{{ u.is_admin ? 'Remove admin' : 'Make admin' }}</button>
                                        <button v-if="u.banned" class="btn-ghost !px-3 !py-1 !text-xs" @click="unban(u)">Unsuspend</button>
                                        <button v-else class="btn-ghost !px-3 !py-1 !text-xs" :disabled="u.is_admin" :title="u.is_admin ? 'Remove admin rights first' : ''" @click="askBan(u)">Suspend</button>
                                        <button class="rounded-full border border-red-400/40 px-3 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-950/40 disabled:pointer-events-none disabled:opacity-40"
                                                :disabled="u.is_admin" :title="u.is_admin ? 'Remove admin rights first' : ''" @click="remove(u)">Delete</button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="loading" class="px-6 py-10 text-center text-ink-300">Loading…</p>
            <p v-else-if="!users.length" class="px-6 py-10 text-center text-ink-300">No users match.</p>
        </div>

        <Pager :page="meta.page" :last-page="meta.last_page" :total="meta.total" @change="load" />
    </div>

    <!-- suspend dialog -->
    <dialog ref="dialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-ink-700 bg-ink-900 p-0 backdrop:bg-black/70 backdrop:backdrop-blur-sm" @click="(e) => e.target === dialog && dialog.close()">
        <form v-if="ban.user" @submit.prevent="confirmBan">
            <div class="p-5">
                <h2 class="font-display text-2xl font-bold text-gold-300">Suspend {{ ban.user.name }}?</h2>
                <p class="mt-1 text-sm text-ink-300">They are signed out right away and cannot log in until you unsuspend them.</p>
                <label for="reason" class="mb-1.5 mt-4 block text-sm font-semibold text-ink-200">Reason (shown to them when they try to log in)</label>
                <input id="reason" v-model="ban.reason" maxlength="255" class="input" placeholder="Optional">
            </div>
            <div class="flex justify-end gap-2 border-t border-ink-700 px-5 py-4">
                <button type="button" class="btn-ghost" @click="dialog.close()">Cancel</button>
                <button class="btn-gold" :disabled="ban.busy">{{ ban.busy ? 'Suspending…' : 'Suspend' }}</button>
            </div>
        </form>
    </dialog>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { timeAgo } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import MiniBars from '@/components/admin/MiniBars.vue';

const ui = useUi();
const stats = ref(null);

onMounted(async () => {
    try { stats.value = (await http.get('/admin/stats')).data; }
    catch (e) { ui.toast(errorText(e)); }
});

const cards = (s) => [
    { label: 'Users', value: s.users.total, note: `+${s.users.today} today`, icon: 'users' },
    { label: 'Online now', value: s.users.online, note: `${s.users.banned} suspended`, icon: 'user' },
    { label: 'Posts', value: s.posts.total, note: `+${s.posts.today} today`, icon: 'image' },
    { label: 'Comments', value: s.comments.total, note: `+${s.comments.today} today`, icon: 'reply' },
    { label: 'Messages', value: s.messages.total, note: `+${s.messages.today} today`, icon: 'chat' },
    { label: 'Calls', value: s.calls.total, note: `${s.calls.minutes} min talked, ${s.calls.missed} missed`, icon: 'phone' },
];
</script>

<template>
    <div v-if="!stats" class="card px-6 py-14 text-center text-ink-300">Loading dashboard…</div>

    <div v-else class="space-y-6">
        <div>
            <h1 class="font-display text-4xl font-bold sm:text-5xl">Dashboard</h1>
            <p class="mt-1 text-ink-300">A quick look at how the community is doing.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div v-for="c in cards(stats)" :key="c.label" class="card flex items-center gap-4 p-5">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gold-grad text-ink-950"><AppIcon :name="c.icon" class="h-6 w-6" /></span>
                <div class="min-w-0">
                    <p class="font-display text-4xl font-bold leading-none text-gold-300">{{ c.value.toLocaleString() }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ c.label }}</p>
                    <p class="truncate text-xs text-ink-300">{{ c.note }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <MiniBars title="New users" :series="stats.series.users" />
            <MiniBars title="New posts" :series="stats.series.posts" />
            <MiniBars title="Messages" :series="stats.series.messages" />
        </div>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-700 bg-ink-800/50 px-5 py-4">
                <h2 class="font-display text-2xl font-bold text-gold-300">Newest members</h2>
                <RouterLink to="/admin/users" class="text-xs font-semibold text-ink-300 hover:text-gold-300">See all users</RouterLink>
            </div>
            <div class="divide-y divide-ink-700">
                <RouterLink v-for="u in stats.recent_users" :key="u.id" :to="`/users/${u.id}`" class="flex items-center gap-3 px-5 py-3 transition hover:bg-ink-800">
                    <UserAvatar :user="u" size="sm" :online="u.is_online" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold">{{ u.name }}</p>
                        <p class="truncate text-xs text-ink-300">{{ u.email }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-ink-300">joined {{ timeAgo(u.created_at) }}</span>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

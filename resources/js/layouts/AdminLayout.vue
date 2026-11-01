<script setup>
import { useRoute } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';

const route = useRoute();

const links = [
    { to: '/admin', label: 'Dashboard', icon: 'home', exact: true },
    { to: '/admin/users', label: 'Users', icon: 'users' },
    { to: '/admin/posts', label: 'Posts', icon: 'image' },
    { to: '/admin/comments', label: 'Comments', icon: 'chat' },
];

const active = (l) => (l.exact ? route.path === l.to : route.path.startsWith(l.to));
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[230px_minmax(0,1fr)]">
        <aside class="card h-fit p-3 lg:sticky lg:top-24">
            <p class="flex items-center gap-2 px-3 pb-3 pt-2 font-display text-2xl font-bold text-gold-300">
                <AppIcon name="shield" class="h-5 w-5" /> Admin
            </p>
            <nav class="flex gap-1 overflow-x-auto lg:flex-col" aria-label="Admin">
                <RouterLink v-for="l in links" :key="l.to" :to="l.to"
                            class="flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="active(l) ? 'bg-gold-grad text-ink-950' : 'text-ink-300 hover:bg-ink-800 hover:text-gold-300'">
                    <AppIcon :name="l.icon" class="h-4 w-4" /> {{ l.label }}
                </RouterLink>
                <RouterLink to="/feed" class="flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300 lg:mt-3 lg:border-t lg:border-ink-700 lg:pt-4">
                    <AppIcon name="back" class="h-4 w-4" /> Back to app
                </RouterLink>
            </nav>
        </aside>

        <section class="min-w-0">
            <RouterView v-slot="{ Component, route: r }">
                <component :is="Component" :key="r.path" />
            </RouterView>
        </section>
    </div>
</template>

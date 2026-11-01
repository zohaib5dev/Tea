<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '@/stores/auth';
import { useInbox } from '@/stores/inbox';
import { useCounters } from '@/stores/counters';
import { useUi } from '@/stores/ui';
import { getEcho } from '@/lib/echo';
import { messagePreview } from '@/lib/format';
import { initCalls } from '@/composables/useCalls';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import CallManager from '@/components/CallManager.vue';

const auth = useAuth();
const inbox = useInbox();
const counters = useCounters();
const ui = useUi();
const route = useRoute();
const appName = window.__APP_NAME__ || 'App';

const links = computed(() => [
    { to: '/feed', label: 'Feed', icon: 'home', active: route.path === '/feed' || route.path.startsWith('/posts') },
    { to: '/friends', label: 'Friends', icon: 'users', active: route.path.startsWith('/friends'), badge: counters.pending },
    { to: '/messages', label: 'Messages', icon: 'chat', active: route.path.startsWith('/messages'), badge: inbox.totalUnread },
    { to: '/profile', label: 'Profile', icon: 'user', active: route.path.startsWith('/users') },
    ...(auth.user.is_admin ? [{ to: '/admin', label: 'Admin', icon: 'shield', active: route.path.startsWith('/admin') }] : []),
]);

onMounted(() => {
    inbox.load().catch(() => {});
    counters.load();

    const echo = getEcho();
    if (!echo) return;

    // Messages for chats you are NOT looking at: update badges and tell you.
    echo.private('user.' + auth.user.id)
        .listen('.message.sent', (e) => {
            if (e.message.conversation_id === inbox.activeId) return;   // the open chat handles its own messages
            inbox.touch({ conversationId: e.message.conversation_id, userId: e.message.user_id, type: e.conversation.type, preview: e.preview, unread: true });
            ui.toast(`${e.message.user?.name || 'New message'}: ${e.preview}`);
        })
        .listen('.message.updated', (e) => {
            if (e.message.conversation_id === inbox.activeId) return;
            inbox.touch({ conversationId: e.message.conversation_id, userId: e.message.user_id, type: e.conversation.type, preview: e.preview, unread: false });
        });

    initCalls(auth.user.id);
});
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-ink-700 bg-ink-950/85 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
            <RouterLink to="/feed" class="bg-gold-grad bg-clip-text font-display text-3xl font-bold text-transparent">{{ appName }}</RouterLink>

            <nav class="ml-auto hidden items-center gap-1 md:flex" aria-label="Main">
                <RouterLink v-for="l in links" :key="l.to" :to="l.to"
                            class="relative flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
                            :class="l.active ? 'bg-ink-800 text-gold-300' : 'text-ink-300 hover:bg-ink-800 hover:text-gold-300'">
                    <AppIcon :name="l.icon" class="h-4 w-4" /> {{ l.label }}
                    <span v-if="l.badge" class="rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950">{{ l.badge }}</span>
                </RouterLink>
            </nav>

            <div class="ml-auto flex items-center gap-3 md:ml-2">
                <RouterLink to="/profile" aria-label="Your profile"><UserAvatar :user="auth.user" size="xs" /></RouterLink>
                <button class="text-sm font-semibold text-ink-300 transition hover:text-gold-300" @click="auth.logout()">Log out</button>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 pb-28 pt-6 md:pb-12">
        <RouterView v-slot="{ Component, route: r }">
            <component :is="Component" :key="r.path" />
        </RouterView>
    </main>

    <!-- Mobile bottom navigation -->
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-700 bg-ink-950/95 backdrop-blur md:hidden" aria-label="Main">
        <div class="grid" :class="links.length > 4 ? 'grid-cols-5' : 'grid-cols-4'">
            <RouterLink v-for="l in links" :key="l.to" :to="l.to"
                        class="relative flex flex-col items-center gap-1 py-3 text-xs font-semibold"
                        :class="l.active ? 'text-gold-300' : 'text-ink-300'">
                <AppIcon :name="l.icon" class="h-5 w-5" /> {{ l.label }}
                <span v-if="l.badge" class="absolute right-6 top-1.5 h-2.5 w-2.5 rounded-full bg-gold-400"></span>
            </RouterLink>
        </div>
    </nav>

    <CallManager />
</template>

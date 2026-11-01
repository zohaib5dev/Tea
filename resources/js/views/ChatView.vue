<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http, { errorText } from '@/lib/http';
import { getEcho } from '@/lib/echo';
import { useAuth } from '@/stores/auth';
import { useInbox } from '@/stores/inbox';
import { useUi } from '@/stores/ui';
import { firstName, messagePreview } from '@/lib/format';
import { startCall } from '@/composables/useCalls';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import ChatSidebar from '@/components/ChatSidebar.vue';
import MessageBubble from '@/components/MessageBubble.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const inbox = useInbox();
const ui = useUi();

const id = Number(route.params.id);
const me = auth.user;

const conversation = ref(null);
const messages = ref([]);
const hasMore = ref(false);
const loading = ref(true);
const thread = ref(null);
const seen = new Set();

const body = ref('');
const file = ref(null);
const replyTo = ref(null);
const sending = ref(false);

const live = ref(false);
const liveText = ref('Connecting…');
const typers = ref({});                 // userId -> { name, timer }

const isGroup = computed(() => conversation.value?.type === 'group');
const other = computed(() => conversation.value?.other);
const avatarUser = computed(() => other.value || { name: conversation.value?.title, avatar_url: conversation.value?.avatar_url });
const typingNames = computed(() => Object.values(typers.value).map((t) => t.name));
const typingLabel = computed(() => typingNames.value.length === 1 ? `${typingNames.value[0]} is typing…` : `${typingNames.value.slice(0, 2).join(' and ')} are typing…`);
const statusLine = computed(() => {
    if (typingNames.value.length) return typingLabel.value;
    if (isGroup.value) return `${conversation.value.members.length} members: ${conversation.value.members.map((m) => m.name).join(', ')}`;
    return other.value?.is_online ? 'Online now' : 'Offline';
});

// ---------- scrolling ----------
let pinned = true;
const atBottom = () => !thread.value || thread.value.scrollHeight - thread.value.scrollTop - thread.value.clientHeight < 120;
const toBottom = () => { if (thread.value) thread.value.scrollTop = thread.value.scrollHeight; };
const onScroll = () => { pinned = atBottom(); };
const onMediaLoad = () => { if (pinned) toBottom(); };   // images finishing keep the newest message in view

// ---------- messages ----------
function add(m) {
    if (seen.has(m.id)) return;
    seen.add(m.id);
    messages.value.push(m);
}

function replace(m) {
    const i = messages.value.findIndex((x) => x.id === m.id);
    if (i === -1) add(m); else messages.value[i] = m;
}

const lastId = () => messages.value.reduce((n, m) => Math.max(n, m.id), 0);

async function addAndStick(m, force = false) {
    const stick = force || atBottom();
    add(m);
    if (stick) { await nextTick(); toBottom(); }
}

async function loadOlder() {
    const first = messages.value[0];
    if (!first) return;
    const el = thread.value, before = el.scrollHeight;
    try {
        const { data } = await http.get(`/conversations/${id}/messages`, { params: { before: first.id } });
        data.messages.forEach((m) => seen.add(m.id));
        messages.value = [...data.messages, ...messages.value];
        hasMore.value = data.has_more;
        await nextTick();
        el.scrollTop = el.scrollHeight - before;     // stay where you were reading
    } catch (e) { ui.toast(errorText(e)); }
}

async function send() {
    if (sending.value || (!body.value.trim() && !file.value)) return;
    const fd = new FormData();
    if (body.value.trim()) fd.append('body', body.value);
    if (file.value) fd.append('attachment', file.value);
    if (replyTo.value) fd.append('reply_to_id', replyTo.value.id);

    sending.value = true;
    try {
        const { data } = await http.post(`/conversations/${id}/messages`, fd);
        await addAndStick(data, true);
        inbox.touch({ conversationId: id, userId: other.value?.id, type: conversation.value.type, preview: (data.type === 'call' ? '' : 'You: ') + messagePreview(data), unread: false });
        body.value = ''; file.value = null; replyTo.value = null;
    } catch (e) { alert('Message not sent: ' + errorText(e)); }
    sending.value = false;
}

function pickFile(e) { file.value = e.target.files[0] || null; }
function startReply(m) { replyTo.value = m; document.getElementById('chat-input')?.focus(); }

// ---------- delete: for me / for everyone ----------
const delDialog = ref(null);
const delTarget = ref(null);

function askDelete(m) { delTarget.value = m; delDialog.value.showModal(); }

async function doDelete(url, doneText) {
    const m = delTarget.value;
    delDialog.value.close();
    try {
        await http.delete(url(m));
        messages.value = messages.value.filter((x) => x.id !== m.id);
        ui.toast(doneText);
    } catch (e) { alert('Could not delete the message: ' + errorText(e)); }
}
const deleteForEveryone = () => doDelete((m) => `/messages/${m.id}`, 'Message deleted for everyone.');
const deleteForMe = () => doDelete((m) => `/messages/${m.id}/me`, 'Message deleted for you.');

// ---------- calls ----------
const call = (video) => startCall(id, other.value, video);

// ---------- read marker ----------
let needsRead = false;
function markRead() {
    if (document.hidden) { needsRead = true; return; }
    needsRead = false;
    inbox.clearUnread(id);
    http.post(`/conversations/${id}/read`).catch(() => {});
}
const onVisible = () => { if (!document.hidden && needsRead) markRead(); };

// ---------- real time (Reverb) with polling as a safety net ----------
let echo = null, subscribed = false, lastTyping = 0, lastPoll = 0, pollTimer = null, connection = null;

function onTyping() {
    if (!echo || !subscribed || Date.now() - lastTyping < 1500) return;
    lastTyping = Date.now();
    try { echo.private('conversation.' + id).whisper('typing', { id: me.id, name: firstName(me.name) }); } catch { /* not ready yet */ }
}

function setTyper(e) {
    if (e.id === me.id) return;
    clearTimeout(typers.value[e.id]?.timer);
    const timer = setTimeout(() => dropTyper(e.id), 3000);
    typers.value = { ...typers.value, [e.id]: { name: e.name, timer } };
    if (atBottom()) nextTick(toBottom);
}
function dropTyper(userId) {
    const { [userId]: gone, ...rest } = typers.value;
    clearTimeout(gone?.timer);
    typers.value = rest;
}

const stateText = { connecting: 'Connecting…', unavailable: 'Server unreachable', failed: 'Not supported', disconnected: 'Reconnecting…' };
function showState(state) {
    live.value = state === 'connected';
    liveText.value = live.value ? 'Live' : (stateText[state] || 'Reconnecting…');
    if (live.value) poll(true);   // catch up on anything missed while offline
}

async function poll(force = false) {
    if (document.hidden) return;
    if (!force && live.value && Date.now() - lastPoll < 30000) return;
    lastPoll = Date.now();
    try {
        const { data } = await http.get(`/conversations/${id}/poll`, { params: { after: lastId() } });
        const stick = atBottom();
        data.forEach(add);
        if (data.length && stick) { await nextTick(); toBottom(); }
    } catch { /* try again on the next tick */ }
}

function connect() {
    echo = getEcho();
    if (!echo) { liveText.value = 'Offline mode'; return; }

    const channel = echo.private('conversation.' + id)
        .listen('.message.sent', (e) => {
            const m = e.message;
            addAndStick(m, m.user_id === me.id);
            dropTyper(m.user_id);
            inbox.touch({ conversationId: id, userId: other.value?.id, type: conversation.value.type, preview: (m.user_id === me.id && m.type !== 'call' ? 'You: ' + messagePreview(m) : e.preview), unread: false });
            if (m.user_id !== me.id) markRead();
        })
        .listen('.message.updated', (e) => {
            const stick = atBottom();
            replace(e.message);
            if (stick) nextTick(toBottom);
            inbox.touch({ conversationId: id, userId: other.value?.id, type: conversation.value.type, preview: e.preview, unread: false });
        })
        .listen('.message.deleted', (e) => { messages.value = messages.value.filter((m) => m.id !== e.id); })
        .listenForWhisper('typing', setTyper);

    channel.subscription?.bind('pusher:subscription_succeeded', () => { subscribed = true; });

    connection = echo.connector.pusher.connection;
    connection.bind('state_change', (s) => showState(s.current));
    showState(connection.state);
}

onMounted(async () => {
    try {
        const { data } = await http.get(`/conversations/${id}`);
        conversation.value = data.conversation;
        data.messages.forEach(add);
        hasMore.value = data.has_more;
    } catch (e) {
        ui.toast(errorText(e, 'This chat is not available.'));
        return router.replace('/messages');
    }
    loading.value = false;

    inbox.activeId = id;
    inbox.clearUnread(id);
    await inbox.load().catch(() => {});

    await nextTick();
    toBottom();
    requestAnimationFrame(toBottom);
    document.fonts?.ready.then(() => { if (pinned) toBottom(); });

    connect();
    pollTimer = setInterval(poll, 4000);
    document.addEventListener('visibilitychange', onVisible);
});

onBeforeUnmount(() => {
    clearInterval(pollTimer);
    document.removeEventListener('visibilitychange', onVisible);
    Object.values(typers.value).forEach((t) => clearTimeout(t.timer));
    if (inbox.activeId === id) inbox.activeId = null;
    if (echo) {
        echo.leave('conversation.' + id);
        connection?.unbind('state_change');
    }
});
</script>

<template>
    <div v-if="loading" class="card mx-auto max-w-3xl px-6 py-14 text-center text-ink-300">Loading chat…</div>

    <div v-else class="grid h-[calc(100dvh-10rem)] min-h-[460px] gap-4 lg:grid-cols-[330px_minmax(0,1fr)]">
        <ChatSidebar :active-id="id" :start-on-groups="isGroup" />

        <section class="scrollbar-gold card flex min-h-0 flex-col overflow-hidden" :aria-label="`Chat with ${conversation.title}`">
            <!-- header -->
            <div class="flex items-center gap-3 border-b border-ink-700 px-4 py-3">
                <RouterLink to="/messages" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300 lg:hidden" aria-label="Back to messages"><AppIcon name="back" class="h-5 w-5" /></RouterLink>
                <UserAvatar :user="avatarUser" size="sm" :online="other?.is_online" />
                <div class="min-w-0 flex-1">
                    <h1 class="truncate font-bold">{{ conversation.title }}</h1>
                    <p class="truncate text-xs" :class="typingNames.length ? 'italic text-gold-300' : other?.is_online ? 'text-emerald-400' : 'text-ink-300'">{{ statusLine }}</p>
                </div>

                <div v-if="!isGroup" class="flex shrink-0 items-center gap-1">
                    <button type="button" class="rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Start a voice call" title="Voice call" @click="call(false)"><AppIcon name="phone" /></button>
                    <button type="button" class="rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Start a video call" title="Video call" @click="call(true)"><AppIcon name="video" /></button>
                </div>

                <span class="flex shrink-0 items-center gap-1.5 text-xs text-ink-300" title="Real-time connection">
                    <span class="h-2 w-2 rounded-full" :class="live ? 'bg-emerald-400' : 'bg-ink-300'"></span>
                    <span class="hidden sm:inline">{{ liveText }}</span>
                </span>
            </div>

            <!-- thread -->
            <div ref="thread" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4" aria-live="polite" @scroll.passive="onScroll" @load.capture="onMediaLoad">
                <div v-if="hasMore" class="text-center"><button type="button" class="btn-ghost !px-3 !py-1.5 !text-xs" @click="loadOlder">Load older messages</button></div>
                <p v-if="!messages.length" class="py-10 text-center text-ink-300">No messages yet. Say hello.</p>

                <MessageBubble v-for="m in messages" :key="m.id" :m="m" :me-id="me.id" :is-group="isGroup"
                               @reply="startReply" @delete="askDelete" @callback="call" />

                <!-- someone is typing -->
                <div v-if="typingNames.length" class="flex justify-start" aria-hidden="true">
                    <div class="rounded-2xl rounded-bl-md bg-ink-800 px-4 py-3">
                        <p v-if="isGroup" class="mb-1 text-xs font-semibold text-gold-400">{{ typingNames.join(', ') }}</p>
                        <span class="flex items-center gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400" style="animation-delay: .15s"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400" style="animation-delay: .3s"></span>
                        </span>
                    </div>
                </div>
            </div>
            <p class="sr-only" aria-live="polite">{{ typingNames.length ? typingLabel : '' }}</p>

            <!-- composer -->
            <form class="border-t border-ink-700 p-3" @submit.prevent="send">
                <div v-if="replyTo" class="mb-2 flex items-center justify-between gap-3 rounded-lg border-l-2 border-gold-500 bg-ink-800 px-3 py-2 text-xs text-ink-200">
                    <span class="truncate">Replying to: {{ replyTo.body || 'Attachment' }}</span>
                    <button type="button" class="text-ink-300 hover:text-gold-300" aria-label="Cancel reply" @click="replyTo = null"><AppIcon name="x" class="h-4 w-4" /></button>
                </div>
                <div class="flex items-center gap-2">
                    <label class="cursor-pointer rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" title="Attach a file">
                        <AppIcon name="clip" />
                        <input type="file" class="hidden" @change="pickFile">
                    </label>
                    <input id="chat-input" v-model="body" type="text" maxlength="5000" autocomplete="off" class="input !rounded-full !py-2.5" placeholder="Write a message" @input="onTyping">
                    <button class="btn-gold !p-3" :disabled="sending" aria-label="Send message"><AppIcon name="send" class="h-5 w-5" /></button>
                </div>
                <p v-if="file" class="mt-1 px-3 text-xs text-gold-300">Attached: {{ file.name }}</p>
            </form>
        </section>
    </div>

    <!-- delete dialog -->
    <dialog ref="delDialog" class="w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-ink-700 bg-ink-900 p-0 backdrop:bg-black/70 backdrop:backdrop-blur-sm"
            @click="(e) => e.target === delDialog && delDialog.close()">
        <div class="p-5">
            <h2 class="font-display text-2xl font-bold text-gold-300">Delete message?</h2>
            <p class="mt-1 text-sm text-ink-300">Choose who should no longer see it.</p>
            <div class="mt-5 space-y-2">
                <button v-if="delTarget?.user_id === me.id" type="button" class="flex w-full items-start gap-3 rounded-xl border border-red-400/40 px-4 py-3 text-left transition hover:bg-red-950/40" @click="deleteForEveryone">
                    <AppIcon name="trash" class="mt-0.5 h-4 w-4 shrink-0 text-red-300" />
                    <span class="text-sm font-semibold text-red-300">Delete for everyone<span class="block text-xs font-normal text-ink-300">Removed from this chat for all members.</span></span>
                </button>
                <button type="button" class="flex w-full items-start gap-3 rounded-xl border border-ink-700 px-4 py-3 text-left transition hover:bg-ink-800" @click="deleteForMe">
                    <AppIcon name="user" class="mt-0.5 h-4 w-4 shrink-0 text-gold-400" />
                    <span class="text-sm font-semibold">Delete for me<span class="block text-xs font-normal text-ink-300">Only removed from your view. Others still see it.</span></span>
                </button>
                <button type="button" class="btn-ghost w-full" @click="delDialog.close()">Cancel</button>
            </div>
        </div>
    </dialog>
</template>

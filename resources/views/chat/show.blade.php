@extends('layouts.app')
@section('title', $conversation->displayTitleFor(auth()->user()))

@section('content')
@php
    $me = auth()->user();
    $isGroup = $conversation->type === 'group';
    $other = $isGroup ? null : $conversation->participants->firstWhere('id', '!=', $me->id);
    $title = $conversation->displayTitleFor($me);
    $avatarUser = $other ?? (object) ['name' => $title, 'avatar' => $conversation->avatar];
    $friendsUnread = $friends->sum('unread_messages_count');
    $groupsUnread = $groups->sum('unread_count');
@endphp

<div class="grid h-[calc(100dvh-10rem)] min-h-[460px] gap-4 lg:grid-cols-[330px_minmax(0,1fr)]">

    {{-- ============ SIDE: friends and group chats (updates live) ============ --}}
    <aside class="card hidden min-h-0 flex-col overflow-hidden lg:flex" aria-label="Conversations">
        <div class="border-b border-ink-700 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-2xl font-bold text-gold-300">Messages</h2>
                <a href="{{ route('conversations.index') }}" class="text-xs font-semibold text-ink-300 hover:text-gold-300">All &amp; new group</a>
            </div>

            <input id="side-search" type="search" placeholder="Search" aria-label="Search conversations" class="input !rounded-full !py-2 text-sm">

            <div class="mt-3 grid grid-cols-2 gap-1 rounded-xl bg-ink-800/70 p-1 ring-1 ring-ink-700/50" role="tablist">
                <button type="button" role="tab" data-tab="friends" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition">
                    Friends
                    <span data-tab-badge="friends" class="rounded-full bg-gold-grad px-1.5 text-[10px] font-bold text-ink-950 {{ $friendsUnread ? '' : 'hidden' }}">{{ $friendsUnread }}</span>
                </button>
                <button type="button" role="tab" data-tab="groups" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition">
                    Groups
                    <span data-tab-badge="groups" class="rounded-full bg-gold-grad px-1.5 text-[10px] font-bold text-ink-950 {{ $groupsUnread ? '' : 'hidden' }}">{{ $groupsUnread }}</span>
                </button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto">

            {{-- Friends --}}
            <div data-panel="friends" class="divide-y divide-ink-700/60">
                @forelse($friends as $friend)
                    @php
                        $unread = $friend->unread_messages_count;
                        $active = $friend->conversation_id === $conversation->id;
                    @endphp
                    <form method="POST" action="{{ route('conversations.store') }}" data-item data-row
                          data-name="{{ \Illuminate\Support\Str::lower($friend->name) }}" data-user-id="{{ $friend->id }}"
                          @if($friend->conversation_id) data-conversation-id="{{ $friend->conversation_id }}" @endif>
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $friend->id }}">
                        <button class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-ink-800 {{ $active ? 'bg-ink-800' : ($unread ? 'bg-gold-900/10' : '') }}" @if($active) aria-current="true" @endif>
                            <x-avatar :user="$friend" size="sm" :online="$friend->is_online"/>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate text-sm font-bold">{{ $friend->name }}</p>
                                    <span data-time class="shrink-0 text-[11px] {{ $unread ? 'font-semibold text-gold-300' : 'text-ink-300' }}">{{ $friend->last_message_at?->diffForHumans(null, true, true) }}</span>
                                </div>
                                <p data-preview class="truncate text-xs {{ $unread ? 'font-semibold text-ink-50' : 'text-ink-300' }}">{{ $friend->last_message_text ?? 'No messages yet' }}</p>
                            </div>
                            <span data-badge class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950 {{ $unread ? '' : 'hidden' }}">{{ $unread > 99 ? '99+' : $unread }}</span>
                        </button>
                    </form>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-ink-300">
                        No friends yet.
                        <a href="{{ route('friends.index') }}" class="font-semibold text-gold-300 hover:underline">Find friends</a>
                    </div>
                @endforelse
            </div>

            {{-- Group chats --}}
            <div data-panel="groups" class="hidden divide-y divide-ink-700/60">
                @forelse($groups as $group)
                    @php $active = $group->id === $conversation->id; @endphp
                    <a href="{{ route('conversations.show', $group) }}" data-item data-row data-conversation-id="{{ $group->id }}"
                       data-name="{{ \Illuminate\Support\Str::lower($group->title ?: 'group chat') }}"
                       class="flex items-center gap-3 px-4 py-3 transition hover:bg-ink-800 {{ $active ? 'bg-ink-800' : ($group->unread_count ? 'bg-gold-900/10' : '') }}" @if($active) aria-current="true" @endif>
                        <x-avatar :user="(object) ['name' => $group->title ?: 'Group', 'avatar' => $group->avatar]" size="sm"/>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="truncate text-sm font-bold">{{ $group->title ?: 'Group chat' }}</p>
                                <span data-time class="shrink-0 text-[11px] {{ $group->unread_count ? 'font-semibold text-gold-300' : 'text-ink-300' }}">{{ $group->last_message_at?->diffForHumans(null, true, true) }}</span>
                            </div>
                            <p data-preview class="truncate text-xs {{ $group->unread_count ? 'font-semibold text-ink-50' : 'text-ink-300' }}">{{ $group->preview ?? 'No messages yet' }}</p>
                        </div>
                        <span data-badge class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950 {{ $group->unread_count ? '' : 'hidden' }}">{{ $group->unread_count }}</span>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-ink-300">
                        No group chats yet.
                        <a href="{{ route('conversations.index') }}" class="font-semibold text-gold-300 hover:underline">Create one</a>
                    </div>
                @endforelse
            </div>

            <p id="side-empty" class="hidden px-5 py-10 text-center text-sm text-ink-300">Nothing matches your search.</p>
        </div>
    </aside>

    {{-- ============ MAIN: the chat ============ --}}
    <section class="card flex min-h-0 flex-col overflow-hidden" aria-label="Chat with {{ $title }}">

        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-ink-700 px-4 py-3">
            <a href="{{ route('conversations.index') }}" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300 lg:hidden" aria-label="Back to messages"><x-icon name="back" class="h-5 w-5"/></a>
            <x-avatar :user="$avatarUser" size="sm" :online="$other?->is_online ?? false"/>
            <div class="min-w-0 flex-1">
                <h1 class="truncate font-bold">{{ $title }}</h1>
                <p id="status-line" class="truncate text-xs {{ ($other?->is_online ?? false) ? 'text-emerald-400' : 'text-ink-300' }}">
                    @if($isGroup) {{ $conversation->participants->count() }} members: {{ $conversation->participants->pluck('name')->join(', ') }}
                    @else {{ $other?->is_online ? 'Online now' : 'Offline' }} @endif
                </p>
            </div>
            @unless($isGroup)
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" id="call-voice" class="rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Start a voice call" title="Voice call"><x-icon name="phone"/></button>
                    <button type="button" id="call-video" class="rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Start a video call" title="Video call"><x-icon name="video"/></button>
                </div>
            @endunless

            {{-- Connection status --}}
            <span class="flex shrink-0 items-center gap-1.5 text-xs text-ink-300" title="Real-time connection">
                <span id="live-dot" class="h-2 w-2 rounded-full bg-ink-300"></span>
                <span id="live-text" class="hidden sm:inline">Connecting…</span>
            </span>
        </div>

        {{-- Thread --}}
        <div id="thread" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4" aria-live="polite">
            @if($messages->hasMorePages())
                <div class="text-center"><a href="{{ $messages->nextPageUrl() }}" class="btn-ghost !px-3 !py-1.5 !text-xs">Load older messages</a></div>
            @endif
            <p id="empty" class="hidden py-10 text-center text-ink-300">No messages yet. Say hello.</p>

            {{-- "Someone is typing" bubble: always the last thing in the thread --}}
            <div id="typing-row" class="hidden flex justify-start" aria-hidden="true">
                <div class="rounded-2xl rounded-bl-md bg-ink-800 px-4 py-3">
                    <p id="typing-name" class="mb-1 hidden text-xs font-semibold text-gold-400"></p>
                    <span class="flex items-center gap-1">
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400"></span>
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400" style="animation-delay:.15s"></span>
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gold-400" style="animation-delay:.3s"></span>
                    </span>
                </div>
            </div>
        </div>

        <p id="typing" class="sr-only" aria-live="polite"></p>

        {{-- Composer --}}
        <form id="composer" class="border-t border-ink-700 p-3" enctype="multipart/form-data">
            <div id="reply-bar" class="mb-2 hidden items-center justify-between gap-3 rounded-lg border-l-2 border-gold-500 bg-ink-800 px-3 py-2 text-xs text-ink-200">
                <span id="reply-text" class="truncate"></span>
                <button type="button" id="reply-cancel" class="text-ink-300 hover:text-gold-300" aria-label="Cancel reply"><x-icon name="x" class="h-4 w-4"/></button>
            </div>
            <input type="hidden" name="reply_to_id" id="reply-id">
            <div class="flex items-center gap-2">
                <label class="cursor-pointer rounded-full p-2.5 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" title="Attach a file">
                    <x-icon name="clip"/>
                    <input type="file" name="attachment" id="attachment" class="hidden">
                </label>
                <input type="text" name="body" id="body" maxlength="5000" autocomplete="off" class="input !rounded-full !py-2.5" placeholder="Write a message">
                <button class="btn-gold !p-3" aria-label="Send message"><x-icon name="send" class="h-5 w-5"/></button>
            </div>
            <p id="file-name" class="mt-1 hidden px-3 text-xs text-gold-300"></p>
        </form>
    </section>
</div>

{{-- Delete message: for me or for everyone --}}
<dialog id="delete-dialog" aria-labelledby="delete-title"
        class="w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-ink-700 bg-ink-900 p-0 text-ink-50 backdrop:bg-black/70 backdrop:backdrop-blur-sm">
    <div class="p-5">
        <h2 id="delete-title" class="font-display text-2xl font-bold text-gold-300">Delete message?</h2>
        <p class="mt-1 text-sm text-ink-300">Choose who should no longer see it.</p>

        <div class="mt-5 space-y-2">
            <button type="button" id="delete-everyone" class="flex w-full items-start gap-3 rounded-xl border border-red-400/40 px-4 py-3 text-left transition hover:bg-red-950/40">
                <x-icon name="trash" class="mt-0.5 h-4 w-4 shrink-0 text-red-300"/>
                <span class="text-sm font-semibold text-red-300">Delete for everyone
                    <span class="block text-xs font-normal text-ink-300">Removed from this chat for all members.</span>
                </span>
            </button>
            <button type="button" id="delete-me" class="flex w-full items-start gap-3 rounded-xl border border-ink-700 px-4 py-3 text-left transition hover:bg-ink-800">
                <x-icon name="user" class="mt-0.5 h-4 w-4 shrink-0 text-gold-400"/>
                <span class="text-sm font-semibold">Delete for me
                    <span class="block text-xs font-normal text-ink-300">Only removed from your view. Others still see it.</span>
                </span>
            </button>
            <button type="button" id="delete-cancel" class="btn-ghost w-full">Cancel</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    const ME = {{ $me->id }};
    const ME_FIRST = @json(explode(' ', $me->name)[0]);
    const CONVERSATION_ID = @json($conversation->id);
    const IS_GROUP = @json($isGroup);
    const OTHER = @json($other ? ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar] : null);
    const URLS = {
        store: @json(route('messages.store', $conversation)),
        poll: @json(route('messages.poll', $conversation)),
        read: @json(route('messages.read', $conversation)),
        destroy: @json(route('messages.destroy', ':id')),
        hide: @json(route('messages.hide', ':id')),
        storage: @json(asset('storage')) + '/',
    };
    const initial = @json($messages->getCollection()->reverse()->values());

    const thread = document.getElementById('thread');
    const form = document.getElementById('composer');
    const bodyInput = document.getElementById('body');
    const fileInput = document.getElementById('attachment');
    const fileName = document.getElementById('file-name');
    const replyBar = document.getElementById('reply-bar');
    const replyText = document.getElementById('reply-text');
    const replyId = document.getElementById('reply-id');
    const empty = document.getElementById('empty');
    const typingRow = document.getElementById('typing-row');
    const typingName = document.getElementById('typing-name');
    const statusEl = document.getElementById('status-line');
    const statusText = statusEl.textContent.trim();
    const statusClass = statusEl.className;
    const seen = new Set();
    let lastId = 0;

    const el = (tag, cls, text) => {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text) n.textContent = text;
        return n;
    };
    const atBottom = () => thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120;
    const toBottom = () => thread.scrollTop = thread.scrollHeight;
    // Turn a failed response into readable text, e.g. "Server error (500)" or the Laravel message when APP_DEBUG=true.
    async function readError(r) {
        try { const j = await r.json(); return (j.message || 'Error') + ' (' + r.status + ')'; }
        catch { return 'Server error (' + r.status + ')'; }
    }
    const plain = (m) => m.type === 'call' ? callLabel(m) : (m.body || (m.attachment_type === 'image' ? 'Sent a photo' : 'Sent an attachment'));

    function render(m) {
        if (seen.has(m.id)) return;
        seen.add(m.id);
        lastId = Math.max(lastId, m.id);
        empty.classList.add('hidden');
        thread.insertBefore(buildRow(m), typingRow);
    }

    // A message changed (for example a call ended): redraw only that row.
    function updateRow(m) {
        const old = thread.querySelector('[data-id="' + m.id + '"]');
        if (old) old.replaceWith(buildRow(m));
        else render(m);
    }

    function buildRow(m) {
        if (m.type === 'call') return buildCall(m);

        const mine = m.user_id === ME;
        const row = el('div', 'flex ' + (mine ? 'justify-end' : 'justify-start'));
        row.dataset.id = m.id;
        const col = el('div', 'max-w-[80%]');

        if (!mine && IS_GROUP) col.append(el('p', 'mb-0.5 px-2 text-xs font-semibold text-gold-400', m.user ? m.user.name : ''));

        const bubble = el('div', 'rounded-2xl px-4 py-2.5 text-sm ' + (mine ? 'rounded-br-md bg-gold-grad text-ink-950' : 'rounded-bl-md bg-ink-800 text-ink-50'));

        if (m.reply_to) {
            bubble.append(el('div', 'mb-2 truncate rounded-lg border-l-2 px-2 py-1 text-xs ' + (mine ? 'border-ink-950/50 bg-ink-950/10' : 'border-gold-500 bg-ink-900'), m.reply_to.body || 'Attachment'));
        }
        if (m.attachment_path) {
            const url = URLS.storage + m.attachment_path;
            if (m.attachment_type === 'image') {
                const a = el('a');
                a.href = url; a.target = '_blank'; a.rel = 'noopener';
                const img = el('img', 'mb-1 max-h-64 rounded-xl');
                img.src = url; img.alt = 'Shared image';
                a.append(img);
                bubble.append(a);
            } else {
                const a = el('a', 'mb-1 block underline', 'Open attachment');
                a.href = url; a.target = '_blank'; a.rel = 'noopener';
                bubble.append(a);
            }
        }
        if (m.body) bubble.append(el('p', 'whitespace-pre-wrap break-words', m.body));

        const meta = el('div', 'mt-1 flex items-center gap-3 px-2 text-[11px] text-ink-300 ' + (mine ? 'justify-end' : ''));
        meta.append(el('span', '', new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })));

        const reply = el('button', 'font-semibold hover:text-gold-300', 'Reply');
        reply.type = 'button';
        reply.onclick = () => {
            replyId.value = m.id;
            replyText.textContent = 'Replying to: ' + (m.body || 'Attachment');
            replyBar.classList.remove('hidden');
            replyBar.classList.add('flex');
            bodyInput.focus();
        };
        meta.append(reply);

        const del = el('button', 'font-semibold hover:text-red-400', 'Delete');
        del.type = 'button';
        del.onclick = () => askDelete(m);
        meta.append(del);

        col.append(bubble, meta);
        row.append(col);
        return row;
    }

    // ---------- Calls inside the chat ----------
const CALL_ICONS = {
    voice: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.11L9.06 10.9a16 16 0 0 0 4.04 4.04l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.84.58 2.8.7A2 2 0 0 1 22 16.92z"/></svg>',
    video: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 10 5-3v10l-5-3"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>'
};
    const clock = (s) => {
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
        return h ? h + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0') : m + ':' + String(sec).padStart(2, '0');
    };
    // "45 sec", "2 min 31 sec", "1 hr 3 min"
    const longDuration = (s) => {
        s = Math.max(0, s || 0);
        if (s < 60) return s + ' sec';
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
        return h ? h + ' hr ' + m + ' min' : m + ' min' + (sec ? ' ' + sec + ' sec' : '');
    };
    const callLabel = (m) => {
        const kind = m.call_kind === 'video' ? 'Video call' : 'Voice call';
        if (m.call_status === 'ended') return m.call_duration ? kind + ', ' + clock(m.call_duration) : kind + ' ended';
        if (m.call_status === 'missed') return 'Missed ' + kind.toLowerCase();
        if (m.call_status === 'declined') return 'Call declined';
        return kind;
    };

    function buildCall(m) {
        const iAmCaller = m.user_id === ME;
        const video = m.call_kind === 'video';
        const kind = video ? 'Video call' : 'Voice call';
        const status = m.call_status;

        let title, sub = '', bad = false;
        if (status === 'ended') {
            title = kind + ' ended';
            sub = m.call_duration ? longDuration(m.call_duration) : '';
        } else if (status === 'missed') {
            bad = !iAmCaller;
            title = iAmCaller ? 'No answer' : 'Missed ' + kind.toLowerCase();
        } else if (status === 'declined') {
            title = iAmCaller ? 'Call declined' : 'You declined the call';
        } else if (status === 'ongoing') {
            title = kind; sub = 'In progress';
        } else {
            title = iAmCaller ? 'Calling…' : 'Incoming ' + kind.toLowerCase();
        }

        const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const row = el('div', 'flex justify-center');
        row.dataset.id = m.id;

        const card = el('div', 'flex items-center gap-3 rounded-2xl border border-ink-700 bg-ink-800/60 px-4 py-3');

        const icon = el('span', 'grid h-10 w-10 shrink-0 place-items-center rounded-full ' + (bad ? 'bg-red-500/20 text-red-300' : 'bg-gold-grad text-ink-950'));
        icon.innerHTML = CALL_ICONS[video ? 'video' : 'voice'];   // trusted, rendered by Blade

        const text = el('div', 'min-w-0');
        text.append(el('p', 'text-sm font-bold ' + (bad ? 'text-red-300' : ''), title));
        text.append(el('p', 'text-xs text-ink-300', sub ? sub + ', ' + time : time));

        card.append(icon, text);

        if (['ended', 'missed', 'declined'].includes(status)) {
            const back = el('button', 'btn-ghost ml-2 !px-3 !py-1.5 !text-xs', 'Call back');
            back.type = 'button';
            back.onclick = () => startCall(video);
            card.append(back);
        }

        row.append(card);
        return row;
    }

    function removeMessage(id) {
        const row = thread.querySelector('[data-id="' + id + '"]');
        if (row) row.remove();
    }

    function clearReply() {
        replyId.value = '';
        replyBar.classList.add('hidden');
        replyBar.classList.remove('flex');
    }
    document.getElementById('reply-cancel').onclick = clearReply;

    fileInput.addEventListener('change', () => {
        const f = fileInput.files[0];
        fileName.textContent = f ? 'Attached: ' + f.name : '';
        fileName.classList.toggle('hidden', !f);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!bodyInput.value.trim() && !fileInput.files.length) return;
        const r = await fetch(URLS.store, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: new FormData(form) });
        if (!r.ok) { alert('Message not sent: ' + await readError(r)); return; }
        const sent = await r.json();
        render(sent);
        touchRow(CONVERSATION_ID, null, 'any', 'You: ' + plain(sent), false);
        form.reset();
        clearReply();
        fileName.classList.add('hidden');
        toBottom();
    });

    // ---------- Delete: for me / for everyone ----------
    const delDialog = document.getElementById('delete-dialog');
    const delEveryone = document.getElementById('delete-everyone');
    let delTarget = null;

    function askDelete(m) {
        delTarget = m;
        delEveryone.classList.toggle('hidden', m.user_id !== ME);   // only the sender can delete for everyone
        delDialog.showModal();
    }

    async function doDelete(urlTemplate, doneText) {
        const m = delTarget;
        if (!m) return;
        delDialog.close();
        const r = await fetch(urlTemplate.replace(':id', m.id), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        if (r.ok) { removeMessage(m.id); toast(doneText); }
        else alert('Could not delete the message: ' + await readError(r));
    }

    delEveryone.onclick = () => doDelete(URLS.destroy, 'Message deleted for everyone.');
    document.getElementById('delete-me').onclick = () => doDelete(URLS.hide, 'Message deleted for you.');
    document.getElementById('delete-cancel').onclick = () => delDialog.close();
    delDialog.addEventListener('click', (e) => { if (e.target === delDialog) delDialog.close(); });

    // ---------- Voice and video calls (the call screen lives in partials/calls.blade.php) ----------
    const startCall = (video) => (window.startCall || (() => toast('Calls are not set up yet.')))(CONVERSATION_ID, OTHER, video);
    const callVoice = document.getElementById('call-voice');
    const callVideo = document.getElementById('call-video');
    if (callVoice) callVoice.onclick = () => startCall(false);
    if (callVideo) callVideo.onclick = () => startCall(true);

    // ---------- Sidebar helpers ----------
    function styleRow(row, unread) {
        const box = row.matches('a') ? row : row.querySelector('button');
        const preview = row.querySelector('[data-preview]');
        const time = row.querySelector('[data-time]');
        box.classList.toggle('bg-gold-900/10', unread && !box.hasAttribute('aria-current'));
        ['font-semibold', 'text-ink-50'].forEach((c) => preview.classList.toggle(c, unread));
        preview.classList.toggle('text-ink-300', !unread);
        ['font-semibold', 'text-gold-300'].forEach((c) => time.classList.toggle(c, unread));
        time.classList.toggle('text-ink-300', !unread);
    }

    function bumpTab(panel) {
        const badge = document.querySelector('[data-tab-badge="' + panel + '"]');
        if (!badge) return;
        badge.textContent = (parseInt(badge.textContent) || 0) + 1;
        badge.classList.remove('hidden');
    }

    // Update a conversation's row: preview, time, unread badge, and move it to the top of its list.
    function touchRow(conversationId, userId, type, preview, unread) {
        let row = document.querySelector('[data-row][data-conversation-id="' + conversationId + '"]');
        if (!row && type === 'private' && userId) row = document.querySelector('[data-panel="friends"] [data-user-id="' + userId + '"]');
        if (!row) return;

        row.dataset.conversationId = conversationId;
        row.querySelector('[data-preview]').textContent = preview;
        row.querySelector('[data-time]').textContent = 'now';

        const badge = row.querySelector('[data-badge]');
        if (unread) {
            const n = (parseInt(badge.textContent) || 0) + 1;
            badge.textContent = n > 99 ? '99+' : n;
            badge.classList.remove('hidden');
            bumpTab(row.closest('[data-panel]').dataset.panel);
        }
        styleRow(row, unread || !badge.classList.contains('hidden'));
        row.parentElement.prepend(row);
    }

    // ---------- Typing indicator ----------
    const typingEl = document.getElementById('typing');
    const typers = new Map();
    function drawTyping() {
        const names = [...typers.values()].map((t) => t.name);
        const on = names.length > 0;
        const label = names.length === 1 ? names[0] + ' is typing…' : names.slice(0, 2).join(' and ') + ' are typing…';
        const wasAtBottom = atBottom();

        typingRow.classList.toggle('hidden', !on);                   // bouncing dots in the thread
        typingName.classList.toggle('hidden', !(on && IS_GROUP));    // who, in group chats
        typingName.textContent = names.join(', ');
        typingEl.textContent = on ? label : '';                      // screen readers
        statusEl.className = on ? 'truncate text-xs italic text-gold-300' : statusClass;   // header line
        statusEl.textContent = on ? label : statusText;

        if (on && wasAtBottom) toBottom();
    }

    // ---------- Read receipts for the open chat ----------
    let needsRead = false;
    function markRead() {
        if (document.hidden) { needsRead = true; return; }
        needsRead = false;
        fetch(URLS.read, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).catch(() => {});
    }
    document.addEventListener('visibilitychange', () => { if (!document.hidden && needsRead) markRead(); });

    // ---------- Real time with Laravel Reverb ----------
    let live = false;
    let lastPoll = 0;
    const liveDot = document.getElementById('live-dot');
    const liveText = document.getElementById('live-text');

    function setLive(on) {
        live = on;
        liveDot.className = 'h-2 w-2 rounded-full ' + (on ? 'bg-emerald-400' : 'bg-ink-300');
        liveText.textContent = on ? 'Live' : 'Reconnecting…';
        if (on) poll(true);   // catch up on anything missed while offline
    }

    let channel = null;
    if (window.Echo) {
        channel = Echo.private('conversation.' + CONVERSATION_ID)
            .listen('.message.sent', (e) => {
                const m = e.message;
                const stick = atBottom() || m.user_id === ME;
                render(m);
                if (stick) toBottom();

                typers.delete(m.user_id);
                drawTyping();
                touchRow(CONVERSATION_ID, null, 'any', (m.user_id === ME && m.type !== 'call') ? 'You: ' + plain(m) : e.preview, false);
                if (m.user_id !== ME) markRead();
            })
            .listen('.message.deleted', (e) => removeMessage(e.id))
            .listen('.message.updated', (e) => {
                const stick = atBottom();
                updateRow(e.message);
                if (stick) toBottom();
                touchRow(CONVERSATION_ID, null, 'any', e.preview, false);
            })
            .listenForWhisper('typing', (e) => {
                if (e.id === ME) return;
                const old = typers.get(e.id);
                if (old) clearTimeout(old.timer);
                const timer = setTimeout(() => { typers.delete(e.id); drawTyping(); }, 3000);
                typers.set(e.id, { name: e.name, timer });
                drawTyping();
            });

        // Messages in OTHER chats: update the sidebar and unread badges.
        Echo.private('user.' + ME)
            .listen('.message.sent', (e) => {
                if (e.message.conversation_id === CONVERSATION_ID) return;
                touchRow(e.message.conversation_id, e.message.user_id, e.conversation.type, e.preview, true);
            })
            .listen('.message.updated', (e) => {
                if (e.message.conversation_id === CONVERSATION_ID) return;
                touchRow(e.message.conversation_id, e.message.user_id, e.conversation.type, e.preview, false);
            });

        const connection = Echo.connector.pusher.connection;
        const labels = { connecting: 'Connecting…', unavailable: 'Server unreachable', failed: 'Not supported', disconnected: 'Reconnecting…' };
        const showState = (state) => {
            setLive(state === 'connected');
            if (state !== 'connected') liveText.textContent = labels[state] || 'Reconnecting…';
        };
        connection.bind('state_change', (s) => showState(s.current));
        connection.bind('error', (err) => console.error('[Realtime] connection error', err));
        showState(connection.state);

        // Tell the others "I am typing" (at most every 1.5 seconds, only once the channel is ready).
        let subscribed = false;
        if (channel.subscription) channel.subscription.bind('pusher:subscription_succeeded', () => { subscribed = true; });

        let lastTyping = 0;
        bodyInput.addEventListener('input', () => {
            if (!subscribed) return;
            const now = Date.now();
            if (now - lastTyping < 1500) return;
            lastTyping = now;
            try {
                channel.whisper('typing', { id: ME, name: ME_FIRST });
            } catch (err) {
                console.warn('[Realtime] could not send typing status', err);
            }
        });
    } else {
        liveText.textContent = 'Offline mode';
        liveText.parentElement.title = window.__echoError || 'Real time is not available';
    }

    // Safety net: poll every 4 seconds if real time is down, and every 30 seconds if it is up.
    async function poll(force = false) {
        if (document.hidden) return;
        if (!force && live && Date.now() - lastPoll < 30000) return;
        lastPoll = Date.now();
        try {
            const r = await fetch(URLS.poll + '?after=' + lastId, { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return;
            const list = await r.json();
            const stick = atBottom();
            list.forEach(render);
            if (list.length && stick) toBottom();
        } catch {}
    }

    initial.forEach(render);
    if (!initial.length) empty.classList.remove('hidden');

    // Open the chat at the newest message and stay there while images and fonts finish loading.
    let pinned = true;
    thread.addEventListener('scroll', () => { pinned = atBottom(); }, { passive: true });
    thread.addEventListener('load', () => { if (pinned) toBottom(); }, true);   // images finishing
    toBottom();
    requestAnimationFrame(toBottom);
    window.addEventListener('load', () => { if (pinned) toBottom(); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { if (pinned) toBottom(); });
    setInterval(poll, 4000);

    // ---------- Sidebar: Friends / Groups tabs and search ----------
    const tabs = document.querySelectorAll('[data-tab]');
    const panels = document.querySelectorAll('[data-panel]');
    const sideSearch = document.getElementById('side-search');
    const sideEmpty = document.getElementById('side-empty');

    function showTab(name) {
        tabs.forEach((t) => {
            const on = t.dataset.tab === name;
            t.setAttribute('aria-selected', on);
            t.classList.toggle('bg-ink-700', on);
            t.classList.toggle('text-gold-300', on);
            t.classList.toggle('shadow-sm', on);
            t.classList.toggle('text-ink-300', !on);
        });
        panels.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== name));
        filterSide();
    }

    function filterSide() {
        const term = sideSearch.value.trim().toLowerCase();
        const panel = [...panels].find((p) => !p.classList.contains('hidden'));
        let shown = 0;
        panel.querySelectorAll('[data-item]').forEach((item) => {
            const match = item.dataset.name.includes(term);
            item.classList.toggle('hidden', !match);
            if (match) shown++;
        });
        sideEmpty.classList.toggle('hidden', !term || shown > 0);
    }

    tabs.forEach((t) => t.addEventListener('click', () => showTab(t.dataset.tab)));
    sideSearch.addEventListener('input', filterSide);
    showTab(IS_GROUP ? 'groups' : 'friends');
</script>
@endpush

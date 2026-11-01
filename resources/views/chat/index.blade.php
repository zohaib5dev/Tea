@extends('layouts.app')
@section('title', 'Messages')

@section('content')
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

    {{-- Friends, newest conversation first --}}
    <section aria-label="Your conversations">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="font-display text-4xl font-bold">Messages</h1>
                @if($totalUnread)
                    <span class="rounded-full bg-gold-grad px-3 py-1 text-xs font-bold text-ink-950">{{ $totalUnread }} new</span>
                @endif
            </div>
            @if($friends->isNotEmpty())
                <input id="friend-search" type="search" placeholder="Search friends" aria-label="Search friends"
                       class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-64">
            @endif
        </div>

        @if($friends->isEmpty())
            <div class="card px-6 py-14 text-center">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><x-icon name="chat" class="h-7 w-7"/></div>
                <p class="font-display text-3xl font-bold text-gold-300">No one to message yet</p>
                <p class="mx-auto mt-2 max-w-sm text-ink-300">Add friends first, then start a conversation here.</p>
                <a href="{{ route('friends.index') }}" class="btn-gold mt-6">Find friends</a>
            </div>
        @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

    @foreach($friends as $friend)
        @php $unread = $friend->unread_messages_count; @endphp
        {{-- A form so the same card works for existing and brand-new chats --}}
        <form method="POST" action="{{ route('conversations.store') }}" data-friend data-name="{{ \Illuminate\Support\Str::lower($friend->name) }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $friend->id }}">
            <button class="card flex h-full w-full items-center gap-3 px-4 py-3.5 text-left transition hover:-translate-y-0.5 hover:border-gold-500/50 {{ $unread ? '!border-gold-500/60 !bg-gold-900/20' : '' }}">
                <x-avatar :user="$friend" :online="$friend->is_online"/>

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="truncate font-bold">{{ $friend->name }}</p>
                        @if($friend->last_message_at)
                            <span class="shrink-0 text-xs {{ $unread ? 'font-semibold text-gold-300' : 'text-ink-300' }}">
                                {{ $friend->last_message_at->diffForHumans(null, true, true) }}
                            </span>
                        @endif
                    </div>
                    <p class="truncate text-sm {{ $unread ? 'font-semibold text-ink-50' : 'text-ink-300' }}">
                        {{ $friend->last_message_text ?? 'No messages yet. Say hello.' }}
                    </p>
                </div>

                @if($unread)
                    <span class="grid h-6 min-w-6 shrink-0 place-items-center rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950">
                        {{ $unread > 99 ? '99+' : $unread }}
                    </span>
                @endif
            </button>
        </form>
    @endforeach
</div>
            <p id="no-match" class="card mt-4 hidden px-6 py-10 text-center text-ink-300">No friends match your search.</p>
        @endif
    </section>

    <aside class="space-y-6">

        {{-- Existing group chats --}}
        @if($groups->isNotEmpty())
            <section class="card overflow-hidden" aria-label="Group chats">
                <div class="border-b border-ink-700 bg-ink-800/50 px-5 py-4">
                    <h2 class="font-display text-2xl font-bold text-gold-300">Group chats</h2>
                </div>
                <div class="divide-y divide-ink-700">
                    @foreach($groups as $group)
                        <a href="{{ route('conversations.show', $group) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-ink-800">
                            <x-avatar :user="(object) ['name' => $group->title ?: 'Group', 'avatar' => $group->avatar]" size="sm"/>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">{{ $group->title ?: 'Group chat' }}</p>
                                <p class="truncate text-xs {{ $group->unread_count ? 'font-semibold text-ink-50' : 'text-ink-300' }}">
                                    {{ $group->preview ?? 'No messages yet' }}
                                </p>
                            </div>
                            @if($group->unread_count)
                                <span class="grid h-5 min-w-5 place-items-center rounded-full bg-gold-grad px-1 text-[11px] font-bold text-ink-950">{{ $group->unread_count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- New group --}}
        @if($friends->count() > 1)
            <details class="card group p-5">
                <summary class="flex cursor-pointer list-none items-center justify-between font-display text-2xl font-bold text-gold-300">
                    New group chat <x-icon name="plus" class="h-5 w-5 transition group-open:rotate-45"/>
                </summary>
                <form method="POST" action="{{ route('conversations.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <input name="title" required maxlength="100" class="input" placeholder="Group name">
                    <div class="max-h-48 space-y-1 overflow-y-auto">
                        @foreach($friends as $friend)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl p-2 hover:bg-ink-800">
                                <input type="checkbox" name="user_ids[]" value="{{ $friend->id }}" class="h-4 w-4 accent-[#d4a63a]">
                                <span class="text-sm">{{ $friend->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="btn-gold w-full">Create group</button>
                </form>
            </details>
        @endif
    </aside>
</div>
@endsection

@push('scripts')
<script>
    // Live search through your friends
    const search = document.getElementById('friend-search');
    if (search) {
        search.addEventListener('input', () => {
            const term = search.value.trim().toLowerCase();
            let shown = 0;
            document.querySelectorAll('[data-friend]').forEach((row) => {
                const match = row.dataset.name.includes(term);
                row.classList.toggle('hidden', !match);
                if (match) shown++;
            });
            document.getElementById('no-match').classList.toggle('hidden', shown > 0);
        });
    }
</script>
@endpush
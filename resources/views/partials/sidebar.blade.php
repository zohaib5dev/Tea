<aside class="scrollbar-gold hidden lg:sticky lg:top-24 lg:flex lg:max-h-[calc(100vh-7rem)] lg:flex-col lg:gap-6 lg:self-start lg:overflow-y-auto lg:pr-1" aria-label="Friends">
     <div class="card p-5" x-data="{ tab: 'friends' }">

        <!-- Tabs -->
        <div class="mb-4 grid grid-cols-3 gap-1 rounded-xl bg-ink-800/70 p-1 ring-1 ring-ink-700/50">
            <button
                type="button"
                @click="tab = 'friends'"
                :class="tab === 'friends' ? 'bg-ink-700 text-gold-300 shadow-sm ring-1 ring-gold-300/20' : 'text-ink-300 hover:text-white'"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition">
                Friends
                <span class="rounded-full bg-ink-900/60 px-1.5 py-0.5 text-[10px] font-bold text-ink-300"
                    :class="tab === 'friends' && 'text-gold-300'">{{ $friends->count() }}</span>
            </button>
            <button
                type="button"
                @click="tab = 'received'"
                :class="tab === 'received' ? 'bg-ink-700 text-gold-300 shadow-sm ring-1 ring-gold-300/20' : 'text-ink-300 hover:text-white'"
                class="relative flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition">
                Requests
                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                    :class="tab === 'received' ? 'bg-gold-300/15 text-gold-300' : 'bg-ink-900/60 text-ink-300'">{{ $receivedRequests->count() }}</span>
                @if($receivedRequests->count() > 0)
                <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-gold-300 animate-pulse"></span>
                @endif
            </button>
            <button
                type="button"
                @click="tab = 'suggestions'"
                :class="tab === 'suggestions' ? 'bg-ink-700 text-gold-300 shadow-sm ring-1 ring-gold-300/20' : 'text-ink-300 hover:text-white'"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition">
                Discover
            </button>
        </div>

        <!-- Friends Tab -->
        <div x-show="tab === 'friends'" x-cloak>
            @if($friends->count())
            <div class="grid grid-cols-2 gap-2">
                @foreach($friends as $friend)
                <div class="group relative flex flex-col items-center gap-2 rounded-xl border border-ink-700/60 bg-ink-800/40 p-3 text-center transition hover:border-gold-300/30 hover:bg-ink-800">
                    <a href="{{ route('users.show', $friend) }}" class="flex flex-col items-center gap-2">
                        <span class="relative">
                            <x-avatar :user="$friend" size="md" :online="$friend->is_online" />
                            @if($friend->unread_messages_count)
                            <span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-gold-grad px-1 text-[10px] font-bold text-ink-950 ring-2 ring-ink-900">
                                {{ $friend->unread_messages_count > 99 ? '99+' : $friend->unread_messages_count }}
                            </span>
                            @endif
                        </span>
                        <div class="min-w-0 w-full">
                            <p class="truncate text-xs font-semibold text-white">{{ $friend->name }}</p>
                            <p class="mt-0.5 flex items-center justify-center gap-1 text-[10px] text-ink-300">
                                @if($friend->is_online)
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Online
                                @else
                                <span class="h-1.5 w-1.5 rounded-full bg-ink-500"></span> Offline
                                @endif
                            </p>

                        </div>
                    </a>

                    <form method="POST" action="{{ route('conversations.store') }}" class="w-full">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $friend->id }}">
                        <button class="btn-gold w-full !py-1 !text-[10px] transition {{ $friend->unread_messages_count ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }}">
                            <x-icon name="chat" class="mr-1 inline h-3 w-3" />
                            {{ $friend->unread_messages_count ? $friend->unread_messages_count . ' new' : 'Message' }}
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-ink-700 py-8 text-center">
                <svg class="h-8 w-8 text-ink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm font-semibold text-white">No friends yet</p>
                <p class="text-xs text-ink-300">Discover people in the Suggestions tab.</p>
            </div>
            @endif
        </div>

        <!-- Requests Received Tab -->
        <div x-show="tab === 'received'" x-cloak>
            @if($receivedRequests->count())
            <div class="space-y-2">
                @foreach($receivedRequests as $request)
                <div class="flex items-center gap-3 rounded-xl border border-ink-700/60 bg-ink-800/40 p-2.5 transition hover:border-gold-300/30 hover:bg-ink-800">
                    <a href="{{ route('users.show', $request->sender) }}" class="shrink-0">
                        <x-avatar :user="$request->sender" size="sm" :online="$request->sender->is_online" />
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('users.show', $request->sender) }}" class="block truncate text-sm font-semibold text-white hover:text-gold-300">
                            {{ $request->sender->name }}
                        </a>
                        <p class="text-[11px] text-ink-300">{{ $request->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1.5">
                        <form method="POST" action="{{ route('friendships.accept', $request) }}" data-ajax data-replace="You are now friends.">
                            @csrf
                            <button class="grid h-8 w-8 place-items-center rounded-lg bg-gold-300 text-ink-900 transition hover:bg-gold-200" title="Accept">
                                <x-icon name="check" class="h-4 w-4" />
                            </button>
                        </form>
                        <form method="POST" action="{{ route('friendships.decline', $request) }}" data-ajax>
                            @csrf
                            <button class="grid h-8 w-8 place-items-center rounded-lg border border-ink-700 bg-ink-800 text-ink-300 transition hover:border-red-400/40 hover:text-red-400" title="Decline">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-ink-700 py-8 text-center">
                <svg class="h-8 w-8 text-ink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8v13H3V8M1 3h22v5H1zM10 12h4" />
                </svg>
                <p class="text-sm font-semibold text-white">All caught up</p>
                <p class="text-xs text-ink-300">No pending requests right now.</p>
            </div>
            @endif
        </div>

        <!-- Suggestions Tab -->
        <div x-show="tab === 'suggestions'" x-cloak>
            @if($suggestions->count())
            <div class="grid grid-cols-2 gap-2">
                @foreach($suggestions as $u)
                <div class="group relative flex flex-col items-center gap-2 rounded-xl border border-ink-700/60 bg-ink-800/40 p-3 text-center transition hover:border-gold-300/30 hover:bg-ink-800">
                    <a href="{{ route('users.show', $u) }}" class="flex flex-col items-center gap-2">
                        <x-avatar :user="$u" size="md" />
                        <div class="min-w-0 w-full">
                            <p class="truncate text-xs font-semibold text-white">{{ $u->name }}</p>
                            <p class="mt-0.5 text-[10px] text-ink-300">
                                {{ $u->mutual_friends_count ?? 0 }} mutual
                            </p>
                        </div>
                    </a>
                    <button type="button"
                        class="btn-gold w-full !py-1 !text-[10px] opacity-0 transition group-hover:opacity-100"
                        data-friend-add
                        data-url="{{ route('friends.add', $u) }}">
                        Add friend
                    </button>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-ink-700 py-8 text-center">
                <svg class="h-8 w-8 text-ink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <p class="text-sm font-semibold text-white">No suggestions</p>
                <p class="text-xs text-ink-300">Check back later for new people.</p>
            </div>
            @endif
        </div>

        <a href="{{ route('friends.index') }}"
            class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl border border-gold-300/20 bg-ink-800/60 px-3 py-2 text-xs font-semibold text-gold-300 transition hover:border-gold-300/40 hover:bg-ink-800 hover:text-gold-200 active:scale-[.99]">
            View All
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</aside>
@extends('layouts.app')
@section('title', 'Friends')

@section('content')
@php
    $onlineCount = $friends->filter(fn ($f) => $f->is_online)->count();
@endphp

<div class="space-y-6">

    {{-- Header with stats --}}
    <section class="card relative overflow-hidden p-6 sm:p-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(600px_220px_at_100%_0%,rgba(212,166,58,.2),transparent)]"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-400">Your circle</p>
                <h1 class="mt-1 font-display text-4xl font-bold sm:text-5xl">Friends</h1>
                <p class="mt-2 max-w-md text-ink-200">Keep up with the people who matter, find new ones, and answer requests.</p>
            </div>

            <dl class="flex gap-3">
                <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                    <dd class="font-display text-3xl font-bold text-gold-300">{{ $friends->count() }}</dd>
                    <dt class="text-xs text-ink-300">Friends</dt>
                </div>
                <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                    <dd class="flex items-center justify-center gap-1.5 font-display text-3xl font-bold text-gold-300">
                        @if($onlineCount)<span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>@endif{{ $onlineCount }}
                    </dd>
                    <dt class="text-xs text-ink-300">Online</dt>
                </div>
                <div class="min-w-[92px] rounded-2xl border border-ink-700 bg-ink-950/50 px-4 py-3 text-center">
                    <dd class="font-display text-3xl font-bold text-gold-300">{{ $incoming->count() }}</dd>
                    <dt class="text-xs text-ink-300">Requests</dt>
                </div>
            </dl>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

        {{-- MAIN --}}
        <div class="space-y-10">

            {{-- Friends --}}
            <section aria-label="Your friends">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-3xl font-bold">All friends</h2>
                    @if($friends->isNotEmpty())
                        <input id="friend-search" type="search" placeholder="Search friends" aria-label="Search friends"
                               class="input !w-full !rounded-full !py-2.5 text-sm sm:!w-64">
                    @endif
                </div>

                @if($friends->isEmpty())
                    <div class="card px-6 py-14 text-center">
                        <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><x-icon name="users" class="h-7 w-7"/></div>
                        <p class="font-display text-3xl font-bold text-gold-300">No friends yet</p>
                        <p class="mx-auto mt-2 max-w-sm text-ink-300">Add people from the suggestions below and they will show up here.</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($friends as $friend)
                            <div class="card group overflow-hidden transition hover:-translate-y-0.5 hover:border-gold-500/50 {{ $friend->unread_messages_count ? '!border-gold-500/60' : '' }}"
                                 data-ajax-row data-friend data-name="{{ \Illuminate\Support\Str::lower($friend->name) }}">
                                <div class="px-4 py-5 text-center">
                                    <a href="{{ route('users.show', $friend) }}" class="inline-block" aria-label="{{ $friend->name }}'s profile">
                                        <span class="relative inline-block">
                                            <x-avatar :user="$friend" size="lg" :online="$friend->is_online"/>
                                            @if($friend->unread_messages_count)
                                                <span class="absolute -right-2 -top-2 grid h-6 min-w-6 place-items-center rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950 ring-2 ring-ink-900">
                                                    {{ $friend->unread_messages_count > 99 ? '99+' : $friend->unread_messages_count }}
                                                </span>
                                            @endif
                                        </span>
                                    </a>
                                    <a href="{{ route('users.show', $friend) }}" class="mt-2 block truncate font-bold hover:text-gold-300">{{ $friend->name }}</a>
                                    <p class="mt-0.5 text-xs {{ $friend->is_online ? 'text-emerald-400' : 'text-ink-300' }}">
                                        {{ $friend->is_online ? 'Online now' : 'Offline' }}
                                    </p>
                                    @if($friend->unread_messages_count)
                                        <p class="mt-1 text-xs font-semibold text-gold-300">
                                            {{ $friend->unread_messages_count }} new {{ \Illuminate\Support\Str::plural('message', $friend->unread_messages_count) }}
                                        </p>
                                    @endif

                                    <div class="mt-4 flex gap-2">
                                        <form method="POST" action="{{ route('conversations.store') }}" class="flex-1">@csrf
                                            <input type="hidden" name="user_id" value="{{ $friend->id }}">
                                            <button class="btn-gold w-full !px-3 !py-2 !text-xs"><x-icon name="chat" class="h-4 w-4"/> {{ $friend->unread_messages_count ? 'Reply' : 'Message' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('friends.destroy', $friend) }}" data-ajax data-confirm="Remove this friend?">@csrf @method('DELETE')
                                            <button class="btn-ghost !p-2" aria-label="Unfriend {{ $friend->name }}" title="Unfriend"><x-icon name="x" class="h-4 w-4"/></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p id="no-match" class="card mt-4 hidden px-6 py-10 text-center text-ink-300">No friends match your search.</p>
                @endif
            </section>

            {{-- Suggestions --}}
            <section aria-label="People you may know">
                <div class="mb-4">
                    <h2 class="font-display text-3xl font-bold">People you may know</h2>
                    <p class="text-sm text-ink-300">Suggested from your mutual friends.</p>
                </div>

                @if($suggestions->isEmpty())
                    <div class="card px-6 py-10 text-center text-ink-300">No suggestions right now. Check back soon.</div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($suggestions as $u)
                            <div class="card flex flex-col items-center p-5 text-center transition hover:-translate-y-0.5 hover:border-gold-500/50">
                                <a href="{{ route('users.show', $u) }}" aria-label="{{ $u->name }}'s profile"><x-avatar :user="$u" size="lg"/></a>
                                <a href="{{ route('users.show', $u) }}" class="mt-3 block max-w-full truncate font-bold hover:text-gold-300">{{ $u->name }}</a>
                                <p class="mt-0.5 text-xs text-ink-300">
                                    @if(($u->mutual_friends_count ?? 0) > 0)
                                        {{ $u->mutual_friends_count }} mutual {{ \Illuminate\Support\Str::plural('friend', $u->mutual_friends_count) }}
                                    @else
                                        New to your circle
                                    @endif
                                </p>
                                <button type="button" class="btn-ghost mt-4 w-full !py-2 !text-xs" data-friend-add data-url="{{ route('friends.add', $u) }}">
                                    <x-icon name="plus" class="h-4 w-4"/> Add friend
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- SIDEBAR: requests (shown first on phones when there are any) --}}
        <aside class="space-y-6 {{ $incoming->isNotEmpty() ? 'order-first lg:order-none' : '' }}">
            <section class="card overflow-hidden" aria-label="Friend requests">
                <div class="flex items-center justify-between border-b border-ink-700 bg-ink-800/50 px-5 py-4">
                    <h2 class="font-display text-2xl font-bold text-gold-300">Requests</h2>
                    @if($incoming->isNotEmpty())
                        <span class="rounded-full bg-gold-grad px-2.5 py-0.5 text-xs font-bold text-ink-950">{{ $incoming->count() }}</span>
                    @endif
                </div>

                <div class="p-3">
                    @forelse($incoming as $req)
                        <div class="flex flex-wrap items-center gap-3 rounded-xl p-2 transition hover:bg-ink-800" data-ajax-row>
                            <a href="{{ route('users.show', $req->sender) }}"><x-avatar :user="$req->sender" size="sm"/></a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('users.show', $req->sender) }}" class="block truncate text-sm font-semibold hover:text-gold-300">{{ $req->sender->name }}</a>
                                <p class="text-xs text-ink-300">{{ $req->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('friendships.accept', $req) }}" data-ajax data-replace="You are now friends.">@csrf
                                    <button class="btn-gold !px-3.5 !py-1.5 !text-xs">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('friendships.decline', $req) }}" data-ajax>@csrf
                                    <button class="btn-ghost !px-3.5 !py-1.5 !text-xs">Decline</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full border border-ink-700 text-gold-400"><x-icon name="check" class="h-5 w-5"/></div>
                            <p class="font-semibold">All caught up</p>
                            <p class="text-sm text-ink-300">No new requests right now.</p>
                        </div>
                    @endforelse
                </div>

                @if($outgoing->isNotEmpty())
                    <div class="border-t border-ink-700 p-3">
                        <h3 class="px-2 pb-2 pt-1 text-xs font-bold uppercase tracking-wider text-ink-300">Sent by you</h3>
                        @foreach($outgoing as $req)
                            <div class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-ink-800" data-ajax-row>
                                <a href="{{ route('users.show', $req->recipient) }}"><x-avatar :user="$req->recipient" size="sm"/></a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('users.show', $req->recipient) }}" class="block truncate text-sm font-semibold hover:text-gold-300">{{ $req->recipient->name }}</a>
                                    <p class="text-xs text-ink-300">Pending</p>
                                </div>
                                <form method="POST" action="{{ route('friends.destroy', $req->recipient) }}" data-ajax>@csrf @method('DELETE')
                                    <button class="btn-ghost !px-3 !py-1.5 !text-xs">Cancel</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>
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
            document.querySelectorAll('[data-friend]').forEach((card) => {
                const match = card.dataset.name.includes(term);
                card.classList.toggle('hidden', !match);
                if (match) shown++;
            });
            document.getElementById('no-match').classList.toggle('hidden', shown > 0);
        });
    }
</script>
@endpush
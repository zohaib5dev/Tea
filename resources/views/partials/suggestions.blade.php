<div class="card p-5">
    <h2 class="mb-3 font-display text-2xl font-bold text-gold-300">People you may know</h2>
    @forelse($suggestions as $u)
        <div class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-ink-800">
            <a href="{{ route('users.show', $u) }}"><x-avatar :user="$u" size="sm"/></a>
            <div class="min-w-0 flex-1">
                <a href="{{ route('users.show', $u) }}" class="block truncate text-sm font-semibold">{{ $u->name }}</a>
                <p class="text-xs text-ink-300">{{ $u->mutual_friends_count ?? 0 }} mutual friends</p>
            </div>
            <button type="button" class="btn-ghost !px-3 !py-1.5 !text-xs" data-friend-add data-url="{{ route('friends.add', $u) }}">Add</button>
        </div>
    @empty
        <p class="p-2 text-sm text-ink-300">No suggestions right now. Check back soon.</p>
    @endforelse
</div>

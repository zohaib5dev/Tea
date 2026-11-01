@php $canDelete = auth()->id() === $comment->user_id || auth()->id() === $post->user_id; @endphp
<div class="flex gap-3" data-comment>
    <a href="{{ route('users.show', $comment->user) }}"><x-avatar :user="$comment->user" size="xs"/></a>
    <div class="min-w-0 flex-1">
        <div class="rounded-2xl rounded-tl-md bg-ink-800 px-4 py-3">
            <a href="{{ route('users.show', $comment->user) }}" class="text-sm font-bold hover:text-gold-300">{{ $comment->user->name }}</a>
            <p class="mt-0.5 whitespace-pre-line break-words text-sm leading-relaxed">{{ $comment->body }}</p>
        </div>

        <div class="mt-1 flex items-center gap-4 px-2 text-xs text-ink-300">
            <span>{{ $comment->created_at->diffForHumans() }}</span>
            @if($canDelete)
                <button type="button" data-comment-delete data-url="{{ route('comments.destroy', $comment) }}" class="font-semibold hover:text-red-400">Delete</button>
            @endif
        </div>

        @if($depth === 0)
            <details class="mt-1 px-2">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-gold-400 hover:text-gold-300">
                    <x-icon name="reply" class="h-3.5 w-3.5"/> Reply
                </summary>
                <form method="POST" action="{{ route('comments.store', $post) }}" data-comment-form class="mt-2 flex gap-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <input name="body" required maxlength="1000" autocomplete="off" class="input !py-2 text-sm" placeholder="Reply to {{ explode(' ', $comment->user->name)[0] }}">
                    <button class="btn-gold !px-4 !py-2">Send</button>
                </form>
            </details>

            <div class="space-y-4 border-l border-ink-700 pl-4 [&>*:first-child]:mt-3" data-replies>
                @foreach($comment->replies as $reply)
                    @include('posts._comment', ['comment' => $reply, 'depth' => 1])
                @endforeach
            </div>
        @endif
    </div>
</div>

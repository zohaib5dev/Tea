@php
    $single = $single ?? false;   // true on the single post page: comments open automatically
    $mine = auth()->id() === $post->user_id;
    $mediaUrl = $post->media ? asset('storage/' . $post->media) : '';
    $edited = $post->updated_at && $post->created_at && $post->updated_at->gt($post->created_at->copy()->addSeconds(10));
@endphp
<article class="card overflow-hidden" id="post-{{ $post->id }}" data-post @if($single) data-single @endif>

    {{-- Header --}}
    <div class="flex items-center gap-3 p-5 pb-0">
        <a href="{{ route('users.show', $post->user) }}"><x-avatar :user="$post->user"/></a>

        <div class="min-w-0 flex-1">
            <a href="{{ route('users.show', $post->user) }}" class="font-bold hover:text-gold-300">{{ $post->user->name }}</a>
            <p class="text-xs text-ink-300">
                <a href="{{ route('posts.show', $post) }}" class="hover:text-gold-300 hover:underline" title="Open post">{{ $post->created_at->diffForHumans() }}</a>
                <span data-visibility-badge class="ml-1 rounded-full border border-ink-700 px-2 py-0.5 {{ $post->visibility === 'friends' ? 'hidden' : '' }}">{{ $post->visibility === 'public' ? 'Public' : 'Only me' }}</span>
                <span data-edited class="ml-1 italic {{ $edited ? '' : 'hidden' }}">edited</span>
            </p>
        </div>

        @if($mine)
            <div class="flex items-center gap-1">
                <button type="button" data-post-edit class="rounded-full p-2 text-ink-300 transition hover:bg-ink-800 hover:text-gold-300" aria-label="Edit post" title="Edit post">
                    <x-icon name="edit" class="h-4 w-4"/>
                </button>
                <button type="button" data-post-delete data-url="{{ route('posts.destroy', $post) }}" data-redirect="{{ route('feed') }}"
                        class="rounded-full p-2 text-ink-300 transition hover:bg-ink-800 hover:text-red-400" aria-label="Delete post" title="Delete post">
                    <x-icon name="trash" class="h-4 w-4"/>
                </button>
            </div>
        @endif
    </div>

    {{-- What everyone sees --}}
    <div data-post-content>
        <p data-post-body class="max-w-[62ch] whitespace-pre-line break-words px-5 pt-4 leading-relaxed {{ $post->body ? '' : 'hidden' }}">{{ $post->body }}</p>

        @if($post->media)
            <a data-post-media href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="mx-5 mt-4 block overflow-hidden rounded-xl border border-ink-700">
                <img src="{{ $mediaUrl }}" alt="Photo shared by {{ $post->user->name }}" loading="lazy" class="max-h-[460px] w-full object-cover">
            </a>
        @endif
    </div>

    {{-- Inline editor (owner only). Logic lives in public/js/post-edit.js --}}
    @if($mine)
        <form data-post-edit-form data-url="{{ route('posts.update', $post) }}" data-original-media="{{ $mediaUrl }}"
              class="hidden px-5 pt-4" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PATCH')

            <div class="rounded-2xl border border-gold-500/40 bg-ink-950/40 p-4">
                <textarea name="body" rows="3" maxlength="5000" class="input resize-none overflow-hidden" placeholder="What's on your mind?">{{ $post->body }}</textarea>
                <p class="mt-1 text-right text-xs text-ink-300" data-edit-count></p>

                <div data-edit-preview class="relative mt-3 {{ $post->media ? '' : 'hidden' }}">
                    <img data-edit-preview-img @if($post->media) src="{{ $mediaUrl }}" @endif alt="Photo preview" class="max-h-72 w-full rounded-xl border border-ink-700 object-cover">
                    <button type="button" data-edit-remove-photo class="absolute right-2 top-2 rounded-full bg-ink-950/80 p-1.5 hover:text-gold-300" aria-label="Remove photo">
                        <x-icon name="x" class="h-4 w-4"/>
                    </button>
                </div>
                <input type="hidden" name="remove_media" value="0" data-remove-media>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
                            <x-icon name="image" class="h-4 w-4"/>
                            <span data-edit-file-label>{{ $post->media ? 'Change photo' : 'Add photo' }}</span>
                            <input type="file" name="media" accept="image/*" class="hidden" data-edit-file>
                        </label>
                        <select name="visibility" class="rounded-xl border border-ink-700 bg-ink-800 px-3 py-2 text-sm text-ink-200 focus:border-gold-500 focus:outline-none" aria-label="Who can see this">
                            @foreach(['friends' => 'Friends', 'public' => 'Public', 'private' => 'Only me'] as $value => $label)
                                <option value="{{ $value }}" @selected($post->visibility === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-xs text-ink-300">Esc to cancel, Ctrl+Enter to save</span>
                </div>
            </div>

            <p data-post-edit-error class="mt-2 hidden text-sm text-red-300" role="alert"></p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button" data-post-edit-cancel class="btn-ghost">Cancel</button>
                <button type="submit" data-post-edit-save class="btn-gold">Save changes</button>
            </div>
        </form>
    @endif

    {{-- Counts --}}
    <div class="flex gap-4 px-5 pt-4 text-sm text-ink-300">
        <span><span data-like-count>{{ $post->likes_count ?? 0 }}</span> likes</span>
        <span><span data-comment-count>{{ $post->comments_count ?? 0 }}</span> comments</span>
    </div>

    {{-- Actions --}}
    <div class="mx-3 mt-3 grid grid-cols-3 border-t border-ink-700 py-1.5">
        <button type="button" data-like data-url="{{ route('posts.like', $post) }}"
                class="{{ ($post->liked ?? false) ? 'liked' : '' }} flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
            <x-icon name="heart"/> Like
        </button>
        <button type="button" data-comments-toggle aria-expanded="{{ $single ? 'true' : 'false' }}"
                class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
            <x-icon name="chat"/> Comment
        </button>
        <button type="button" data-share data-url="{{ route('posts.show', $post) }}"
                class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
            <x-icon name="share"/> <span data-share-label>Share</span>
        </button>
    </div>

    {{-- Comments --}}
    <div class="{{ $single ? '' : 'hidden' }} border-t border-ink-700 px-5 py-4" data-comments-panel
         data-url="{{ route('comments.index', $post) }}" @if($single) data-autoload @endif>
        <form method="POST" action="{{ route('comments.store', $post) }}" data-comment-form class="mb-4 flex items-center gap-3">
            @csrf
            <x-avatar :user="auth()->user()" size="xs"/>
            <input name="body" required maxlength="1000" autocomplete="off" class="input !rounded-full !py-2.5 text-sm" placeholder="Write a comment">
            <button class="btn-gold !px-4 !py-2">Send</button>
        </form>
        <div class="space-y-5" data-comments-list>
            <p class="text-sm text-ink-300">Loading comments…</p>
        </div>
    </div>
</article>

  <script src="{{ asset('js/post-edit.js') }}"></script>
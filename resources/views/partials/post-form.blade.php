<dialog id="composer" aria-labelledby="composer-title"
        class="w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-ink-700 bg-ink-900 p-0 text-ink-50 backdrop:bg-black/70 backdrop:backdrop-blur-sm">
    <form id="composer-form" method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
            <h2 id="composer-title" class="font-display text-2xl font-bold text-gold-300">Create post</h2>
            <button type="button" id="close-composer" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300" aria-label="Close"><x-icon name="x" class="h-5 w-5"/></button>
        </div>

        <div class="space-y-4 p-5">
            <div class="flex items-center gap-3">
                <x-avatar :user="$me" size="sm"/>
                <div>
                    <p class="text-sm font-bold">{{ $me->name }}</p>
                    <select name="visibility" class="mt-0.5 rounded-lg border border-ink-700 bg-ink-800 px-2 py-1 text-xs text-ink-200 focus:border-gold-500 focus:outline-none" aria-label="Who can see this">
                        <option value="friends">Friends</option>
                        <option value="public">Public</option>
                        <option value="private">Only me</option>
                    </select>
                </div>
            </div>

            <textarea name="body" id="composer-body" rows="5" maxlength="2000" class="input resize-none" placeholder="What's on your mind, {{ explode(' ', $me->name)[0] }}?"></textarea>

            <div id="preview-wrap" class="relative hidden">
                <img id="preview" alt="Selected photo" class="max-h-56 w-full rounded-xl border border-ink-700 object-cover">
                <button type="button" id="preview-clear" class="absolute right-2 top-2 rounded-full bg-ink-950/80 p-1.5 hover:text-gold-300" aria-label="Remove photo"><x-icon name="x" class="h-4 w-4"/></button>
            </div>

            <p id="composer-error" class="hidden text-sm text-red-300" role="alert"></p>
        </div>

        <div class="flex items-center justify-between border-t border-ink-700 px-5 py-4">
            <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-ink-800 hover:text-gold-300">
                <x-icon name="image" class="h-4 w-4"/> Add photo
                <input type="file" name="media" id="image" accept="image/*" class="hidden">
            </label>
            <button type="submit" id="composer-submit" class="btn-gold">Post</button>
        </div>
    </form>
</dialog>
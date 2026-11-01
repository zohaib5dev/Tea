@extends('layouts.app')
@section('title', $user->name)

@section('content')
@php
$me = auth()->user();
$isMe = $me->id === $user->id;
@endphp
<div class="mx-auto max-w-5xl space-y-6">
 
    <section class="card overflow-hidden">
        <div id="cover"
            class="relative h-44 bg-gradient-to-br from-gold-800 via-ink-800 to-ink-900 bg-cover bg-center sm:h-60"
            @if($user->cover_photo) style="background-image:url('{{ asset('storage/' . $user->cover_photo) }}')" @endif>
            <div class="absolute inset-0 bg-gradient-to-t from-ink-900/60 to-transparent"></div>

            @if($isMe)
            <label class="absolute right-4 top-4 flex cursor-pointer items-center gap-2 rounded-full border border-ink-700 bg-ink-950/70 px-4 py-2 text-xs font-semibold backdrop-blur transition hover:border-gold-500 hover:text-gold-300">
                <x-icon name="camera" class="h-4 w-4" /> Change cover
                <input type="file" accept="image/*" class="hidden" data-photo="cover">
            </label>
            @endif
        </div>

        <div class="px-6 pb-6 sm:px-8">
            <div class="-mt-16 flex flex-wrap items-end justify-between gap-4">

                <div class="relative" data-profile-avatar>
                    <x-avatar :user="$user" size="xl" :online="$user->is_online" />
                    @if($isMe)
                    <label class="absolute bottom-1 right-1 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-gold-grad text-ink-950 shadow-lg ring-2 ring-ink-900 transition hover:scale-105" title="Change profile picture">
                        <x-icon name="camera" class="h-4 w-4" />
                        <input type="file" accept="image/*" class="hidden" data-photo="avatar">
                    </label>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-4">
                    @if($isMe)
                    <button type="button" id="open-edit" class="btn-ghost"><x-icon name="edit" class="h-4 w-4" /> Edit profile</button>

                    @elseif(! $friendship)
                    <button type="button" class="btn-gold" data-friend-add data-url="{{ route('friends.add', $user) }}"><x-icon name="plus" class="h-4 w-4" /> Add friend</button>

                    @elseif($friendship->status === 'accepted')
                    <form method="POST" action="{{ route('conversations.store') }}">@csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <button class="btn-gold"><x-icon name="chat" class="h-4 w-4" /> Message</button>
                    </form>
                    <form method="POST" action="{{ route('friends.destroy', $user) }}" onsubmit="return confirm('Remove this friend?')">@csrf @method('DELETE')
                        <button class="btn-ghost">Unfriend</button>
                    </form>

                    @elseif($friendship->status === 'pending' && $friendship->sender_id === $me->id)
                    <span class="rounded-full border border-ink-700 px-4 py-2 text-sm text-ink-300">Request sent</span>
                    <form method="POST" action="{{ route('friends.destroy', $user) }}">@csrf @method('DELETE')
                        <button class="btn-ghost">Cancel</button>
                    </form>

                    @elseif($friendship->status === 'pending')
                    <form method="POST" action="{{ route('friendships.accept', $friendship) }}">@csrf
                        <button class="btn-gold"><x-icon name="check" class="h-4 w-4" /> Accept</button>
                    </form>
                    <form method="POST" action="{{ route('friendships.decline', $friendship) }}">@csrf
                        <button class="btn-ghost">Decline</button>
                    </form>
                    @endif
                </div>
            </div>

            <h1 class="mt-4 font-display text-4xl font-bold sm:text-5xl" data-profile-name>{{ $user->name }}</h1>
            <p class="mt-1 max-w-prose text-ink-200 {{ $user->bio ? '' : 'hidden' }}" data-profile-bio>{{ $user->bio }}</p>

            <dl class="mt-6 grid max-w-sm grid-cols-3 gap-3">
                <div class="rounded-2xl border border-ink-700 bg-ink-950/40 px-4 py-3 text-center">
                    <dd class="font-display text-3xl font-bold text-gold-300">{{ $friendsCount }}</dd>
                    <dt class="text-xs text-ink-300">Friends</dt>
                </div>
                <div class="rounded-2xl border border-ink-700 bg-ink-950/40 px-4 py-3 text-center">
                    <dd class="font-display text-3xl font-bold text-gold-300">{{ $posts->total() }}</dd>
                    <dt class="text-xs text-ink-300">Posts</dt>
                </div>
                <div class="rounded-2xl border border-ink-700 bg-ink-950/40 px-4 py-3 text-center">
                    <dd class="font-display text-3xl font-bold text-gold-300">{{ $photosCount }}</dd>
                    <dt class="text-xs text-ink-300">Photos</dt>
                </div>
            </dl>
        </div>
    </section>
 
    <section aria-label="Profile content">
        <div class="card mb-6 grid grid-cols-2 gap-1 p-1.5" role="tablist">
            <button type="button" role="tab" id="tab-posts" aria-controls="panel-posts" aria-selected="true" data-tab="posts"
                class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition bg-gold-grad text-ink-950 shadow">
                <x-icon name="chat" class="h-4 w-4" /> Posts
                <span class="rounded-full bg-ink-950/20 px-2 py-0.5 text-xs">{{ $posts->total() }}</span>
            </button>
            <button type="button" role="tab" id="tab-photos" aria-controls="panel-photos" aria-selected="false" data-tab="photos"
                class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition text-ink-300 hover:bg-ink-800">
                <x-icon name="image" class="h-4 w-4" /> Photos
                <span class="rounded-full bg-ink-950/20 px-2 py-0.5 text-xs">{{ $photosCount }}</span>
            </button>
        </div>
 
        <div id="panel-posts" role="tabpanel" aria-labelledby="tab-posts" data-panel="posts" class="mx-auto  space-y-6">
            @forelse($posts as $post)
            @include('partials.post-card', ['post' => $post])
            @empty
            <div class="card px-6 py-14 text-center">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><x-icon name="chat" class="h-7 w-7" /></div>
                <p class="font-display text-3xl font-bold text-gold-300">Nothing to show yet</p>
                <p class="mt-1 text-ink-300">{{ $isMe ? 'Share your first post from the feed.' : 'Posts you can see will appear here.' }}</p>
                @if($isMe)<a href="{{ route('feed') }}" class="btn-gold mt-6">Go to feed</a>@endif
            </div>
            @endforelse

            {{ $posts->links('pagination.gold') }}
        </div>

        {{-- Just the pictures --}}
        <div id="panel-photos" role="tabpanel" aria-labelledby="tab-photos" data-panel="photos" class="hidden">
            @if($photos->isEmpty())
            <div class="card px-6 py-14 text-center">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-gold-grad text-ink-950"><x-icon name="image" class="h-7 w-7" /></div>
                <p class="font-display text-3xl font-bold text-gold-300">No photos yet</p>
                <p class="mt-1 text-ink-300">{{ $isMe ? 'Pictures you add to your posts will appear here.' : 'Photos you can see will appear here.' }}</p>
                @if($isMe)<a href="{{ route('feed') }}" class="btn-gold mt-6">Post a photo</a>@endif
            </div>
            @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($photos as $photo)
                <a href="{{ route('posts.show', $photo['id']) }}" class="group relative block aspect-square overflow-hidden rounded-2xl border border-ink-700 bg-ink-800" aria-label="Open the post with this photo">
                    <img src="{{ $photo['url'] }}" alt="Photo from a post" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink-950/60 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                </a>
                @endforeach
            </div>
            @if($photosCount > $photos->count())
            <p class="mt-4 text-center text-sm text-ink-300">Showing the latest {{ $photos->count() }} of {{ $photosCount }} photos.</p>
            @endif
            @endif
        </div>
    </section>
</div>

@if($isMe)
{{-- Edit profile dialog --}}
<dialog id="edit-dialog" aria-labelledby="edit-title"
    class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-ink-700 bg-ink-900 p-0 text-ink-50 backdrop:bg-black/70 backdrop:backdrop-blur-sm">
    <form id="edit-form" method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PATCH')
        <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
            <h2 id="edit-title" class="font-display text-2xl font-bold text-gold-300">Edit profile</h2>
            <button type="button" id="close-edit" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300" aria-label="Close"><x-icon name="x" class="h-5 w-5" /></button>
        </div>
        <div class="space-y-4 p-5">
            <div>
                <label for="edit-name" class="mb-1.5 block text-sm font-semibold text-ink-200">Name</label>
                <input id="edit-name" name="name" value="{{ $user->name }}" required maxlength="255" class="input">
            </div>
            <div>
                <label for="edit-bio" class="mb-1.5 block text-sm font-semibold text-ink-200">Bio</label>
                <textarea id="edit-bio" name="bio" rows="3" maxlength="255" class="input resize-none" placeholder="Tell people a little about you">{{ $user->bio }}</textarea>
            </div>
            <p id="edit-error" class="hidden text-sm text-red-300" role="alert"></p>
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-700 px-5 py-4">
            <button type="button" id="cancel-edit" class="btn-ghost">Cancel</button>
            <button type="submit" id="save-edit" class="btn-gold">Save</button>
        </div>
    </form>
</dialog>
@endif



<button type="button" id="open-composer"
    class="fixed bottom-24 right-5 z-40 flex h-14 items-center justify-center gap-2 rounded-full bg-gold-grad px-4 font-bold text-ink-950 shadow-xl shadow-gold-500/30 transition hover:-translate-y-1 hover:shadow-gold-500/50 active:scale-95 md:bottom-8 md:right-8 md:px-6"
    aria-label="Create a new post">
    <x-icon name="plus" class="h-6 w-6" />
    <span class="hidden text-sm md:inline">New post</span>
</button>

@include('partials.post-form')

@endsection

@if($isMe)
@push('scripts')
<script>
    // ---------- Upload profile picture / cover photo ----------
    const MAX_BYTES = 4 * 1024 * 1024;
    const PHOTO_URL = @json(route('profile.photo'));
    const coverEl = document.getElementById('cover');

    const avatarSpan = () => document.querySelector('[data-profile-avatar] > span');
    const headerAvatarSpan = () => document.querySelector('header a[aria-label="Your profile"] > span');

    // Put a picture inside an avatar circle (replaces initials or the old picture, keeps the online dot)
    function setAvatar(span, url) {
        if (!span) return;
        span.querySelectorAll('img').forEach((i) => i.remove());
        [...span.childNodes].forEach((n) => {
            if (n.nodeType === Node.TEXT_NODE) n.remove();
        });
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        img.className = 'h-full w-full rounded-full object-cover';
        span.prepend(img);
    }

    function applyPhoto(type, url) {
        if (type === 'cover') {
            coverEl.style.backgroundImage = "url('" + url + "')";
            return;
        }
        setAvatar(avatarSpan(), url);
        setAvatar(headerAvatarSpan(), url);
    }

    document.querySelectorAll('[data-photo]').forEach((input) => {
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) return;
            if (file.size > MAX_BYTES) {
                toast('Please choose a photo under 4 MB.');
                input.value = '';
                return;
            }

            const type = input.dataset.photo;
            const target = type === 'cover' ? coverEl : avatarSpan();
            const before = type === 'cover' ? coverEl.style.backgroundImage : target.innerHTML;

            applyPhoto(type, URL.createObjectURL(file)); // show it instantly
            toast('Uploading…');

            const body = new FormData();
            body.append('type', type);
            body.append('photo', file);

            try {
                const r = await fetch(PHOTO_URL, {
                    method: 'POST',
                    headers: HEADERS,
                    body
                });
                const d = await r.json();
                if (!r.ok) throw new Error((d.errors && d.errors.photo && d.errors.photo[0]) || d.message || 'Upload failed.');
                applyPhoto(type, d.url);
                toast(type === 'cover' ? 'Cover photo updated.' : 'Profile picture updated.');
            } catch (err) {
                if (type === 'cover') coverEl.style.backgroundImage = before;
                else target.innerHTML = before; // undo
                toast(err.message);
            }
            input.value = '';
        });
    });

    // ---------- Edit name and bio ----------
    const editDialog = document.getElementById('edit-dialog');
    const editForm = document.getElementById('edit-form');
    const editError = document.getElementById('edit-error');
    const saveBtn = document.getElementById('save-edit');

    document.getElementById('open-edit').addEventListener('click', () => editDialog.showModal());
    ['close-edit', 'cancel-edit'].forEach((id) => document.getElementById(id).addEventListener('click', () => editDialog.close()));
    editDialog.addEventListener('click', (e) => {
        if (e.target === editDialog) editDialog.close();
    });

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        editError.classList.add('hidden');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        try {
            const r = await fetch(editForm.action, {
                method: 'POST',
                headers: HEADERS,
                body: new FormData(editForm)
            });
            const d = await r.json();
            if (!r.ok) throw new Error((d.errors && Object.values(d.errors)[0][0]) || d.message || 'Could not save.');
            document.querySelector('[data-profile-name]').textContent = d.name;
            const bio = document.querySelector('[data-profile-bio]');
            bio.textContent = d.bio;
            bio.classList.toggle('hidden', !d.bio);
            editDialog.close();
            toast('Profile updated.');
        } catch (err) {
            editError.textContent = err.message;
            editError.classList.remove('hidden');
        }
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
    });
</script>
@endpush
@endif

@push('scripts')
<script>
    // ---------- Posts / Photos tabs ----------
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tabPanels = document.querySelectorAll('[data-panel]');
    const TAB_ON = ['bg-gold-grad', 'text-ink-950', 'shadow'];
    const TAB_OFF = ['text-ink-300', 'hover:bg-ink-800'];

    function openTab(name, updateUrl = true) {
        tabButtons.forEach((b) => {
            const on = b.dataset.tab === name;
            b.setAttribute('aria-selected', on);
            TAB_ON.forEach((c) => b.classList.toggle(c, on));
            TAB_OFF.forEach((c) => b.classList.toggle(c, !on));
        });
        tabPanels.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== name));
        if (updateUrl) history.replaceState(null, '', name === 'posts' ? location.pathname + location.search : '#' + name);
    }

    tabButtons.forEach((b) => b.addEventListener('click', () => openTab(b.dataset.tab)));
    openTab(location.hash === '#photos' ? 'photos' : 'posts', false); // links like /users/5#photos open the Photos tab
</script>


<script>
    const dialog = document.getElementById('composer');
    const postForm = document.getElementById('composer-form');
    const postsEl = document.getElementById('panel-posts');
    const bodyField = document.getElementById('composer-body');
    const imageField = document.getElementById('image');
    const previewWrap = document.getElementById('preview-wrap');
    const previewImg = document.getElementById('preview');
    const errorEl = document.getElementById('composer-error');
    const submitBtn = document.getElementById('composer-submit');

    // Open / close the modal
    document.getElementById('open-composer').addEventListener('click', () => {
        dialog.showModal();
        bodyField.focus();
    });
    document.getElementById('close-composer').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    }); // click on backdrop

    // Photo preview
    imageField.addEventListener('change', () => {
        const f = imageField.files[0];
        if (!f) return previewWrap.classList.add('hidden');
        previewImg.src = URL.createObjectURL(f);
        previewWrap.classList.remove('hidden');
    });
    document.getElementById('preview-clear').addEventListener('click', () => {
        imageField.value = '';
        previewWrap.classList.add('hidden');
    });

    // Publish without reloading
    postForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorEl.classList.add('hidden');
        if (!bodyField.value.trim() && !imageField.files.length) {
            errorEl.textContent = 'Write something or add a photo first.';
            return errorEl.classList.remove('hidden');
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Posting…';
        try {
            const r = await fetch(postForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: new FormData(postForm)
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.message || 'Could not publish your post.');
            document.getElementById('empty-feed')?.remove();
            if (d.html) {
                const emptyFeed = postsEl.querySelector('.card.text-center');
                emptyFeed?.remove();

                const postElement = fragment(d.html);
                postsEl.prepend(postElement);
 
                postsEl.querySelector('#empty-feed')?.remove();
            }
            postForm.reset();
            previewWrap.classList.add('hidden');
            dialog.close();
            toast('Your post is live.');
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.classList.remove('hidden');
        }
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post';
    });
</script>






@endpush
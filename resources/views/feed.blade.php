@extends('layouts.app')
@section('title', 'Feed')

@section('content')
@php $me = auth()->user(); @endphp
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">

    <section class="flex flex-col gap-6" aria-label="Posts">

        <button type="button" id="open-composer"
            class="fixed bottom-24 right-5 z-40 flex h-14 items-center justify-center gap-2 rounded-full bg-gold-grad px-4 font-bold text-ink-950 shadow-xl shadow-gold-500/30 transition hover:-translate-y-1 hover:shadow-gold-500/50 active:scale-95 md:bottom-8 md:right-8 md:px-6"
            aria-label="Create a new post">
            <x-icon name="plus" class="h-6 w-6" />
            <span class="hidden text-sm md:inline">New post</span>
        </button>

        @if($posts->isEmpty())
        <div id="empty-feed" class="card px-6 py-14 text-center">
            <p class="font-display text-3xl font-bold text-gold-300">Your feed is quiet</p>
            <p class="mt-2 text-ink-300">Create your first post, or add friends to see what they share.</p>
            <a href="{{ route('friends.index') }}" class="btn-gold mt-6">Find friends</a>
        </div>
        @endif

        <div id="posts" class="flex flex-col gap-6" data-next="{{ $posts->nextPageUrl() }}">
            @include('partials.post-list')
        </div>

        <div id="sentinel" class="py-4 text-center text-sm text-ink-300 {{ $posts->hasMorePages() ? '' : 'hidden' }}">Loading more…</div>
    </section>

    @include('partials.sidebar')
</div>

@include('partials.post-form')

@endsection

@push('scripts')
<script>
    const dialog = document.getElementById('composer');
    const postForm = document.getElementById('composer-form');
    const postsEl = document.getElementById('posts');
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
            postsEl.prepend(fragment(d.html));
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

    // Infinite scroll: load the next page as you reach the bottom
    let next = postsEl.dataset.next;
    let loading = false;
    const sentinel = document.getElementById('sentinel');

    async function loadMore() {
        if (!next || loading) return;
        loading = true;
        try {
            const r = await fetch(next, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!r.ok) throw 0;
            const d = await r.json();
            postsEl.append(fragment(d.html));
            next = d.next;
        } catch {
            toast('Could not load more posts.');
        }
        loading = false;
        if (!next) sentinel.classList.add('hidden');
    }
    if (next) new IntersectionObserver((entries) => entries[0].isIntersecting && loadMore(), {
        rootMargin: '500px'
    }).observe(sentinel);
</script>
@endpush
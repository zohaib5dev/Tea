@php
    $me = auth()->user();
    $pending = $me ? $me->receivedFriendships()->pending()->count() : 0;
    $links = [
        ['feed', 'Feed', 'home', ['feed', 'posts.*']],
        ['friends.index', 'Friends', 'users', ['friends.*']],
        ['conversations.index', 'Messages', 'chat', ['conversations.*']],
        ['profile', 'Profile', 'user', ['profile', 'users.*']],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Feed') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.10/dist/cdn.min.js"></script>
    {{-- Tailwind Play CDN: perfect for development. For production, move the config below into
         tailwind.config.js and the <style> block into resources/css/app.css (see README). --}}
    <script src="https://cdn.tailwindcss.com"></script>
    @verbatim
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    gold: { 50:'#fdf8e7', 100:'#faefc2', 200:'#f6e08d', 300:'#f3d57a', 400:'#e5bd4f', 500:'#d4a63a', 600:'#b58626', 700:'#9a7418', 800:'#6f5212', 900:'#4a360c' },
                    ink: { 50:'#f6efde', 200:'#cfc3a3', 300:'#a89a78', 700:'#3a2e17', 800:'#231c10', 900:'#1a150c', 950:'#0f0c07' }
                },
                fontFamily: { display: ['Cormorant Garamond', 'serif'], sans: ['Manrope', 'system-ui', 'sans-serif'] },
                backgroundImage: { 'gold-grad': 'linear-gradient(135deg, #f3d57a 0%, #d4a63a 50%, #9a7418 100%)' }
            } }
        }
    </script>
    <style type="text/tailwindcss">
    @layer utilities {
    /* Themed scrollbar — gold thumb on a dark ink track */
    .scrollbar-gold {
        scrollbar-width: thin;                       /* Firefox */
        scrollbar-color: rgba(212, 175, 55, 0.35) transparent;
    }

    /* WebKit (Chrome / Edge / Safari) */
    .scrollbar-gold::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .scrollbar-gold::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 999px;
        margin-block: 4px;
    }

    .scrollbar-gold::-webkit-scrollbar-thumb {
        background-color: rgba(212, 175, 55, 0.25);
        border-radius: 999px;
        border: 2px solid transparent;
        background-clip: content-box;
        transition: background-color 0.2s ease;
    }

    .scrollbar-gold:hover::-webkit-scrollbar-thumb {
        background-color: rgba(212, 175, 55, 0.5);
    }

    .scrollbar-gold::-webkit-scrollbar-thumb:hover {
        background-color: rgba(212, 175, 55, 0.7);
    }

    .scrollbar-gold::-webkit-scrollbar-corner {
        background: transparent;
    }
}
        @layer base {
            body { @apply bg-ink-950 font-sans text-ink-50 antialiased; background-image: radial-gradient(900px 400px at 50% -100px, rgba(212,166,58,.16), transparent 70%); background-attachment: fixed; }
            :focus-visible { @apply outline outline-2 outline-offset-2 outline-gold-300; }
        }
        @layer components {
            .card { @apply rounded-2xl border border-ink-700 bg-ink-900; }
            .btn-gold { @apply inline-flex items-center justify-center gap-2 rounded-full bg-gold-grad px-5 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-gold-500/20 transition hover:-translate-y-0.5 hover:shadow-gold-500/40; }
            .btn-ghost { @apply inline-flex items-center justify-center gap-2 rounded-full border border-gold-500/60 px-4 py-2 text-sm font-semibold text-gold-300 transition hover:border-transparent hover:bg-gold-grad hover:text-ink-950; }
            .input { @apply w-full rounded-xl border border-ink-700 bg-ink-800 px-4 py-3 text-ink-50 placeholder-ink-300 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/20; }
            .liked { @apply text-gold-300; }
            .liked svg { fill: #d4a63a; stroke: #d4a63a; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
    @endverbatim
    @stack('head')
</head>
<body class="min-h-screen">

<header class="sticky top-0 z-30 border-b border-ink-700 bg-ink-950/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
        <a href="{{ route('feed') }}" class="bg-gold-grad bg-clip-text font-display text-3xl font-bold text-transparent">{{ config('app.name') }}</a>

        <nav class="ml-auto hidden items-center gap-1 md:flex" aria-label="Main">
            @foreach($links as [$route, $label, $icon, $patterns])
                <a href="{{ route($route) }}"
                   class="relative flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs(...$patterns) ? 'bg-ink-800 text-gold-300' : 'text-ink-300 hover:bg-ink-800 hover:text-gold-300' }}">
                     @if($route === 'profile' && $me)
        <x-avatar :user="$me" size="xs"/>
    @else
        <x-icon :name="$icon" class="h-4 w-4"/>
        {{ $label }}
    @endif
                    @if($route === 'friends.index' && $pending)
                        <span class="rounded-full bg-gold-grad px-1.5 text-xs font-bold text-ink-950">{{ $pending }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        @if($me)
            <div class="ml-auto flex items-center gap-3 md:ml-2">
              {{--   <a href="{{ route('profile') }}" aria-label="Your profile">
                <x-avatar :user="$me" size="xs"/></a> --}}
                @if(Route::has('logout'))
                    <form method="POST" class="rounded-full bg-ink-800 px-2.5" action="{{ route('logout') }}">@csrf
                        <button label="Log out" class="text-sm font-semibold text-ink-300 transition hover:text-gold-300"> <x-icon label="Log out" name="logout" class="mt-3 text-red-500 h-5 w-5"/></button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</header>

<main class="mx-auto max-w-7xl px-4 pb-28 pt-6 md:pb-12">
    @if(session('status'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-gold-500/40 bg-gold-900/30 px-4 py-3 text-sm text-gold-200" role="status">
            <x-icon name="check" class="h-4 w-4 shrink-0 text-gold-300"/> {{ session('status') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-400/40 bg-red-950/40 px-4 py-3 text-sm text-red-200" role="alert">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @yield('content')
</main>

{{-- Mobile bottom navigation --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-700 bg-ink-950/95 backdrop-blur md:hidden" aria-label="Main">
    <div class="grid grid-cols-4">
        @foreach($links as [$route, $label, $icon, $patterns])
            <a href="{{ route($route) }}" class="relative flex flex-col items-center gap-1 py-3 text-xs font-semibold {{ request()->routeIs(...$patterns) ? 'text-gold-300' : 'text-ink-300' }}">
                            @if($route === 'profile' && $me)
        <x-avatar :user="$me" size="sm" class="h-5 w-5"/>
    @else
        <x-icon :name="$icon" class="h-4 w-4"/>
    @endif
    {{ $label }}
                @if($route === 'friends.index' && $pending)
                    <span class="absolute right-6 top-1.5 h-2.5 w-2.5 rounded-full bg-gold-400"></span>
                @endif
            </a>
        @endforeach
    </div>
</nav>

<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const HEADERS = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
    const send = (url, method = 'POST') => fetch(url, { method, headers: HEADERS });

    // Turn an HTML string from the server into DOM nodes
    const fragment = (html) => { const t = document.createElement('template'); t.innerHTML = html.trim(); return t.content; };
    const setCount = (card, n) => card.querySelectorAll('[data-comment-count]').forEach((el) => el.textContent = n);

    function toast(message) {
        const t = document.createElement('div');
        t.className = 'fixed bottom-24 left-1/2 z-50 -translate-x-1/2 rounded-full border border-gold-500/50 bg-ink-900 px-5 py-2.5 text-sm text-gold-200 shadow-xl md:bottom-8';
        t.setAttribute('role', 'status');
        t.textContent = message;
        document.body.append(t);
        setTimeout(() => t.remove(), 2600);
    }

    // Load a post's comments once, the first time they are opened
    async function loadComments(panel) {
        if (panel.dataset.loaded) return;
        panel.dataset.loaded = '1';
        const list = panel.querySelector('[data-comments-list]');
        try {
            const r = await fetch(panel.dataset.url, { headers: { 'Accept': 'application/json' } });
            if (!r.ok) throw 0;
            const d = await r.json();
            list.innerHTML = d.html || '<p class="text-sm text-ink-300" data-no-comments>No comments yet. Be the first.</p>';
            setCount(panel.closest('[data-post]'), d.count);
        } catch {
            panel.dataset.loaded = '';
            list.innerHTML = '<p class="text-sm text-red-300">Could not load comments. Click Comment to try again.</p>';
        }
    }

    document.addEventListener('click', async (e) => {
        // Show / hide comments under a post
        const toggle = e.target.closest('[data-comments-toggle]');
        if (toggle) {
            const panel = toggle.closest('[data-post]').querySelector('[data-comments-panel]');
            const open = panel.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', open);
            if (open) { loadComments(panel); panel.querySelector('[name="body"]').focus(); }
            return;
        }

        // Like / unlike
        const like = e.target.closest('[data-like]');
        if (like) {
            const counter = like.closest('[data-post]').querySelector('[data-like-count]');
            const on = like.classList.toggle('liked');
            counter.textContent = Math.max(0, +counter.textContent + (on ? 1 : -1));
            try {
                const r = await send(like.dataset.url);
                if (!r.ok) throw 0;
                const d = await r.json();
                like.classList.toggle('liked', d.liked);
                counter.textContent = d.count;
            } catch {
                like.classList.toggle('liked');
                counter.textContent = Math.max(0, +counter.textContent + (on ? -1 : 1));
            }
            return;
        }

        // Delete a comment
        const cdel = e.target.closest('[data-comment-delete]');
        if (cdel) {
            if (!confirm('Delete this comment?')) return;
            const r = await send(cdel.dataset.url, 'DELETE');
            if (!r.ok) return toast('Could not delete the comment.');
            const d = await r.json();
            const card = cdel.closest('[data-post]');
            cdel.closest('[data-comment]').remove();
            setCount(card, d.count);
            return;
        }

        // Delete a post
        const pdel = e.target.closest('[data-post-delete]');
        if (pdel) {
            if (!confirm('Delete this post?')) return;
            const r = await send(pdel.dataset.url, 'DELETE');
            if (!r.ok) return toast('Could not delete the post.');
            const card = pdel.closest('[data-post]');
            if (card.hasAttribute('data-single')) { location.href = pdel.dataset.redirect; return; }
            card.remove();
            toast('Post deleted.');
            return;
        }

        // Send friend request
        const add = e.target.closest('[data-friend-add]');
        if (add) {
            add.disabled = true; add.textContent = 'Sending…';
            try { const r = await send(add.dataset.url); if (!r.ok) throw 0; add.textContent = 'Requested'; add.classList.add('opacity-60', 'pointer-events-none'); }
            catch { add.textContent = 'Try again'; add.disabled = false; }
            return;
        }

        // Share
        const share = e.target.closest('[data-share]');
        if (share) {
            const url = share.dataset.url;
            if (navigator.share) { try { await navigator.share({ url }); } catch {} return; }
            try {
                await navigator.clipboard.writeText(url);
                const label = share.querySelector('[data-share-label]');
                label.textContent = 'Link copied';
                setTimeout(() => label.textContent = 'Share', 1800);
            } catch {}
        }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target;

        // Add a comment or reply without reloading
        if (form.matches('[data-comment-form]')) {
            e.preventDefault();
            const input = form.querySelector('[name="body"]');
            if (!input.value.trim()) return;
            const btn = form.querySelector('button');
            btn.disabled = true;
            try {
                const r = await fetch(form.action, { method: 'POST', headers: HEADERS, body: new FormData(form) });
                if (!r.ok) throw 0;
                const d = await r.json();
                const isReply = form.querySelector('[name="parent_id"]');
                if (isReply) {
                    form.closest('[data-comment]').querySelector('[data-replies]').append(fragment(d.html));
                    form.closest('details').open = false;
                } else {
                    const list = form.closest('[data-comments-panel]').querySelector('[data-comments-list]');
                    list.querySelector('[data-no-comments]')?.remove();
                    list.prepend(fragment(d.html));
                }
                input.value = '';
                setCount(form.closest('[data-post]'), d.count);
            } catch { toast('Could not post your comment.'); }
            btn.disabled = false;
            return;
        }

        // Generic AJAX form: accept / decline / unfriend etc. (data-ajax, optional data-confirm and data-replace)
        if (form.matches('[data-ajax]')) {
            e.preventDefault();
            if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;
            try {
                const r = await fetch(form.action, { method: 'POST', headers: HEADERS, body: new FormData(form) });
                if (!r.ok) throw 0;
                const d = await r.json().catch(() => ({}));
                toast(d.message || 'Done.');
                const row = form.closest('[data-ajax-row]');
                if (row && form.dataset.replace) {
                    const p = document.createElement('p');
                    p.className = 'w-full p-2 text-sm text-gold-300';
                    p.textContent = form.dataset.replace;
                    row.replaceChildren(p);
                } else if (row) {
                    row.remove();
                }
            } catch { toast('Something went wrong. Please try again.'); }
        }
    });

    // Single post page: open the comments straight away
    document.querySelectorAll('[data-comments-panel][data-autoload]').forEach(loadComments);
</script>
@auth
    @include('partials.echo')
    @include('partials.calls')
@endauth
@stack('scripts')
</body>
</html>

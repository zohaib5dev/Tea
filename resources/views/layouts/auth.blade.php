<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Welcome' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-900 text-white antialiased font-sans">

    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 -left-40 h-[32rem] w-[32rem] rounded-full bg-gold-300/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 h-[32rem] w-[32rem] rounded-full bg-gold-300/5 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(212,175,55,0.08),_transparent_60%)]"></div>
    </div>

    <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">

        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 group">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gold-300/10 ring-1 ring-gold-300/30 transition group-hover:ring-gold-300/60">
                <svg class="h-6 w-6 text-gold-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.6 5.3 5.9.9-4.3 4.2 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9L12 3z"/>
                </svg>
            </span>
            <span class="font-display text-2xl font-bold tracking-tight text-gold-300">
                {{ config('app.name', 'Aurum') }}
            </span>
        </a>

        <div class="w-full max-w-md">
            <div class="card p-8 shadow-2xl shadow-black/40">
                @yield('content')
            </div>

            <p class="mt-6 text-center text-sm text-ink-300">
                @yield('footer')
            </p>
        </div>
    </div>
</body>
</html>
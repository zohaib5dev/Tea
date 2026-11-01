@extends('layouts.app')
@section('title', 'Post by ' . $post->user->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <a href="{{ route('feed') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-300 transition hover:text-gold-300">
        <x-icon name="back" class="h-4 w-4"/> Back to feed
    </a>

    @include('partials.post-card', ['post' => $post, 'single' => true])
</div>
@endsection

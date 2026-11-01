@props(['user', 'size' => 'md', 'online' => false])
@php
    $sizes = ['xs' => 'h-9 w-9 text-xs', 'sm' => 'h-10 w-10 text-sm', 'md' => 'h-11 w-11 text-sm', 'lg' => 'h-16 w-16 text-lg', 'xl' => 'h-28 w-28 text-3xl'];
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['relative inline-grid shrink-0 place-items-center rounded-full bg-gold-grad font-bold text-ink-950 ring-2 ring-gold-500/70 ring-offset-2 ring-offset-ink-950', $sizes[$size] ?? $sizes['md']]) }}>
    @if(!empty($user->avatar))
        <img src="{{ asset('storage/' . $user->avatar) }}" alt="" class="h-full w-full rounded-full object-cover">
    @else
        {{ $initials }}
    @endif
    @if($online)
        <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-ink-900 bg-emerald-400" title="Online"></span>
    @endif
</span>

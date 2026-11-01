{{-- Laravel Echo + Reverb, loaded from a CDN so no build step is needed. --}}
@php $reverb = config('reverb.apps.apps.0'); @endphp
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    try {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: @json($reverb['key']),
            wsHost: @json($reverb['options']['host']),
            wsPort: @json((int) $reverb['options']['port']),
            wssPort: @json((int) $reverb['options']['port']),
            forceTLS: @json(($reverb['options']['scheme'] ?? 'http') === 'https'),
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: { headers: { 'X-CSRF-TOKEN': csrf } },
        });
    } catch (e) {
        console.error('Real time is not available, falling back to polling.', e);
        window.Echo = null;
    }
</script>

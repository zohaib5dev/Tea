import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let echo = null;

/** One shared Reverb connection. Returns null when Reverb is not configured (the app then falls back to polling). */
export function getEcho() {
    if (echo) return echo;

    const env = import.meta.env;
    if (!env.VITE_REVERB_APP_KEY) return null;

    try {
        echo = new Echo({
            broadcaster: 'reverb',
            key: env.VITE_REVERB_APP_KEY,
            wsHost: env.VITE_REVERB_HOST,
            wsPort: env.VITE_REVERB_PORT ?? 80,
            wssPort: env.VITE_REVERB_PORT ?? 443,
            forceTLS: (env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } },
        });
    } catch (e) {
        console.error('[Realtime] could not start', e);
        echo = null;
    }

    return echo;
}

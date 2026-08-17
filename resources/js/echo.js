import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

/**
 * Only initialize Echo/Reverb when a broadcaster key is actually
 * configured. Reverb needs a persistent WebSocket process that most
 * shared-hosting environments (and this app's own local dev/test setup,
 * unless `reverb:start` is running) don't provide — without this guard,
 * the browser would repeatedly attempt and fail a WebSocket connection on
 * every page load, spamming the console and wasting resources. Pages that
 * use `window.subscribeToMatch()`/`subscribeToTournament()` already treat
 * a missing `window.Echo` as "no live updates available" and degrade
 * gracefully (they just show the last-loaded state).
 */
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

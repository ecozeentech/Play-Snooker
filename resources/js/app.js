import './bootstrap';
import './push';

// Game-engine Alpine components (window.practiceGame / window.matchGame) must
// be registered *before* Alpine.start() runs below, otherwise Alpine tries to
// evaluate `x-data="practiceGame()"` before that function exists and the
// component silently fails to initialize (the canvas never renders). These
// used to be separate Vite entry points loaded via a second <script> tag
// further down the page — which loads/executes *after* this file since it's
// declared first in <head> — so bundling them here guarantees the right order
// on every page, regardless of script placement.
import './game/practice';
import './game/multiplayer';

import Alpine from 'alpinejs';
import AOS from 'aos';
import 'aos/dist/aos.css';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    AOS.init({
        duration: 700,
        once: true,
        offset: 40,
    });
});

/**
 * Subscribes to a match's live channel (odds + frame updates) when Echo/
 * Reverb is configured, otherwise silently falls back to polling handled
 * by the calling page. See routes/channels.php and App\Events\Match*.
 */
window.subscribeToMatch = function subscribeToMatch(matchId, { onOdds, onFrame } = {}) {
    if (!window.Echo) {
        return null;
    }

    const channel = window.Echo.channel(`match.${matchId}`);

    if (onOdds) {
        channel.listen('.odds.updated', onOdds);
    }

    if (onFrame) {
        channel.listen('.frame.updated', onFrame);
    }

    return channel;
};

window.subscribeToTournament = function subscribeToTournament(tournamentId, { onBracket } = {}) {
    if (!window.Echo) {
        return null;
    }

    const channel = window.Echo.channel(`tournament.${tournamentId}`);

    if (onBracket) {
        channel.listen('.bracket.updated', onBracket);
    }

    return channel;
};

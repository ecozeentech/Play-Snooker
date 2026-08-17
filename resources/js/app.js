import './bootstrap';
import './push';

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

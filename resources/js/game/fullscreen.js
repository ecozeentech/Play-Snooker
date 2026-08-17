/**
 * Cross-browser fullscreen helper for the game canvas wrapper, with a
 * best-effort landscape orientation lock on mobile devices. Orientation
 * locking is only supported by some browsers (notably not iOS Safari), so
 * it's wrapped in a try/catch and silently ignored where unavailable —
 * the fullscreen view itself still works everywhere the Fullscreen API is
 * supported, and CSS elsewhere provides a "rotate your device" hint as a
 * fallback for browsers that can't lock orientation programmatically.
 */
export function isFullscreen() {
    return Boolean(
        document.fullscreenElement
        || document.webkitFullscreenElement
        || document.msFullscreenElement,
    );
}

export async function toggleFullscreen(element) {
    if (isFullscreen()) {
        const exit = document.exitFullscreen
            || document.webkitExitFullscreen
            || document.msExitFullscreen;

        await exit?.call(document);

        try {
            await screen.orientation?.unlock?.();
        } catch (error) {
            // Not supported on this browser — safe to ignore.
        }

        return false;
    }

    const request = element.requestFullscreen
        || element.webkitRequestFullscreen
        || element.msRequestFullscreen;

    if (request) {
        await request.call(element);

        try {
            await screen.orientation?.lock?.('landscape');
        } catch (error) {
            // Not supported (e.g. iOS Safari) — the player can still rotate manually.
        }
    }

    return true;
}

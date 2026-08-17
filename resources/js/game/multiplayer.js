import { PoolEngine } from './engine';
import { toggleFullscreen, isFullscreen } from './fullscreen';

/**
 * Wires the PoolEngine up to a live 1v1 match page. Turn state and frame
 * results are synced across both players via Laravel Reverb broadcasts
 * (see routes/channels.php + App\Events\Match*), so spectators and the
 * live betting panel always reflect the current match flow.
 *
 * Scope note: full real-time ball-position netcode (streaming every
 * physics tick between two browsers) is out of scope for this pass — each
 * frame is played out locally by whichever player currently holds the
 * shot, and the *frame result* (winner + points) is what gets synced.
 * This still satisfies turn-based multiplayer with WebSocket-driven live
 * odds/score updates, which is what the betting platform depends on.
 */
window.matchGame = function matchGame({ matchId, player1Id, player2Id, currentUserId, initialFrame, cues = [] }) {
    return {
        matchId,
        player1Id,
        player2Id,
        currentUserId,
        currentFrame: initialFrame,
        engine: null,
        isMyTurn: false,
        shooterId: null,
        message: 'Waiting for match state…',
        oddsData: null,
        cues,
        selectedCueId: (cues.find((c) => c.equipped) ?? cues[0])?.id ?? 0,
        isFullscreen: false,

        init() {
            this.shooterId = this.currentFrame % 2 === 1 ? this.player1Id : this.player2Id;
            this.isMyTurn = this.currentUserId === this.shooterId;
            this.message = this.isMyTurn ? 'Your frame — take your shot!' : "Waiting for your opponent's frame…";

            if (this.isMyTurn) {
                const canvas = this.$refs.canvas;
                this.engine = new PoolEngine(canvas, {
                    onBallsStopped: (pottedIds) => this.handleBallsStopped(pottedIds),
                    onFoul: (reason) => { this.message = `Foul: ${reason}`; },
                });
                this.engine.rackBalls();
                this.applySelectedCue();
            }

            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = isFullscreen();
                setTimeout(() => this.engine?.resize(), 100);
            });

            window.subscribeToMatch(this.matchId, {
                onOdds: (event) => { this.oddsData = event.odds; },
                onFrame: (event) => {
                    this.currentFrame = event.current_frame;
                    if (event.status === 'finished') {
                        this.message = 'Match finished!';
                    }
                },
            });
        },

        applySelectedCue() {
            const cue = this.cues.find((c) => c.id === this.selectedCueId);
            if (cue && this.engine) {
                this.engine.setCueAppearance(cue.appearance);
            }
        },

        async toggleTableFullscreen() {
            this.isFullscreen = await toggleFullscreen(this.$refs.tableWrapper);
        },

        handleBallsStopped(pottedIds) {
            const cuePotted = pottedIds.includes(0);
            const objectBallsLeft = this.engine.remainingObjectBalls().length;

            if (cuePotted) {
                this.engine.respotCueBall();
            }

            if (objectBallsLeft === 0) {
                this.reportFrameResult(this.shooterId, pottedIds.length);
            }
        },

        concedeFrame() {
            const opponentId = this.shooterId === this.player1Id ? this.player2Id : this.player1Id;
            this.reportFrameResult(opponentId, 0);
        },

        async reportFrameResult(winnerId, points) {
            this.message = 'Submitting frame result…';

            const response = await fetch(`/play/matches/${this.matchId}/frames`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    winner_id: winnerId,
                    player1_points: winnerId === this.player1Id ? points : 0,
                    player2_points: winnerId === this.player2Id ? points : 0,
                }),
            });

            const data = await response.json();
            this.oddsData = data.odds_data;
            this.message = data.status === 'finished' ? 'Match finished!' : 'Frame submitted. Waiting for the next frame to start…';
        },
    };
};

import { PoolEngine } from './engine';
import { planAiShot } from './ai';
import { toggleFullscreen, isFullscreen } from './fullscreen';

/**
 * Wires the PoolEngine up to the practice-mode page: solo play against an
 * adjustable-difficulty AI opponent. Exposed as an Alpine.js component
 * factory (`window.practiceGame`) so the Blade view can bind it directly
 * with `x-data="practiceGame(cues)"`.
 */
window.practiceGame = function practiceGame(cues = []) {
    return {
        difficulty: 'medium',
        turn: 'player',
        message: 'Your shot — drag the cue ball backward, then release to strike.',
        playerScore: 0,
        aiScore: 0,
        engine: null,
        frameHistory: [],
        cues,
        selectedCueId: (cues.find((c) => c.equipped) ?? cues[0])?.id ?? 0,
        isFullscreen: false,

        init() {
            const canvas = this.$refs.canvas;
            this.engine = new PoolEngine(canvas, {
                onBallsStopped: (pottedIds) => this.handleBallsStopped(pottedIds),
                onPot: () => {},
                onFoul: (reason) => {
                    this.message = `Foul: ${reason}`;
                },
            });
            this.engine.rackBalls();
            this.applySelectedCue();

            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = isFullscreen();
                setTimeout(() => this.engine?.resize(), 100);
            });
        },

        applySelectedCue() {
            const cue = this.cues.find((c) => c.id === this.selectedCueId);
            if (cue) {
                this.engine.setCueAppearance(cue.appearance);
            }
        },

        async toggleTableFullscreen() {
            this.isFullscreen = await toggleFullscreen(this.$refs.tableWrapper);
        },

        handleBallsStopped(pottedIds) {
            this.frameHistory.push({ turn: this.turn, potted: pottedIds, at: Date.now() });

            const objectBallPotted = pottedIds.some((id) => id !== 0);
            const cuePotted = pottedIds.includes(0);

            if (this.turn === 'player' && objectBallPotted && !cuePotted) {
                this.playerScore++;
                this.message = 'Nice shot! Go again.';
            } else if (this.turn === 'ai' && objectBallPotted && !cuePotted) {
                this.aiScore++;
            }

            if (cuePotted) {
                this.engine.respotCueBall();
            }

            if (this.engine.remainingObjectBalls().length === 0) {
                this.message = this.playerScore >= this.aiScore
                    ? '🏆 You cleared the table! Practice frame won.'
                    : 'The AI cleared the table. Rack them up again!';
                return;
            }

            if (this.turn === 'player' && !(objectBallPotted && !cuePotted)) {
                this.switchTurn();
            } else if (this.turn === 'ai') {
                this.switchTurn();
            }
        },

        switchTurn() {
            this.turn = this.turn === 'player' ? 'ai' : 'player';

            if (this.turn === 'ai') {
                this.message = 'AI is aiming...';
                setTimeout(() => this.playAiTurn(), 900);
            } else {
                this.message = 'Your shot!';
            }
        },

        playAiTurn() {
            const shot = planAiShot(this.engine, this.difficulty);

            if (!shot) {
                this.switchTurn();
                return;
            }

            this.engine.shoot(shot.angle, shot.power);
        },

        newRack() {
            this.engine.rackBalls();
            this.applySelectedCue();
            this.playerScore = 0;
            this.aiScore = 0;
            this.turn = 'player';
            this.message = 'Fresh rack! Your shot.';
            this.frameHistory = [];
        },

        async saveReplay(matchId) {
            if (!matchId || this.frameHistory.length === 0) return;

            await fetch(`/play/matches/${matchId}/replays`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ frames: this.frameHistory, duration_seconds: Math.round(this.frameHistory.length * 4) }),
            });
        },
    };
};

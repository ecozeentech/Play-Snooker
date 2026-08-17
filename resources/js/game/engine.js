/**
 * Play Snooker digital game engine.
 *
 * A self-contained 2D physics simulation for pool/snooker-style gameplay
 * rendered on an HTML5 Canvas. Handles realistic ball-to-ball collisions,
 * cushion rebounds, rolling friction, simple cue-ball "spin" (side/back
 * spin approximated as post-impact curl), pocketing detection, and
 * touch/mouse "drag to aim, pull back for power" controls.
 *
 * This engine intentionally has zero external dependencies so it can be
 * embedded directly in Blade views via Vite without pulling in a full 3D
 * engine (PixiJS/Three.js) — the visual "HD" feel comes from gradient
 * shading, soft shadows and a reflective baize table drawn in Canvas 2D.
 */

const TABLE_PADDING = 36;
const BALL_RADIUS = 11;
const POCKET_RADIUS = 22;
const FRICTION = 0.988; // Per-frame rolling friction (velocity decay).
const MIN_VELOCITY = 0.04; // Balls below this speed are considered stopped.
const MAX_POWER_DISTANCE = 160; // Pixels of pull-back that yields max power.
const MAX_SHOT_SPEED = 15;
const RESTITUTION = 0.94; // Cushion bounce energy retention.

export const BALL_COLORS = [
    '#f4f4f4', // 0: cue ball
    '#e3b02b', // 1: gold
    '#1f7a48', // 2: baize green
    '#c94b3c', // 3: red
    '#3c6fc9', // 4: blue
    '#8c3ca0', // 5: purple
    '#d97b1f', // 6: orange
    '#151515', // 7: black (8-ball equivalent)
];

export class PoolEngine {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {{onBallsStopped?: Function, onPot?: Function, onFoul?: Function}} callbacks
     */
    constructor(canvas, callbacks = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.callbacks = callbacks;
        this.balls = [];
        this.pockets = [];
        this.aiming = false;
        this.aimStart = null;
        this.aimCurrent = null;
        this.animationFrame = null;
        this.shotInFlight = false;
        this.lastPottedIds = [];

        this.resize();
        this.setupPockets();
        this.bindInput();
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        this.width = rect.width;
        this.height = rect.height;
    }

    setupPockets() {
        const { width, height } = this;
        const p = TABLE_PADDING;
        this.pockets = [
            { x: p, y: p },
            { x: width / 2, y: p - 6 },
            { x: width - p, y: p },
            { x: p, y: height - p },
            { x: width / 2, y: height - p + 6 },
            { x: width - p, y: height - p },
        ];
    }

    /**
     * Rack a reduced 7-ball set (cue ball + 6 numbered object balls) in a
     * triangle formation. A reduced rack keeps practice frames quick while
     * exercising the full physics feature set (collisions, spin, cushions).
     */
    rackBalls() {
        const { width, height } = this;
        const balls = [];

        balls.push({
            id: 0,
            x: width * 0.25,
            y: height / 2,
            vx: 0,
            vy: 0,
            spin: 0,
            radius: BALL_RADIUS,
            color: BALL_COLORS[0],
            potted: false,
            isCue: true,
        });

        const rackX = width * 0.7;
        const rackY = height / 2;
        const rows = [1, 2, 3];
        let ballNumber = 1;

        rows.forEach((count, rowIndex) => {
            for (let i = 0; i < count; i++) {
                const x = rackX + rowIndex * (BALL_RADIUS * 1.9);
                const y = rackY + (i - (count - 1) / 2) * (BALL_RADIUS * 2.1);

                balls.push({
                    id: ballNumber,
                    x,
                    y,
                    vx: 0,
                    vy: 0,
                    spin: 0,
                    radius: BALL_RADIUS,
                    color: BALL_COLORS[ballNumber] ?? '#999',
                    potted: false,
                    isCue: false,
                });

                ballNumber++;
            }
        });

        this.balls = balls;
        this.render();
    }

    get cueBall() {
        return this.balls.find((b) => b.isCue && !b.potted);
    }

    bindInput() {
        const start = (point) => {
            if (this.shotInFlight || !this.cueBall) return;
            this.aiming = true;
            this.aimStart = point;
            this.aimCurrent = point;
        };

        const move = (point) => {
            if (!this.aiming) return;
            this.aimCurrent = point;
            this.render();
        };

        const end = () => {
            if (!this.aiming || !this.cueBall) {
                this.aiming = false;
                return;
            }

            const dx = this.aimStart.x - this.aimCurrent.x;
            const dy = this.aimStart.y - this.aimCurrent.y;
            const distance = Math.min(Math.hypot(dx, dy), MAX_POWER_DISTANCE);

            if (distance > 6) {
                const angle = Math.atan2(dy, dx);
                const power = (distance / MAX_POWER_DISTANCE) * MAX_SHOT_SPEED;
                this.shoot(angle, power);
            }

            this.aiming = false;
            this.aimStart = null;
            this.aimCurrent = null;
        };

        const toPoint = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const touch = e.touches?.[0] ?? e.changedTouches?.[0];
            const clientX = touch ? touch.clientX : e.clientX;
            const clientY = touch ? touch.clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        };

        this.canvas.addEventListener('mousedown', (e) => start(toPoint(e)));
        this.canvas.addEventListener('mousemove', (e) => move(toPoint(e)));
        window.addEventListener('mouseup', () => end());

        this.canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(toPoint(e)); }, { passive: false });
        this.canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(toPoint(e)); }, { passive: false });
        this.canvas.addEventListener('touchend', (e) => { e.preventDefault(); end(); if (navigator.vibrate) navigator.vibrate(15); }, { passive: false });
    }

    /**
     * Fire the cue ball. `angle` is the forward shot direction (radians).
     * `spin` (-1..1) approximates side-spin ("english") which we render as
     * a gentle curl applied to the cue ball's path during the first few
     * frames after contact.
     */
    shoot(angle, power, spin = 0) {
        const cue = this.cueBall;
        if (!cue) return;

        cue.vx = Math.cos(angle) * power;
        cue.vy = Math.sin(angle) * power;
        cue.spin = spin;

        this.shotInFlight = true;
        this.lastPottedIds = [];
        this.loop();
    }

    loop() {
        if (this.animationFrame) cancelAnimationFrame(this.animationFrame);

        const step = () => {
            this.update();
            this.render();

            if (this.isMoving()) {
                this.animationFrame = requestAnimationFrame(step);
            } else {
                this.shotInFlight = false;
                this.callbacks.onBallsStopped?.(this.lastPottedIds);
            }
        };

        this.animationFrame = requestAnimationFrame(step);
    }

    isMoving() {
        return this.balls.some((b) => !b.potted && (Math.abs(b.vx) > MIN_VELOCITY || Math.abs(b.vy) > MIN_VELOCITY));
    }

    update() {
        const p = TABLE_PADDING;

        for (const ball of this.balls) {
            if (ball.potted) continue;

            // Cue-ball spin curl: nudges lateral velocity while the ball is fast,
            // decaying quickly — a lightweight approximation of side-spin.
            if (ball.isCue && Math.abs(ball.spin) > 0.001) {
                const speed = Math.hypot(ball.vx, ball.vy);
                if (speed > 0.5) {
                    const perpX = -ball.vy / speed;
                    const perpY = ball.vx / speed;
                    ball.vx += perpX * ball.spin * 0.05;
                    ball.vy += perpY * ball.spin * 0.05;
                    ball.spin *= 0.96;
                }
            }

            ball.x += ball.vx;
            ball.y += ball.vy;
            ball.vx *= FRICTION;
            ball.vy *= FRICTION;

            if (Math.abs(ball.vx) < MIN_VELOCITY) ball.vx = 0;
            if (Math.abs(ball.vy) < MIN_VELOCITY) ball.vy = 0;

            // Cushion rebounds.
            if (ball.x - ball.radius < p) {
                ball.x = p + ball.radius;
                ball.vx = -ball.vx * RESTITUTION;
            } else if (ball.x + ball.radius > this.width - p) {
                ball.x = this.width - p - ball.radius;
                ball.vx = -ball.vx * RESTITUTION;
            }

            if (ball.y - ball.radius < p) {
                ball.y = p + ball.radius;
                ball.vy = -ball.vy * RESTITUTION;
            } else if (ball.y + ball.radius > this.height - p) {
                ball.y = this.height - p - ball.radius;
                ball.vy = -ball.vy * RESTITUTION;
            }
        }

        this.resolveCollisions();
        this.checkPockets();
    }

    resolveCollisions() {
        const active = this.balls.filter((b) => !b.potted);

        for (let i = 0; i < active.length; i++) {
            for (let j = i + 1; j < active.length; j++) {
                const a = active[i];
                const b = active[j];
                const dx = b.x - a.x;
                const dy = b.y - a.y;
                const distance = Math.hypot(dx, dy);
                const minDistance = a.radius + b.radius;

                if (distance < minDistance && distance > 0) {
                    // Separate overlapping balls.
                    const overlap = (minDistance - distance) / 2;
                    const nx = dx / distance;
                    const ny = dy / distance;
                    a.x -= nx * overlap;
                    a.y -= ny * overlap;
                    b.x += nx * overlap;
                    b.y += ny * overlap;

                    // Elastic collision (equal mass balls swap normal velocity components).
                    const relVx = a.vx - b.vx;
                    const relVy = a.vy - b.vy;
                    const speed = relVx * nx + relVy * ny;

                    if (speed > 0) {
                        a.vx -= speed * nx;
                        a.vy -= speed * ny;
                        b.vx += speed * nx;
                        b.vy += speed * ny;
                    }
                }
            }
        }
    }

    checkPockets() {
        for (const ball of this.balls) {
            if (ball.potted) continue;

            for (const pocket of this.pockets) {
                const distance = Math.hypot(ball.x - pocket.x, ball.y - pocket.y);

                if (distance < POCKET_RADIUS) {
                    ball.potted = true;
                    ball.vx = 0;
                    ball.vy = 0;
                    this.lastPottedIds.push(ball.id);
                    this.callbacks.onPot?.(ball);

                    if (ball.isCue) {
                        this.callbacks.onFoul?.('Cue ball potted');
                    }

                    break;
                }
            }
        }
    }

    respotCueBall() {
        const cue = this.cueBall;
        if (cue) return;

        this.balls.push({
            id: 0,
            x: this.width * 0.25,
            y: this.height / 2,
            vx: 0,
            vy: 0,
            spin: 0,
            radius: BALL_RADIUS,
            color: BALL_COLORS[0],
            potted: false,
            isCue: true,
        });
    }

    remainingObjectBalls() {
        return this.balls.filter((b) => !b.isCue && !b.potted);
    }

    render() {
        const ctx = this.ctx;
        const { width, height } = this;

        ctx.clearRect(0, 0, width, height);

        // Table felt with a subtle radial highlight for a "reflective baize" feel.
        const felt = ctx.createRadialGradient(width / 2, height / 2, 10, width / 2, height / 2, Math.max(width, height));
        felt.addColorStop(0, '#1f7a48');
        felt.addColorStop(1, '#0d3924');
        ctx.fillStyle = felt;
        ctx.fillRect(0, 0, width, height);

        // Wooden rail.
        ctx.strokeStyle = '#4a301d';
        ctx.lineWidth = TABLE_PADDING;
        ctx.strokeRect(TABLE_PADDING / 2, TABLE_PADDING / 2, width - TABLE_PADDING, height - TABLE_PADDING);

        // Cushions.
        ctx.strokeStyle = 'rgba(255,255,255,0.08)';
        ctx.lineWidth = 3;
        ctx.strokeRect(TABLE_PADDING, TABLE_PADDING, width - TABLE_PADDING * 2, height - TABLE_PADDING * 2);

        // Pockets.
        for (const pocket of this.pockets) {
            ctx.beginPath();
            ctx.arc(pocket.x, pocket.y, POCKET_RADIUS, 0, Math.PI * 2);
            ctx.fillStyle = '#050505';
            ctx.fill();
        }

        // Aim guide.
        if (this.aiming && this.cueBall && this.aimStart && this.aimCurrent) {
            const cue = this.cueBall;
            const dx = this.aimStart.x - this.aimCurrent.x;
            const dy = this.aimStart.y - this.aimCurrent.y;
            const distance = Math.min(Math.hypot(dx, dy), MAX_POWER_DISTANCE);
            const angle = Math.atan2(dy, dx);

            ctx.save();
            ctx.strokeStyle = `rgba(227, 176, 43, ${0.4 + (distance / MAX_POWER_DISTANCE) * 0.5})`;
            ctx.lineWidth = 2;
            ctx.setLineDash([6, 6]);
            ctx.beginPath();
            ctx.moveTo(cue.x, cue.y);
            ctx.lineTo(cue.x + Math.cos(angle) * 260, cue.y + Math.sin(angle) * 260);
            ctx.stroke();

            // Cue stick pulled back behind the ball.
            ctx.setLineDash([]);
            ctx.strokeStyle = '#c1935c';
            ctx.lineWidth = 5;
            ctx.beginPath();
            ctx.moveTo(cue.x - Math.cos(angle) * (20 + distance), cue.y - Math.sin(angle) * (20 + distance));
            ctx.lineTo(cue.x - Math.cos(angle) * (20 + distance * 0.35), cue.y - Math.sin(angle) * (20 + distance * 0.35));
            ctx.stroke();
            ctx.restore();
        }

        // Balls with a simple lighting gradient for a glossy look.
        for (const ball of this.balls) {
            if (ball.potted) continue;

            ctx.save();
            ctx.beginPath();
            ctx.arc(ball.x, ball.y + 2, ball.radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(0,0,0,0.25)';
            ctx.fill();

            const gradient = ctx.createRadialGradient(
                ball.x - ball.radius * 0.4,
                ball.y - ball.radius * 0.4,
                1,
                ball.x,
                ball.y,
                ball.radius,
            );
            gradient.addColorStop(0, '#ffffff');
            gradient.addColorStop(0.25, ball.color);
            gradient.addColorStop(1, ball.color);

            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
            ctx.fillStyle = gradient;
            ctx.fill();
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(0,0,0,0.3)';
            ctx.stroke();

            if (!ball.isCue) {
                ctx.fillStyle = '#fff';
                ctx.font = `${ball.radius}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(String(ball.id), ball.x, ball.y);
            }

            ctx.restore();
        }
    }

    destroy() {
        if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    }
}

/**
 * Play Snooker digital game engine.
 *
 * A self-contained 2D physics simulation for pool/snooker-style gameplay
 * rendered on an HTML5 Canvas. Handles realistic ball-to-ball collisions,
 * cushion rebounds, rolling friction, simple cue-ball "spin" (side/back
 * spin approximated as post-impact curl), pocketing detection, a modern
 * aiming HUD (ghost-ball preview, power meter, angle readout, spin/
 * "english" picker), customizable cue-stick appearance, and touch/mouse
 * "drag to aim, pull back for power" controls.
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
const SPIN_WIDGET_RADIUS = 26; // On-canvas "english" picker widget.
const SPIN_WIDGET_MARGIN = 18;

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

export const DEFAULT_CUE_APPEARANCE = {
    shaft_color: '#c1935c',
    wrap_color: '#4a301d',
    tip_color: '#2b6cb0',
    butt_color: '#1f140c',
};

export class PoolEngine {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {{onBallsStopped?: Function, onPot?: Function, onFoul?: Function, onAimChange?: Function}} callbacks
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
        this.cueAppearance = { ...DEFAULT_CUE_APPEARANCE };
        this.spin = { x: 0, y: 0 }; // -1..1 on each axis (side spin, top/back spin).
        this.adjustingSpin = false;

        this.resize();
        this.setupPockets();
        this.bindInput();
        this.observeResize();
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        // If the canvas is measured before its container has finished
        // laying out (e.g. right after an Alpine x-show/x-if toggle, or on
        // very fast page loads before the aspect-ratio CSS has applied),
        // getBoundingClientRect() can briefly report 0x0. Fall back to a
        // sane default so the table still renders, then self-correct once
        // real layout is available.
        const width = rect.width > 0 ? rect.width : (this.canvas.parentElement?.clientWidth || 800);
        const height = rect.height > 0 ? rect.height : Math.round(width * (9 / 16));

        this.canvas.width = width * dpr;
        this.canvas.height = height * dpr;
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        this.width = width;
        this.height = height;

        if (rect.width === 0) {
            requestAnimationFrame(() => {
                this.resize();
                this.setupPockets();
                this.render();
            });
        }
    }

    /**
     * Keeps the table crisp across window resizes, device rotation, and
     * fullscreen toggles — anything that changes the canvas's laid-out
     * size, not just the browser window (a plain `resize` listener misses
     * container-driven changes like fullscreen or orientation flips).
     */
    observeResize() {
        if (typeof ResizeObserver === 'undefined') return;

        let lastWidth = this.width;
        let lastHeight = this.height;

        this.resizeObserver = new ResizeObserver(() => {
            const rect = this.canvas.getBoundingClientRect();
            if (Math.abs(rect.width - lastWidth) < 1 && Math.abs(rect.height - lastHeight) < 1) return;

            lastWidth = rect.width;
            lastHeight = rect.height;
            this.resize();
            this.setupPockets();
            this.render();
        });

        this.resizeObserver.observe(this.canvas);
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

    setCueAppearance(appearance) {
        this.cueAppearance = { ...DEFAULT_CUE_APPEARANCE, ...appearance };
        this.render();
    }

    setSpin(x, y) {
        this.spin = { x: Math.max(-1, Math.min(1, x)), y: Math.max(-1, Math.min(1, y)) };
        this.callbacks.onAimChange?.({ spin: this.spin });
        this.render();
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

    spinWidgetCenter() {
        return {
            x: this.width - SPIN_WIDGET_MARGIN - SPIN_WIDGET_RADIUS,
            y: SPIN_WIDGET_MARGIN + SPIN_WIDGET_RADIUS,
        };
    }

    isWithinSpinWidget(point) {
        const center = this.spinWidgetCenter();
        return Math.hypot(point.x - center.x, point.y - center.y) <= SPIN_WIDGET_RADIUS + 6;
    }

    bindInput() {
        const start = (point) => {
            if (this.isWithinSpinWidget(point)) {
                this.adjustingSpin = true;
                this.updateSpinFromPoint(point);
                return;
            }

            if (this.shotInFlight || !this.cueBall) return;
            this.aiming = true;
            this.aimStart = point;
            this.aimCurrent = point;
            this.callbacks.onAimChange?.({ aiming: true });
        };

        const move = (point) => {
            if (this.adjustingSpin) {
                this.updateSpinFromPoint(point);
                return;
            }

            if (!this.aiming) return;
            this.aimCurrent = point;
            this.render();
        };

        const end = () => {
            if (this.adjustingSpin) {
                this.adjustingSpin = false;
                return;
            }

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
                this.shoot(angle, power, this.spin.x);
            }

            this.aiming = false;
            this.aimStart = null;
            this.aimCurrent = null;
            this.callbacks.onAimChange?.({ aiming: false });
            this.render();
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

    updateSpinFromPoint(point) {
        const center = this.spinWidgetCenter();
        const dx = (point.x - center.x) / SPIN_WIDGET_RADIUS;
        const dy = (point.y - center.y) / SPIN_WIDGET_RADIUS;
        const magnitude = Math.hypot(dx, dy);
        const clampScale = magnitude > 1 ? 1 / magnitude : 1;

        this.setSpin(dx * clampScale, dy * clampScale);
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

    /**
     * Casts a ray from the cue ball along the aim direction and returns the
     * first object ball it would strike (if any) and the "ghost ball"
     * position — where the cue ball's center would be at the moment of
     * contact — powering the modern ghost-ball aiming aid.
     */
    findAimTarget(angle) {
        const cue = this.cueBall;
        if (!cue) return null;

        const dirX = Math.cos(angle);
        const dirY = Math.sin(angle);
        let closest = null;

        for (const ball of this.balls) {
            if (ball.isCue || ball.potted) continue;

            // Project the ball's center onto the aim ray to find the closest
            // approach distance, then solve for where a ball of combined
            // radius would first touch it along that ray.
            const toBallX = ball.x - cue.x;
            const toBallY = ball.y - cue.y;
            const projection = toBallX * dirX + toBallY * dirY;
            if (projection <= 0) continue;

            const closestX = cue.x + dirX * projection;
            const closestY = cue.y + dirY * projection;
            const perpDistance = Math.hypot(ball.x - closestX, ball.y - closestY);
            const combinedRadius = ball.radius + cue.radius;
            if (perpDistance >= combinedRadius) continue;

            const backOffset = Math.sqrt(Math.max(combinedRadius ** 2 - perpDistance ** 2, 0));
            const contactDistance = projection - backOffset;
            if (contactDistance < 0) continue;

            if (!closest || contactDistance < closest.distance) {
                closest = {
                    ball,
                    distance: contactDistance,
                    ghostX: cue.x + dirX * contactDistance,
                    ghostY: cue.y + dirY * contactDistance,
                };
            }
        }

        return closest;
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

        // Faint diamond markers along the rail for a pro-table look.
        ctx.fillStyle = 'rgba(255,255,255,0.25)';
        const diamondCount = 6;
        for (let i = 1; i < diamondCount; i++) {
            const x = TABLE_PADDING + (i * (width - TABLE_PADDING * 2)) / diamondCount;
            ctx.beginPath();
            ctx.arc(x, TABLE_PADDING - 10, 2, 0, Math.PI * 2);
            ctx.arc(x, height - TABLE_PADDING + 10, 2, 0, Math.PI * 2);
            ctx.fill();
        }

        // Wooden rail with a subtle gradient for depth.
        const rail = ctx.createLinearGradient(0, 0, 0, height);
        rail.addColorStop(0, '#5c4230');
        rail.addColorStop(1, '#3a281b');
        ctx.strokeStyle = rail;
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
            const pocketGradient = ctx.createRadialGradient(pocket.x, pocket.y, 2, pocket.x, pocket.y, POCKET_RADIUS);
            pocketGradient.addColorStop(0, '#000000');
            pocketGradient.addColorStop(1, '#1a1a1a');
            ctx.fillStyle = pocketGradient;
            ctx.fill();
        }

        this.renderAimHud();

        // Balls with a simple lighting gradient for a glossy look.
        for (const ball of this.balls) {
            if (ball.potted) continue;
            this.renderBall(ball);
        }

        this.renderSpinWidget();
    }

    renderBall(ball) {
        const ctx = this.ctx;

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
        } else if (Math.abs(this.spin.x) > 0.05 || Math.abs(this.spin.y) > 0.05) {
            // Show the current "english" as a small dot offset on the cue ball itself.
            ctx.beginPath();
            ctx.arc(ball.x + this.spin.x * ball.radius * 0.5, ball.y + this.spin.y * ball.radius * 0.5, 2, 0, Math.PI * 2);
            ctx.fillStyle = '#c94b3c';
            ctx.fill();
        }

        ctx.restore();
    }

    /**
     * Draws the modern aiming HUD: an extended dashed trajectory line, a
     * translucent "ghost ball" at the predicted point of contact with a
     * secondary line showing where the object ball would travel, a power
     * meter, and a degree readout — plus the pulled-back cue stick itself,
     * rendered using the equipped cue's custom colors.
     */
    renderAimHud() {
        if (!this.aiming || !this.cueBall || !this.aimStart || !this.aimCurrent) return;

        const ctx = this.ctx;
        const cue = this.cueBall;
        const dx = this.aimStart.x - this.aimCurrent.x;
        const dy = this.aimStart.y - this.aimCurrent.y;
        const distance = Math.min(Math.hypot(dx, dy), MAX_POWER_DISTANCE);
        const angle = Math.atan2(dy, dx);
        const powerPct = distance / MAX_POWER_DISTANCE;

        const target = this.findAimTarget(angle);
        const guideLength = target ? target.distance : 500;

        ctx.save();

        // Dashed aim guide up to the ghost-ball contact point (or off into
        // the distance if nothing is in the way).
        ctx.strokeStyle = `rgba(227, 176, 43, ${0.45 + powerPct * 0.5})`;
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 6]);
        ctx.beginPath();
        ctx.moveTo(cue.x, cue.y);
        ctx.lineTo(cue.x + Math.cos(angle) * guideLength, cue.y + Math.sin(angle) * guideLength);
        ctx.stroke();

        if (target) {
            // Ghost ball: translucent outline showing where the cue ball's
            // center will be at the moment of impact.
            ctx.setLineDash([]);
            ctx.beginPath();
            ctx.arc(target.ghostX, target.ghostY, cue.radius, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255,255,255,0.85)';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            ctx.fillStyle = 'rgba(255,255,255,0.12)';
            ctx.fill();

            // Predicted travel direction of the object ball (line from its
            // center through the impact point, extended outward).
            const throughX = target.ball.x - target.ghostX;
            const throughY = target.ball.y - target.ghostY;
            const throughLen = Math.hypot(throughX, throughY) || 1;
            ctx.strokeStyle = 'rgba(255,255,255,0.5)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([3, 5]);
            ctx.beginPath();
            ctx.moveTo(target.ball.x, target.ball.y);
            ctx.lineTo(target.ball.x + (throughX / throughLen) * 120, target.ball.y + (throughY / throughLen) * 120);
            ctx.stroke();
        }

        // Cue stick pulled back behind the ball, rendered with the
        // equipped cue's own colors for a personalized look.
        ctx.setLineDash([]);
        const pullback = 20 + distance;
        const tipX = cue.x - Math.cos(angle) * pullback;
        const tipY = cue.y - Math.sin(angle) * pullback;
        const buttX = cue.x - Math.cos(angle) * (pullback + 130);
        const buttY = cue.y - Math.sin(angle) * (pullback + 130);
        const wrapX = cue.x - Math.cos(angle) * (pullback + 90);
        const wrapY = cue.y - Math.sin(angle) * (pullback + 90);

        // Shaft (tip -> wrap).
        ctx.strokeStyle = this.cueAppearance.shaft_color;
        ctx.lineWidth = 6;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(tipX, tipY);
        ctx.lineTo(wrapX, wrapY);
        ctx.stroke();

        // Tip (small colored cap at the striking end).
        ctx.strokeStyle = this.cueAppearance.tip_color;
        ctx.lineWidth = 6;
        ctx.beginPath();
        ctx.moveTo(tipX, tipY);
        ctx.lineTo(cue.x - Math.cos(angle) * (pullback - 6), cue.y - Math.sin(angle) * (pullback - 6));
        ctx.stroke();

        // Wrap/grip (wrap -> butt).
        ctx.strokeStyle = this.cueAppearance.wrap_color;
        ctx.lineWidth = 7;
        ctx.beginPath();
        ctx.moveTo(wrapX, wrapY);
        ctx.lineTo(buttX, buttY);
        ctx.stroke();

        // Butt cap.
        ctx.fillStyle = this.cueAppearance.butt_color;
        ctx.beginPath();
        ctx.arc(buttX, buttY, 4.5, 0, Math.PI * 2);
        ctx.fill();

        // Power meter: small radial arc around the cue ball.
        ctx.beginPath();
        ctx.arc(cue.x, cue.y, cue.radius + 8, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * powerPct);
        ctx.strokeStyle = powerPct > 0.75 ? '#e05252' : '#e3b02b';
        ctx.lineWidth = 3;
        ctx.stroke();

        // Angle + power readout near the cue ball.
        const degrees = Math.round(((angle * 180) / Math.PI + 360) % 360);
        ctx.font = '600 11px sans-serif';
        ctx.fillStyle = 'rgba(255,255,255,0.85)';
        ctx.textAlign = 'center';
        ctx.fillText(`${degrees}° · ${Math.round(powerPct * 100)}%`, cue.x, cue.y - cue.radius - 14);

        ctx.restore();
    }

    /**
     * Small always-visible "english" (spin) picker in the corner of the
     * table — tap/drag within it to apply side-spin to the next shot.
     */
    renderSpinWidget() {
        const ctx = this.ctx;
        const center = this.spinWidgetCenter();

        ctx.save();
        ctx.beginPath();
        ctx.arc(center.x, center.y, SPIN_WIDGET_RADIUS, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(10, 30, 43, 0.55)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.35)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // Crosshair.
        ctx.strokeStyle = 'rgba(255,255,255,0.2)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(center.x - SPIN_WIDGET_RADIUS, center.y);
        ctx.lineTo(center.x + SPIN_WIDGET_RADIUS, center.y);
        ctx.moveTo(center.x, center.y - SPIN_WIDGET_RADIUS);
        ctx.lineTo(center.x, center.y + SPIN_WIDGET_RADIUS);
        ctx.stroke();

        // Cue-ball miniature + spin dot.
        ctx.beginPath();
        ctx.arc(center.x, center.y, SPIN_WIDGET_RADIUS - 6, 0, Math.PI * 2);
        ctx.fillStyle = '#f4f4f4';
        ctx.fill();

        const dotX = center.x + this.spin.x * (SPIN_WIDGET_RADIUS - 10);
        const dotY = center.y + this.spin.y * (SPIN_WIDGET_RADIUS - 10);
        ctx.beginPath();
        ctx.arc(dotX, dotY, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#c94b3c';
        ctx.fill();
        ctx.restore();
    }

    destroy() {
        if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
        this.resizeObserver?.disconnect();
    }
}

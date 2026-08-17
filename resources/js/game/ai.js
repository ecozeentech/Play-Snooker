/**
 * Lightweight AI opponent for practice mode. Aims at the nearest remaining
 * object ball and shoots towards a pocket, with the accuracy and power
 * control degrading at easier difficulties to keep practice matches
 * approachable for newer players.
 */

const DIFFICULTY_SETTINGS = {
    easy: { aimErrorDegrees: 14, powerVariance: 0.5 },
    medium: { aimErrorDegrees: 6, powerVariance: 0.25 },
    hard: { aimErrorDegrees: 1.5, powerVariance: 0.08 },
};

export function planAiShot(engine, difficulty = 'medium') {
    const settings = DIFFICULTY_SETTINGS[difficulty] ?? DIFFICULTY_SETTINGS.medium;
    const cue = engine.cueBall;
    const targets = engine.remainingObjectBalls();

    if (!cue || targets.length === 0) {
        return null;
    }

    // Pick the target ball with the clearest shot to any pocket.
    let bestShot = null;

    for (const ball of targets) {
        for (const pocket of engine.pockets) {
            const angleToPocket = Math.atan2(pocket.y - ball.y, pocket.x - ball.x);
            const ballToPocketDistance = Math.hypot(pocket.x - ball.x, pocket.y - ball.y);

            // Contact point: just behind the object ball, opposite the pocket.
            const contactX = ball.x - Math.cos(angleToPocket) * (ball.radius * 2);
            const contactY = ball.y - Math.sin(angleToPocket) * (ball.radius * 2);
            const cueDistance = Math.hypot(contactX - cue.x, contactY - cue.y);

            const score = ballToPocketDistance + cueDistance;

            if (!bestShot || score < bestShot.score) {
                bestShot = { ball, pocket, contactX, contactY, score, cueDistance };
            }
        }
    }

    if (!bestShot) return null;

    const idealAngle = Math.atan2(bestShot.contactY - cue.y, bestShot.contactX - cue.x);
    const errorRadians = (Math.random() * 2 - 1) * (settings.aimErrorDegrees * Math.PI) / 180;
    const angle = idealAngle + errorRadians;

    const basePower = Math.min(6 + bestShot.cueDistance / 40, 13);
    const powerVariance = 1 + (Math.random() * 2 - 1) * settings.powerVariance;
    const power = Math.max(3, Math.min(basePower * powerVariance, 15));

    return { angle, power };
}

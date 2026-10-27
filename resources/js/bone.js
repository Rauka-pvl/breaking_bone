const VIEW_W = 600;
const VIEW_H = 220;
const CENTER_Y = 110;
const GRAVITY = 1500;
const HIT_STOP = 0.07;

const clamp = (v, min = 0, max = 1) => Math.min(max, Math.max(min, v));
const lerp = (a, b, t) => a + (b - a) * t;
const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
const easeInOutSine = (t) => -(Math.cos(Math.PI * t) - 1) / 2;
const easeOutBack = (t, s = 1.70158) => 1 + (s + 1) * Math.pow(t - 1, 3) + s * Math.pow(t - 1, 2);
const rand = (min, max) => min + Math.random() * (max - min);
const pick = (list) => list[Math.floor(Math.random() * list.length)];

const CONFETTI_COLORS = ['#00afca', '#5fd4e6', '#f2c14e', '#ffe08a', '#ffffff'];
const SPARK_COLORS = ['#fff6d8', '#ffe08a', '#f2c14e', '#ffb347'];

export function createBone({ wrap, canvas }) {
    const rig = wrap.querySelector('.bone-rig');
    const left = wrap.querySelector('.half-left');
    const right = wrap.querySelector('.half-right');
    const shadow = wrap.querySelector('.bone-shadow');
    const rays = wrap.querySelector('.bone-rays');
    const halo = wrap.querySelector('.bone-halo');
    const edges = [...wrap.querySelectorAll('.edge')];
    const stress = [...wrap.querySelectorAll('.stress')];
    const sheens = [...wrap.querySelectorAll('.sheen')];
    const cracks = [...wrap.querySelectorAll('.crack')].map((el) => ({ el, at: Number(el.dataset.at), shown: -1 }));
    const ctx = canvas.getContext('2d');

    let progress = 0;
    let energy = 0;
    let brokenAt = null;
    let appearAt = performance.now();
    let pulseAt = null;
    let lastT = performance.now();
    let crackStage = 0;
    const particles = [];

    function resize() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(canvas.clientWidth * dpr);
        canvas.height = Math.round(canvas.clientHeight * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function boneFrame() {
        const c = canvas.getBoundingClientRect();
        const r = wrap.getBoundingClientRect();
        return {
            x: r.left - c.left + r.width / 2,
            y: r.top - c.top + (CENTER_Y / VIEW_H) * r.height,
            scale: r.width / VIEW_W,
        };
    }

    function renderCracks() {
        let stage = 0;
        for (const crack of cracks) {
            const local = brokenAt ? 1 : clamp((progress - crack.at) / 0.1);
            if (local > 0) stage += 1;
            const value = Math.round((1 - local) * 1000) / 1000;
            if (value !== crack.shown) {
                crack.shown = value;
                crack.el.setAttribute('stroke-dashoffset', String(value));
            }
        }
        return stage;
    }

    /* ---------------------------------------------------------------- */
    /* Particles                                                        */
    /* ---------------------------------------------------------------- */

    function spawnShard(f, opts = {}) {
        const angle = opts.angle ?? rand(-Math.PI, 0);
        const speed = opts.speed ?? rand(250, 900);
        const size = (opts.size ?? rand(5, 15)) * Math.max(f.scale, 0.6);
        const corners = 3 + Math.floor(Math.random() * 3);
        const points = Array.from({ length: corners }, (_, i) => {
            const a = (i / corners) * Math.PI * 2 + rand(-0.4, 0.4);
            const d = size * rand(0.55, 1);
            return [Math.cos(a) * d, Math.sin(a) * d];
        });
        particles.push({
            type: 'shard',
            x: f.x + rand(-8, 8) * f.scale,
            y: f.y + rand(-14, 14) * f.scale,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            rot: rand(0, Math.PI * 2),
            vr: rand(-12, 12),
            points,
            life: opts.life ?? rand(1.3, 2.3),
            age: 0,
            shade: rand(0.84, 1),
        });
    }

    function spawnDust(f, count, spread = 1) {
        for (let i = 0; i < count; i++) {
            const a = rand(0, Math.PI * 2);
            const s = rand(40, 260) * spread;
            particles.push({
                type: 'dust',
                x: f.x + rand(-12, 12) * f.scale,
                y: f.y + rand(-12, 12) * f.scale,
                vx: Math.cos(a) * s,
                vy: Math.sin(a) * s - 40,
                r: rand(6, 22) * f.scale * spread,
                life: rand(0.8, 1.8),
                age: 0,
            });
        }
    }

    function spawnSparks(f, count, speed = 1) {
        for (let i = 0; i < count; i++) {
            const a = rand(0, Math.PI * 2);
            const s = rand(400, 1200) * speed;
            particles.push({
                type: 'spark',
                x: f.x,
                y: f.y,
                vx: Math.cos(a) * s,
                vy: Math.sin(a) * s,
                life: rand(0.35, 0.8),
                age: 0,
                width: rand(1.5, 3.5),
                color: pick(SPARK_COLORS),
            });
        }
    }

    function spawnRing(f, { delay = 0, radius = 420, width = 12, life = 0.75, color = '255, 236, 180' } = {}) {
        particles.push({ type: 'ring', x: f.x, y: f.y, radius: radius * f.scale, width, life, age: -delay, color });
    }

    function spawnBurst(f, radius = 240) {
        particles.push({ type: 'burst', x: f.x, y: f.y, radius: radius * f.scale, life: 0.45, age: 0 });
    }

    function spawnConfetti(count) {
        const w = canvas.clientWidth;
        for (let i = 0; i < count; i++) {
            particles.push({
                type: 'confetti',
                x: rand(0, w),
                y: rand(-220, -10),
                vx: rand(-60, 60),
                vy: rand(120, 320),
                rot: rand(0, Math.PI * 2),
                vr: rand(-8, 8),
                w: rand(6, 14),
                h: rand(10, 22),
                sway: rand(0, Math.PI * 2),
                color: pick(CONFETTI_COLORS),
                life: rand(3.5, 6),
                age: 0,
            });
        }
    }

    function updateParticles(dt) {
        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.age += dt;
            if (p.age >= p.life) {
                particles.splice(i, 1);
                continue;
            }
            if (p.age < 0) continue;

            if (p.type === 'shard') {
                p.vy += GRAVITY * dt;
                p.vx *= 0.995;
                p.rot += p.vr * dt;
            } else if (p.type === 'dust') {
                p.vx *= 0.94;
                p.vy = p.vy * 0.94 - 10 * dt;
                p.r += 18 * dt;
            } else if (p.type === 'spark') {
                p.vx *= 0.92;
                p.vy = p.vy * 0.92 + 600 * dt;
            } else if (p.type === 'confetti') {
                p.sway += dt * 4;
                p.x += Math.sin(p.sway) * 40 * dt;
                p.rot += p.vr * dt;
            }
            p.x += (p.vx ?? 0) * dt;
            p.y += (p.vy ?? 0) * dt;
        }
    }

    function drawShard(p) {
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rot);
        ctx.beginPath();
        p.points.forEach(([x, y], i) => (i ? ctx.lineTo(x, y) : ctx.moveTo(x, y)));
        ctx.closePath();
        const g = Math.round(232 * p.shade);
        ctx.fillStyle = `rgb(${Math.min(255, g + 20)}, ${g + 4}, ${Math.round(g * 0.8)})`;
        ctx.strokeStyle = '#6e4f26';
        ctx.lineWidth = 1.1;
        ctx.fill();
        ctx.stroke();
        const [[x0, y0], [x1, y1]] = p.points;
        ctx.beginPath();
        ctx.moveTo(x0, y0);
        ctx.lineTo(x1, y1);
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.75)';
        ctx.lineWidth = 1.4;
        ctx.stroke();
    }

    function drawParticles() {
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        for (const p of particles) {
            if (p.age < 0) continue;
            const t = p.age / p.life;
            const fade = 1 - clamp((p.age - p.life * 0.6) / (p.life * 0.4));
            ctx.save();
            ctx.globalAlpha = fade;

            if (p.type === 'shard') {
                drawShard(p);
            } else if (p.type === 'dust') {
                ctx.globalAlpha = fade * 0.26;
                ctx.fillStyle = '#efe2c4';
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();
            } else if (p.type === 'spark') {
                ctx.globalCompositeOperation = 'lighter';
                ctx.strokeStyle = p.color;
                ctx.lineWidth = p.width * (1 - t);
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x - p.vx * 0.035, p.y - p.vy * 0.035);
                ctx.stroke();
            } else if (p.type === 'ring') {
                const e = easeOutCubic(t);
                ctx.globalAlpha = (1 - t) * 0.9;
                ctx.strokeStyle = `rgb(${p.color})`;
                ctx.lineWidth = Math.max(0.5, p.width * (1 - e));
                ctx.beginPath();
                ctx.ellipse(p.x, p.y, 10 + p.radius * e, (10 + p.radius * e) * 0.45, 0, 0, Math.PI * 2);
                ctx.stroke();
            } else if (p.type === 'burst') {
                const r = p.radius * (0.3 + 0.7 * easeOutCubic(t));
                const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, r);
                g.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
                g.addColorStop(0.35, 'rgba(255, 230, 160, 0.6)');
                g.addColorStop(1, 'rgba(242, 193, 78, 0)');
                ctx.globalAlpha = 1 - t;
                ctx.globalCompositeOperation = 'lighter';
                ctx.fillStyle = g;
                ctx.beginPath();
                ctx.arc(p.x, p.y, r, 0, Math.PI * 2);
                ctx.fill();
            } else if (p.type === 'confetti') {
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.scale(1, Math.cos(p.sway * 1.6));
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            }
            ctx.restore();
        }
    }

    /* ---------------------------------------------------------------- */
    /* Bone pose                                                        */
    /* ---------------------------------------------------------------- */

    function setSheen(time, period, sweep) {
        const phase = (time % period) / sweep;
        const x = phase <= 1 ? lerp(-260, 760, easeInOutSine(phase)) : -400;
        for (const el of sheens) el.setAttribute('x', String(Math.round(x)));
    }

    function renderIntact(t, time) {
        const ap = clamp((t - appearAt) / 600);
        const appear = lerp(0.82, 1, easeOutBack(ap, 2.2));
        const pp = pulseAt ? clamp((t - pulseAt) / 420) : 1;
        const pulse = 1 + 0.055 * Math.sin(pp * Math.PI) * (1 - pp * 0.4);

        const calm = 1 - clamp(progress * 4);
        const bob = Math.sin(time * 1.7) * 6 * calm;
        const sway = Math.sin(time * 0.9) * 1.2 * calm;
        rig.style.transform = `translate(0px, ${bob.toFixed(2)}px) rotate(${sway.toFixed(2)}deg) scale(${(appear * pulse).toFixed(4)})`;
        rig.style.opacity = String(clamp(ap * 1.6));

        const shake = energy * 4 + progress * progress * 3.5;
        const sx = (Math.random() - 0.5) * shake;
        const sy = (Math.random() - 0.5) * shake;
        const tremble = progress > 0.55 ? Math.sin(time * 70) * (progress - 0.55) * 2.2 : 0;
        const bend = Math.pow(progress, 1.5) * 11;
        left.style.transform = `translate(${sx.toFixed(2)}px, ${sy.toFixed(2)}px) rotate(${(bend + tremble).toFixed(2)}deg)`;
        right.style.transform = `translate(${sx.toFixed(2)}px, ${sy.toFixed(2)}px) rotate(${(-bend - tremble).toFixed(2)}deg)`;

        const heat = Math.pow(progress, 1.7) * (0.82 + 0.18 * Math.sin(time * 12));
        for (const el of stress) el.setAttribute('opacity', heat.toFixed(3));

        shadow.setAttribute('rx', (250 - bob * 2.2).toFixed(1));
        shadow.setAttribute('opacity', (0.4 - bob * 0.012).toFixed(3));

        setSheen(time, progress > 0.02 ? 9999 : 3.6, 1.3);
    }

    function renderBroken(t, time) {
        const bt = (t - brokenAt) / 1000;
        rig.style.transform = 'none';
        rig.style.opacity = '1';

        if (bt < HIT_STOP) {
            const jolt = (Math.random() - 0.5) * 10;
            left.style.transform = `translate(${jolt}px, ${-jolt}px) rotate(12deg)`;
            right.style.transform = `translate(${jolt}px, ${jolt}px) rotate(-12deg)`;
            return;
        }

        const st = bt - HIT_STOP;
        const split = easeOutCubic(clamp(st / 0.55));
        const rise = easeOutBack(clamp((st - 0.8) / 0.9), 1.4);
        const float = st > 1.7 ? Math.sin((st - 1.7) * 2.2) * 5 : 0;
        const wobble = Math.sin(st * 22) * Math.exp(-st * 4) * 6;

        for (const [el, dir] of [[left, -1], [right, 1]]) {
            const dx = dir * (150 * split - 36 * rise);
            const dy = 34 * split - 82 * rise + float * (dir === -1 ? 1 : 0.8);
            const angle = lerp(12 + 24 * split, -16, clamp(rise, 0, 1.1)) + wobble;
            el.style.transform = `translate(${dx.toFixed(2)}px, ${dy.toFixed(2)}px) rotate(${(-dir * angle).toFixed(2)}deg)`;
        }

        for (const el of stress) el.setAttribute('opacity', (Math.max(0, 1 - st * 3)).toFixed(3));

        const glow = clamp((st - 0.75) / 0.6);
        rays.setAttribute('opacity', (glow * 0.6).toFixed(3));
        rays.style.transform = `rotate(${(time * 10) % 360}deg) scale(${(0.6 + 0.4 * easeOutCubic(glow)).toFixed(3)})`;
        halo.setAttribute('opacity', (glow * (0.75 + 0.15 * Math.sin(time * 3))).toFixed(3));

        shadow.setAttribute('rx', (250 + 150 * split - 40 * clamp(rise)).toFixed(1));
        shadow.setAttribute('opacity', (0.4 - 0.22 * split).toFixed(3));

        setSheen(time, 2.6, 1.1);
    }

    function loop(t) {
        const dt = Math.min(0.05, (t - lastT) / 1000);
        lastT = t;
        const time = t / 1000;
        energy *= 0.9;

        if (brokenAt) renderBroken(t, time);
        else renderIntact(t, time);

        updateParticles(dt);
        drawParticles();
        requestAnimationFrame(loop);
    }

    resize();
    window.addEventListener('resize', resize);
    requestAnimationFrame(loop);

    return {
        reset() {
            const wasUsed = brokenAt !== null || progress > 0;
            progress = 0;
            energy = 0;
            crackStage = 0;
            brokenAt = null;
            particles.length = 0;
            if (wasUsed) appearAt = performance.now();
            wrap.classList.remove('glow');
            edges.forEach((e) => e.setAttribute('opacity', '0'));
            rays.setAttribute('opacity', '0');
            halo.setAttribute('opacity', '0');
            renderCracks();
        },

        setProgress(value) {
            if (brokenAt) return;
            progress = clamp(value);
            const stage = renderCracks();
            if (stage > crackStage) {
                const f = boneFrame();
                for (let i = 0; i < 4; i++) spawnShard(f, { size: rand(2, 4), speed: rand(120, 320), life: rand(0.5, 0.9) });
                spawnDust(f, 3, 0.5);
                energy = Math.min(3, energy + 0.8);
            }
            crackStage = stage;
        },

        pulse() {
            pulseAt = performance.now();
        },

        hit(amount) {
            if (brokenAt || amount <= 0) return;
            energy = Math.min(3, energy + amount * 0.04);
            const f = boneFrame();
            const chips = Math.min(5, Math.ceil(amount / 18));
            for (let i = 0; i < chips; i++) {
                spawnShard(f, { size: rand(2, 4.5), speed: rand(150, 420), life: rand(0.6, 1.1) });
            }
        },

        breakBone() {
            if (brokenAt) return;
            brokenAt = performance.now();
            progress = 1;
            renderCracks();

            const f = boneFrame();
            setTimeout(() => {
                edges.forEach((e) => e.setAttribute('opacity', '1'));
                spawnBurst(f);
                spawnRing(f);
                spawnRing(f, { delay: 0.12, radius: 560, width: 6, life: 0.9, color: '242, 193, 78' });
                spawnSparks(f, 46);
                for (let i = 0; i < 64; i++) spawnShard(f);
                for (let i = 0; i < 22; i++) spawnShard(f, { angle: rand(0, Math.PI * 2), speed: rand(500, 1300), size: rand(3, 7) });
                spawnDust(f, 24);
            }, HIT_STOP * 1000);

            setTimeout(() => {
                wrap.classList.add('glow');
                spawnSparks(f, 24, 0.6);
                spawnConfetti(220);
            }, 900);
            setTimeout(() => spawnConfetti(140), 2200);
        },

        isBroken() {
            return brokenAt !== null;
        },
    };
}

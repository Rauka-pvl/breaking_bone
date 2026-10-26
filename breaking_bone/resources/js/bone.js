const VIEW_W = 600;
const VIEW_H = 220;
const CENTER_Y = 110;
const GRAVITY = 1500;

const clamp = (v, min = 0, max = 1) => Math.min(max, Math.max(min, v));
const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
const easeOutBack = (t) => {
    const c1 = 1.70158;
    const c3 = c1 + 1;
    return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
};
const rand = (min, max) => min + Math.random() * (max - min);

const SIDE_COLORS = {
    left: ['#2f7cf6', '#7fb0ff', '#f2c14e', '#ffffff'],
    right: ['#e5383b', '#ff8a8c', '#f2c14e', '#ffffff'],
    draw: ['#2f7cf6', '#e5383b', '#f2c14e', '#ffffff'],
};

export function createBone({ wrap, canvas }) {
    const left = wrap.querySelector('.half-left');
    const right = wrap.querySelector('.half-right');
    const shadow = wrap.querySelector('.bone-shadow');
    const edges = [...wrap.querySelectorAll('.edge')];
    const cracks = [...wrap.querySelectorAll('.crack')].map((el) => ({
        el,
        at: Number(el.dataset.at),
    }));
    const ctx = canvas.getContext('2d');

    let progress = 0;
    let balance = 0;
    let shownBalance = 0;
    let energy = 0;
    let brokenAt = null;
    let winner = null;
    let lastT = performance.now();
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
        const scale = r.width / VIEW_W;
        return {
            x: r.left - c.left + r.width / 2,
            y: r.top - c.top + (CENTER_Y / VIEW_H) * r.height,
            scale,
        };
    }

    function renderCracks() {
        for (const crack of cracks) {
            const local = brokenAt ? 1 : clamp((progress - crack.at) / 0.12);
            crack.el.setAttribute('stroke-dashoffset', String(1 - local));
        }
    }

    function spawnShard(f, opts = {}) {
        const angle = opts.angle ?? rand(-Math.PI, 0);
        const speed = opts.speed ?? rand(250, 900);
        const size = (opts.size ?? rand(5, 16)) * Math.max(f.scale, 0.6);
        const corners = 3 + Math.floor(Math.random() * 3);
        const points = Array.from({ length: corners }, (_, i) => {
            const a = (i / corners) * Math.PI * 2 + rand(-0.4, 0.4);
            const d = size * rand(0.5, 1);
            return [Math.cos(a) * d, Math.sin(a) * d];
        });
        particles.push({
            type: 'shard',
            x: f.x + rand(-10, 10) * f.scale,
            y: f.y + rand(-14, 14) * f.scale,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            rot: rand(0, Math.PI * 2),
            vr: rand(-12, 12),
            points,
            life: opts.life ?? rand(1.4, 2.4),
            age: 0,
            shade: rand(0.82, 1),
        });
    }

    function spawnDust(f, count) {
        for (let i = 0; i < count; i++) {
            const a = rand(0, Math.PI * 2);
            const s = rand(40, 260);
            particles.push({
                type: 'dust',
                x: f.x + rand(-12, 12) * f.scale,
                y: f.y + rand(-12, 12) * f.scale,
                vx: Math.cos(a) * s,
                vy: Math.sin(a) * s - 40,
                r: rand(6, 22) * f.scale,
                life: rand(0.8, 1.8),
                age: 0,
            });
        }
    }

    function spawnConfetti(side, count) {
        const colors = SIDE_COLORS[side] ?? SIDE_COLORS.draw;
        const w = canvas.clientWidth;
        for (let i = 0; i < count; i++) {
            particles.push({
                type: 'confetti',
                x: rand(0, w),
                y: rand(-200, -10),
                vx: rand(-60, 60),
                vy: rand(120, 320),
                rot: rand(0, Math.PI * 2),
                vr: rand(-8, 8),
                w: rand(6, 14),
                h: rand(10, 22),
                sway: rand(0, Math.PI * 2),
                color: colors[Math.floor(Math.random() * colors.length)],
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
            if (p.type === 'shard') {
                p.vy += GRAVITY * dt;
                p.vx *= 0.995;
                p.rot += p.vr * dt;
            } else if (p.type === 'dust') {
                p.vx *= 0.94;
                p.vy = p.vy * 0.94 - 10 * dt;
                p.r += 18 * dt;
            } else if (p.type === 'confetti') {
                p.sway += dt * 4;
                p.x += Math.sin(p.sway) * 40 * dt;
                p.rot += p.vr * dt;
            }
            p.x += p.vx * dt;
            p.y += p.vy * dt;
        }
    }

    function drawParticles() {
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        for (const p of particles) {
            const fade = 1 - clamp((p.age - p.life * 0.6) / (p.life * 0.4));
            ctx.save();
            ctx.globalAlpha = fade;
            if (p.type === 'shard') {
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.beginPath();
                p.points.forEach(([x, y], i) => (i ? ctx.lineTo(x, y) : ctx.moveTo(x, y)));
                ctx.closePath();
                const g = Math.round(230 * p.shade);
                ctx.fillStyle = `rgb(${Math.min(255, g + 22)}, ${g + 6}, ${Math.round(g * 0.82)})`;
                ctx.strokeStyle = '#6b5530';
                ctx.lineWidth = 1.2;
                ctx.fill();
                ctx.stroke();
            } else if (p.type === 'dust') {
                ctx.globalAlpha = fade * 0.28;
                ctx.fillStyle = '#efe2c4';
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
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

    function renderBone(t) {
        energy *= 0.9;
        shownBalance += (balance - shownBalance) * 0.08;

        if (!brokenAt) {
            const shake = energy * 5 + progress * progress * 3;
            const sx = (Math.random() - 0.5) * shake;
            const sy = (Math.random() - 0.5) * shake;
            const bend = Math.pow(progress, 1.6) * 8;
            const tiltX = shownBalance * 22;
            const tilt = shownBalance * 4;
            left.style.transform = `translate(${tiltX + sx}px, ${sy}px) rotate(${bend + tilt}deg)`;
            right.style.transform = `translate(${tiltX + sx}px, ${sy}px) rotate(${-bend + tilt}deg)`;
            shadow.setAttribute('cx', String(300 + tiltX));
            return;
        }

        const bt = (t - brokenAt) / 1000;
        const split = easeOutCubic(clamp(bt / 0.7));
        const rise = easeOutBack(clamp((bt - 0.9) / 0.9));

        const halves = { left: { el: left, dir: -1 }, right: { el: right, dir: 1 } };
        for (const [side, { el, dir }] of Object.entries(halves)) {
            let dx = dir * 150 * split;
            let dy = 26 * split;
            let rot = dir * 24 * split;
            let opacity = 1;

            if (winner === side) {
                dx = dir * (150 - 50 * rise);
                dy = 26 - 70 * rise;
                rot = dir * (24 - 30 * rise);
            } else if (winner && winner !== 'draw') {
                dy += 46 * rise;
                rot += dir * 16 * rise;
                opacity = 1 - 0.45 * rise;
            }

            el.style.transform = `translate(${dx}px, ${dy}px) rotate(${rot}deg)`;
            el.style.opacity = String(opacity);
        }

        shadow.setAttribute('rx', String(250 + 120 * split));
        shadow.setAttribute('opacity', String(0.35 - 0.15 * split));
    }

    function loop(t) {
        const dt = Math.min(0.05, (t - lastT) / 1000);
        lastT = t;
        renderBone(t);
        updateParticles(dt);
        drawParticles();
        requestAnimationFrame(loop);
    }

    resize();
    window.addEventListener('resize', resize);
    requestAnimationFrame(loop);

    return {
        reset() {
            progress = 0;
            balance = 0;
            shownBalance = 0;
            energy = 0;
            brokenAt = null;
            winner = null;
            particles.length = 0;
            wrap.classList.remove('glow-left', 'glow-right');
            edges.forEach((e) => e.setAttribute('opacity', '0'));
            left.style.opacity = '1';
            right.style.opacity = '1';
            shadow.setAttribute('rx', '250');
            shadow.setAttribute('opacity', '0.35');
            renderCracks();
        },

        setProgress(value) {
            progress = clamp(value);
            renderCracks();
        },

        setBalance(value) {
            balance = clamp(value, -1, 1);
        },

        hit(amount) {
            if (brokenAt || amount <= 0) return;
            energy = Math.min(3, energy + amount * 0.04);
            const f = boneFrame();
            const chips = Math.min(6, Math.ceil(amount / 15));
            for (let i = 0; i < chips; i++) {
                spawnShard(f, { size: rand(2, 5), speed: rand(150, 420), life: rand(0.6, 1.1) });
            }
        },

        breakBone(winnerSide) {
            if (brokenAt) return;
            brokenAt = performance.now();
            winner = winnerSide;
            progress = 1;
            renderCracks();
            edges.forEach((e) => e.setAttribute('opacity', '1'));

            const f = boneFrame();
            for (let i = 0; i < 70; i++) spawnShard(f);
            for (let i = 0; i < 24; i++) spawnShard(f, { angle: rand(0, Math.PI * 2), speed: rand(500, 1300), size: rand(3, 7) });
            spawnDust(f, 26);

            setTimeout(() => {
                if (winner && winner !== 'draw') wrap.classList.add(`glow-${winner}`);
                spawnConfetti(winner, 220);
            }, 900);
            setTimeout(() => spawnConfetti(winner, 140), 2200);
        },

        isBroken() {
            return brokenAt !== null;
        },
    };
}

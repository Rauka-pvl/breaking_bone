import QRCode from 'qrcode';
import {
    api, beep, config, crackSound, createClock, createEcho, escapeHtml,
    isEchoConnected, onEchoState, phaseOf, SIDES, unlockAudio,
} from './common';
import { createBone } from './bone';

const $ = (selector) => document.querySelector(selector);

const clock = createClock();
const bone = createBone({ wrap: $('#bone'), canvas: $('#fx') });
const els = {
    screen: $('#screen'),
    stage: $('#stage'),
    timer: $('#timer'),
    status: $('#status'),
    tugFill: $('#tugFill'),
    countdown: $('#countdown'),
    countNum: $('#countNum'),
    result: $('#result'),
    winnerName: $('#winnerName'),
    finalScore: $('#finalScore'),
    topPlayers: $('#topPlayers'),
    flash: $('#flash'),
    conn: $('#conn'),
    startBtn: $('#startBtn'),
    resetBtn: $('#resetBtn'),
};
const teams = Object.fromEntries(['left', 'right'].map((side) => {
    const root = $(`#team-${side}`);
    return [side, {
        root,
        qr: root.querySelector('.qr'),
        count: root.querySelector('[data-count]'),
        max: root.querySelector('[data-max]'),
        score: root.querySelector('[data-score]'),
        names: root.querySelector('[data-names]'),
    }];
}));

let state = null;
let scores = { left: 0, right: 0 };
let renderedKey = null;
let lastCount = null;
let lastScoreAt = 0;
let polling = false;
let finishing = false;
let goShownFor = null;

for (const side of ['left', 'right']) {
    QRCode.toDataURL(config.joinUrls[side], {
        width: 640,
        margin: 1,
        errorCorrectionLevel: 'M',
        color: { dark: '#08142b', light: '#ffffff' },
    }).then((src) => {
        teams[side].qr.querySelector('img').src = src;
    });
}

function setStatus(text) {
    els.status.textContent = text;
}

function renderPlayers(players, max) {
    for (const side of ['left', 'right']) {
        teams[side].count.textContent = players?.[side] ?? 0;
        if (max) teams[side].max.textContent = max;
    }
}

function addName(side, name) {
    const list = teams[side]?.names;
    if (!list || !name) return;
    const li = document.createElement('li');
    li.textContent = name;
    list.prepend(li);
    while (list.children.length > 14) list.lastElementChild.remove();
}

function setScores(next, { animate = true } = {}) {
    const delta = Math.max(0, next.left - scores.left) + Math.max(0, next.right - scores.right);
    scores = { left: next.left, right: next.right };

    teams.left.score.textContent = scores.left;
    teams.right.score.textContent = scores.right;

    const total = scores.left + scores.right;
    els.tugFill.style.width = `${total ? (scores.left / total) * 100 : 50}%`;
    bone.setBalance(total ? (scores.right - scores.left) / total : 0);

    if (animate) bone.hit(delta);
}

function applyState(next) {
    if (!next || typeof next.gameId !== 'number') return;
    if (state && next.gameId < state.gameId) return;

    const newGame = !state || next.gameId !== state.gameId;
    state = newGame ? { ...next } : { ...state, ...next };

    if (newGame) {
        teams.left.names.innerHTML = '';
        teams.right.names.innerHTML = '';
        setScores({ left: 0, right: 0 }, { animate: false });
    }

    renderPlayers(state.players, state.maxPerSide);

    if (next.scores) {
        const final = state.status === 'finished';
        setScores(final ? next.scores : {
            left: Math.max(scores.left, next.scores.left),
            right: Math.max(scores.right, next.scores.right),
        }, { animate: !newGame && !final });
    }

    tick();
}

function showCount(text, go = false) {
    els.countdown.classList.add('show');
    els.countNum.textContent = text;
    els.countNum.classList.toggle('go', go);
    els.countNum.style.animation = 'none';
    void els.countNum.offsetWidth;
    els.countNum.style.animation = '';
}

function hideCount() {
    els.countdown.classList.remove('show');
}

function setButtons(phase) {
    if (!els.startBtn) return;
    const busy = phase === 'countdown' || phase === 'playing' || phase === 'judging';
    els.startBtn.disabled = busy;
    els.resetBtn.disabled = busy;
    els.startBtn.textContent = phase === 'finished' ? 'Қайта ойнау · Ещё раз' : 'Бастау · Старт';
}

function showResult() {
    const winner = state.winner;
    if (!bone.isBroken()) {
        bone.breakBone(winner);
        crackSound();
        els.flash.classList.remove('go');
        void els.flash.offsetWidth;
        els.flash.classList.add('go');
        els.screen.classList.remove('quake');
        void els.screen.offsetWidth;
        els.screen.classList.add('quake');
    }

    const final = state.scores ?? scores;
    setScores(final, { animate: false });

    teams.left.root.classList.toggle('winner', winner === 'left');
    teams.right.root.classList.toggle('winner', winner === 'right');

    els.winnerName.innerHTML = winner === 'draw'
        ? 'Тең түсті! · <span style="opacity:.8">Ничья</span>'
        : `<span style="color:${SIDES[winner].color}">${SIDES[winner].kk} жеңді!</span> · <span style="opacity:.8">Победили ${SIDES[winner].ru.toLowerCase()}</span>`;
    els.finalScore.innerHTML = `<span style="color:${SIDES.left.color}">${final.left}</span> : <span style="color:${SIDES.right.color}">${final.right}</span>`;

    const top = state.top;
    els.topPlayers.innerHTML = top ? ['left', 'right'].map((side) => `
        <div><b style="color:${SIDES[side].color}">${SIDES[side].kk}</b>
            <ol>${(top[side] ?? []).map((p) => `<li>${escapeHtml(p.name)} — ${p.taps}</li>`).join('')}</ol>
        </div>`).join('') : '';

    setTimeout(() => {
        if (state.status === 'finished') els.result.classList.add('show');
    }, 1300);

    setStatus('');
}

function onPhase(phase) {
    els.result.classList.toggle('show', false);
    els.stage.classList.toggle('finished', phase === 'finished');
    setButtons(phase);

    if (phase !== 'finished') {
        teams.left.root.classList.remove('winner');
        teams.right.root.classList.remove('winner');
    }

    const dimQr = phase !== 'lobby' && phase !== 'finished';
    teams.left.qr.classList.toggle('dim', dimQr);
    teams.right.qr.classList.toggle('dim', dimQr);

    switch (phase) {
        case 'lobby':
            bone.reset();
            hideCount();
            els.timer.classList.remove('hurry');
            els.timer.textContent = `${(state?.roundSeconds ?? 10).toFixed(1)}`;
            setStatus('QR-ды сканерлеп, жағыңды таңда · Сканируйте QR и выберите сторону');
            break;
        case 'countdown':
            bone.reset();
            lastCount = null;
            setStatus('Дайындалыңыз! · Приготовьтесь!');
            break;
        case 'playing':
            setStatus('Бас! Бас! Бас! · Жми быстрее!');
            break;
        case 'judging':
            hideCount();
            els.timer.textContent = '0.0';
            els.timer.classList.remove('hurry');
            setStatus('Санап жатырмыз… · Считаем…');
            break;
        case 'finished':
            hideCount();
            els.timer.textContent = '';
            showResult();
            break;
    }
}

async function poll() {
    if (polling) return;
    polling = true;
    try {
        applyState(await api('/state', { clock }));
    } catch {
        // retried on the next tick
    } finally {
        polling = false;
    }
}

async function finish() {
    if (finishing) return;
    finishing = true;
    try {
        applyState(await api('/finish', { method: 'POST', clock }));
    } catch {
        // retried on the next tick
    }
    setTimeout(() => { finishing = false; }, 800);
}

function tick() {
    if (!state) return;
    const now = clock.now();
    const phase = phaseOf(state, now);
    const key = `${state.gameId}:${phase}`;

    if (key !== renderedKey) {
        renderedKey = key;
        onPhase(phase);
    }

    if (phase === 'countdown') {
        const secs = Math.ceil((state.startsAt - now) / 1000);
        if (secs !== lastCount && secs > 0) {
            lastCount = secs;
            showCount(String(secs));
            beep(520, 0.15);
        }
        els.timer.textContent = `${state.roundSeconds.toFixed(1)}`;
    }

    if (phase === 'playing') {
        if (goShownFor !== state.gameId) {
            goShownFor = state.gameId;
            showCount('СЫНДЫР!', true);
            beep(880, 0.35, 0.3);
            setTimeout(hideCount, 800);
        }

        const remaining = Math.max(0, (state.endsAt - now) / 1000);
        els.timer.textContent = remaining.toFixed(1);
        els.timer.classList.toggle('hurry', remaining <= 3);
        bone.setProgress(1 - remaining / state.roundSeconds);

        if (now - lastScoreAt > 1200) {
            lastScoreAt = now;
            poll();
        }
    }

    if (phase === 'judging' && now >= state.endsAt + (state.graceMs ?? 1000) + 150) {
        finish();
    }
}

setInterval(tick, 50);

const echo = createEcho();
if (echo) {
    const channel = echo.channel(config.channel);
    channel.listen('.players.updated', (e) => {
        if (!state || e.gameId !== state.gameId) return;
        state.players = e.players;
        renderPlayers(e.players);
        if (e.lastJoined) addName(e.lastJoined.side, e.lastJoined.name);
    });
    channel.listen('.game.started', (e) => applyState(e));
    channel.listen('.score.updated', (e) => {
        if (!state || e.gameId !== state.gameId) return;
        lastScoreAt = clock.now();
        setScores({
            left: Math.max(scores.left, e.scores.left),
            right: Math.max(scores.right, e.scores.right),
        });
    });
    channel.listen('.game.finished', (e) => applyState(e));
    channel.listen('.game.reset', (e) => applyState(e));

    const setConn = (s) => els.conn.classList.toggle('ok', s === 'connected');
    onEchoState(echo, setConn);
}

setInterval(() => {
    const phase = state ? phaseOf(state, clock.now()) : 'lobby';
    if (!isEchoConnected(echo) || phase === 'lobby' || phase === 'finished') poll();
}, 4000);

async function hostAction(path) {
    unlockAudio();
    try {
        applyState(await api(path, { method: 'POST', clock, headers: { 'X-Host-Key': config.hostKey } }));
    } catch (e) {
        if (e.status === 403 || e.status === 404) {
            alert('Кілт өшірілген · Ключ отключён администратором');
            window.location.reload();
            return;
        }
        alert(e.message);
        setButtons(phaseOf(state, clock.now()));
    }
}

els.startBtn?.addEventListener('click', () => {
    els.startBtn.disabled = true;
    hostAction('/start');
});

els.resetBtn?.addEventListener('click', () => hostAction('/reset'));

document.addEventListener('pointerdown', unlockAudio, { once: true });

poll();

import QRCode from 'qrcode';
import {
    api, beep, config, crackSound, createClock, createEcho, escapeHtml, formatSeconds,
    isEchoConnected, onEchoState, phaseOf, unlockAudio,
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
    qr: $('#qr'),
    playerCount: $('#playerCount'),
    maxPlayers: $('#maxPlayers'),
    names: $('#names'),
    board: $('#board'),
    raceFill: $('#raceFill'),
    raceLabel: $('#raceLabel'),
    countdown: $('#countdown'),
    countNum: $('#countNum'),
    result: $('#result'),
    winnerName: $('#winnerName'),
    winnerTime: $('#winnerTime'),
    podium: $('#podium'),
    flash: $('#flash'),
    conn: $('#conn'),
    startBtn: $('#startBtn'),
    resetBtn: $('#resetBtn'),
};

let state = null;
let leaderTaps = 0;
let tapSum = 0;
let renderedKey = null;
let lastCount = null;
let lastScoreAt = 0;
let polling = false;
let finishing = false;
let goShownFor = null;

QRCode.toDataURL(config.joinUrl, {
    width: 720,
    margin: 1,
    errorCorrectionLevel: 'M',
    color: { dark: '#08142b', light: '#ffffff' },
}).then((src) => {
    els.qr.querySelector('img').src = src;
});

const target = () => state?.targetTaps ?? 100;

function setStatus(text) {
    els.status.textContent = text;
}

function renderPlayers(count, max) {
    els.playerCount.textContent = count ?? 0;
    if (max) els.maxPlayers.textContent = max;
}

function addName(name) {
    if (!name) return;
    const li = document.createElement('li');
    li.textContent = name;
    els.names.prepend(li);
    while (els.names.children.length > 24) els.names.lastElementChild.remove();
}

function renderLeaders(leaders, { animate = true } = {}) {
    const list = leaders ?? [];
    const goal = target();

    els.board.innerHTML = list.map((p) => {
        const done = p.timeMs !== null;
        const pct = Math.min(100, (p.taps / goal) * 100);
        return `<li class="${done ? 'done' : ''}">
            <span class="place">${p.place}</span>
            <span class="who"><b>${escapeHtml(p.name)}</b><i style="width:${pct}%"></i></span>
            <span class="taps">${done ? formatSeconds(p.timeMs) : p.taps}</span>
        </li>`;
    }).join('');

    const leader = list[0];
    const top = leader?.taps ?? 0;
    const sum = list.reduce((acc, p) => acc + p.taps, 0);

    els.raceFill.style.width = `${Math.min(100, (top / goal) * 100)}%`;
    els.raceLabel.innerHTML = leader
        ? `Көшбасшы · Лидер: <b>${escapeHtml(leader.name)}</b> — ${top} / ${goal}`
        : `Бірінші ${goal} басқан жеңеді · Первый до ${goal} побеждает`;

    if (animate && sum > tapSum) bone.hit(sum - tapSum);
    tapSum = sum;
    leaderTaps = top;
}

function applyState(next) {
    if (!next || typeof next.gameId !== 'number') return;
    if (state && next.gameId < state.gameId) return;

    const newGame = !state || next.gameId !== state.gameId;
    state = newGame ? { ...next } : { ...state, ...next };

    if (newGame) {
        els.names.innerHTML = '';
        tapSum = 0;
        leaderTaps = 0;
    }

    renderPlayers(state.players, state.maxPlayers);
    if (next.leaders || newGame) renderLeaders(next.leaders ?? [], { animate: !newGame && state.status === 'playing' });

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
    if (!bone.isBroken()) {
        bone.breakBone();
        crackSound();
        els.flash.classList.remove('go');
        void els.flash.offsetWidth;
        els.flash.classList.add('go');
        els.screen.classList.remove('quake');
        void els.screen.offsetWidth;
        els.screen.classList.add('quake');
    }

    const winner = state.winner;
    els.winnerName.textContent = winner ? winner.name : 'Жеңімпаз жоқ · Нет победителя';
    els.winnerTime.textContent = winner
        ? (winner.timeMs !== null ? `${winner.taps} басу · ${formatSeconds(winner.timeMs)}` : `${winner.taps} басу · нажатий`)
        : '';

    els.podium.innerHTML = (state.leaders ?? []).slice(0, 3).map((p) => `
        <li class="p${p.place}">
            <span class="medal">${p.place}</span>
            <b>${escapeHtml(p.name)}</b>
            <small>${p.timeMs !== null ? formatSeconds(p.timeMs) : `${p.taps} басу`}</small>
        </li>`).join('');

    setTimeout(() => {
        if (state.status === 'finished') els.result.classList.add('show');
    }, 1300);

    setStatus('');
}

function onPhase(phase) {
    els.result.classList.toggle('show', false);
    els.stage.classList.toggle('finished', phase === 'finished');
    els.qr.classList.toggle('dim', phase !== 'lobby' && phase !== 'finished');
    setButtons(phase);
    bone.grip(phase !== 'lobby');

    switch (phase) {
        case 'lobby':
            bone.reset();
            hideCount();
            renderLeaders([], { animate: false });
            els.timer.classList.remove('hurry');
            els.timer.textContent = '0.0';
            setStatus('QR-ды сканерлеңіз · Сканируйте QR');
            break;
        case 'countdown':
            bone.reset();
            lastCount = null;
            els.timer.textContent = '0.0';
            setStatus('Дайындалыңыз! · Приготовьтесь!');
            break;
        case 'playing':
            setStatus(`Бас! ${target()} рет! · Жми ${target()} раз!`);
            break;
        case 'judging':
            hideCount();
            els.timer.classList.remove('hurry');
            els.timer.textContent = ((state.endsAt - state.startsAt) / 1000).toFixed(1);
            setStatus(leaderTaps >= target() ? 'Бар! Тексеріп жатырмыз… · Есть! Проверяем…' : 'Уақыт бітті! · Время вышло!');
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
            bone.pulse();
            beep(520, 0.15);
        }
    }

    if (phase === 'playing') {
        if (goShownFor !== state.gameId) {
            goShownFor = state.gameId;
            showCount('СЫНДЫР!', true);
            bone.pulse();
            beep(880, 0.35, 0.3);
            setTimeout(hideCount, 800);
        }

        const remaining = Math.max(0, (state.endsAt - now) / 1000);
        els.timer.textContent = Math.max(0, (now - state.startsAt) / 1000).toFixed(1);
        els.timer.classList.toggle('hurry', remaining <= 5);
        bone.setProgress(leaderTaps / target());

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
        if (e.lastJoined) addName(e.lastJoined.name);
    });
    channel.listen('.game.started', (e) => applyState(e));
    channel.listen('.score.updated', (e) => {
        if (!state || e.gameId !== state.gameId || state.status !== 'playing') return;
        lastScoreAt = clock.now();
        state.leaders = e.leaders;
        renderLeaders(e.leaders);
    });
    channel.listen('.round.stopped', (e) => applyState(e));
    channel.listen('.game.finished', (e) => applyState(e));
    channel.listen('.game.reset', (e) => applyState(e));

    onEchoState(echo, (s) => els.conn.classList.toggle('ok', s === 'connected'));
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

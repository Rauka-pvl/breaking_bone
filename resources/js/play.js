import { api, config, createClock, createEcho, isEchoConnected, phaseOf, SIDES } from './common';

const $ = (selector) => document.querySelector(selector);

const side = config.side;
const joinedKey = `joined.${config.room}`;
const root = $('#play');
const clock = createClock();
const els = {
    joinForm: $('#joinForm'),
    nameInput: $('#nameInput'),
    joinBtn: $('#joinBtn'),
    joinError: $('#joinError'),
    helloName: $('#helloName'),
    waitCounts: $('#waitCounts'),
    phoneCount: $('#phoneCount'),
    tapBtn: $('#tapBtn'),
    tapTime: $('#tapTime'),
    myTaps: $('#myTaps'),
    miniFill: $('#miniFill'),
    miniLeft: $('#miniLeft'),
    miniRight: $('#miniRight'),
    judgeTaps: $('#judgeTaps'),
    resultTitle: $('#resultTitle'),
    resultSub: $('#resultSub'),
    resultTaps: $('#resultTaps'),
    resultScore: $('#resultScore'),
    retryBtn: $('#retryBtn'),
};

const storage = {
    get: (key) => { try { return localStorage.getItem(`zhilik.${key}`); } catch { return null; } },
    set: (key, value) => { try { localStorage.setItem(`zhilik.${key}`, value); } catch { /* private mode */ } },
};

const clientId = storage.get('clientId') || (() => {
    const id = crypto.randomUUID?.() ?? `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
    storage.set('clientId', id);
    return id;
})();

let state = null;
let joined = false;
let joining = false;
let taps = 0;
let sent = 0;
let sending = false;
let lastCount = null;
let renderedKey = null;
let wakeLock = null;

els.nameInput.value = storage.get('name') ?? '';

function setView(name) {
    if (root.dataset.view !== name) root.dataset.view = name;
}

function renderCounts() {
    if (!state?.players) return;
    els.waitCounts.textContent = `${SIDES.left.kk}: ${state.players.left} · ${SIDES.right.kk}: ${state.players.right}`;
}

function renderMini(scores) {
    if (!scores) return;
    const total = scores.left + scores.right;
    els.miniFill.style.width = `${total ? (scores.left / total) * 100 : 50}%`;
    els.miniLeft.textContent = scores.left;
    els.miniRight.textContent = scores.right;
}

function applyState(next) {
    if (!next || typeof next.gameId !== 'number') return;
    if (state && next.gameId < state.gameId) return;

    if (!state || next.gameId !== state.gameId) {
        taps = 0;
        sent = 0;
        els.myTaps.textContent = '0';
    }

    state = { ...state, ...next };
    renderCounts();
    renderMini(state.scores);
    tick();
}

async function requestWakeLock() {
    try {
        wakeLock = await navigator.wakeLock?.request('screen');
    } catch {
        wakeLock = null;
    }
}

async function join(name) {
    if (joining) return;
    joining = true;
    els.joinBtn.disabled = true;
    els.joinError.textContent = '';

    try {
        const res = await api('/join', { method: 'POST', body: { clientId, side, name: name || null } });

        if (res.player.side !== side) {
            window.location.replace(config.playUrls[res.player.side]);
            return;
        }

        joined = true;
        storage.set(joinedKey, '1');
        storage.set('name', res.player.name);
        els.helloName.textContent = `Сәлем, ${res.player.name}!`;
        applyState(res.state);
        poll();
        requestWakeLock();
    } catch (e) {
        if (e.status === 409) {
            setView('full');
        } else if (e.status === 404) {
            els.joinError.textContent = 'Ойын жабық · Игра недоступна';
        } else {
            els.joinError.textContent = 'Қате · Ошибка соединения, попробуйте ещё раз';
        }
    } finally {
        joining = false;
        els.joinBtn.disabled = false;
    }
}

async function poll() {
    try {
        applyState(await api('/state', { clock }));
    } catch {
        // retried by the interval
    }
}

async function syncTaps({ final = false } = {}) {
    if (!joined || sending || taps <= sent) return;
    sending = true;
    const value = taps;
    try {
        const res = await api('/taps', { method: 'POST', body: { clientId, taps: value }, keepalive: final });
        if (res.accepted) sent = Math.max(sent, value);
        if (res.reason === 'not_joined') join(storage.get('name'));
    } catch {
        // retried by the interval
    } finally {
        sending = false;
    }
}

function showResult() {
    const winner = state.winner;
    const myTaps = Math.max(taps, sent);
    els.resultTaps.textContent = myTaps;

    els.resultTitle.className = 'result-title';
    if (winner === 'draw') {
        els.resultTitle.textContent = 'Тең!';
        els.resultSub.textContent = 'Ничья · Жілік екіге бөлінді';
    } else if (winner === side) {
        els.resultTitle.textContent = 'Жеңіс!';
        els.resultTitle.classList.add('win');
        els.resultSub.textContent = 'Победа! Жілікті сіздің жақ сындырды!';
        navigator.vibrate?.([80, 60, 80, 60, 200]);
    } else {
        els.resultTitle.textContent = 'Жеңіліс';
        els.resultTitle.classList.add('lose');
        els.resultSub.textContent = 'Поражение · Келесі жолы жеңесіз!';
        navigator.vibrate?.(300);
    }

    const s = state.scores ?? { left: 0, right: 0 };
    els.resultScore.innerHTML = `<span style="color:#7fb0ff">${s.left}</span> : <span style="color:#ff8a8c">${s.right}</span>`;
}

function tick() {
    if (!joined) {
        if (root.dataset.view !== 'full') setView('join');
        return;
    }
    if (!state) return;

    const now = clock.now();
    const phase = phaseOf(state, now);
    const key = `${state.gameId}:${phase}`;
    const changed = key !== renderedKey;
    renderedKey = key;

    switch (phase) {
        case 'lobby':
            setView('wait');
            break;
        case 'countdown': {
            setView('countdown');
            const secs = Math.max(1, Math.ceil((state.startsAt - now) / 1000));
            if (secs !== lastCount) {
                lastCount = secs;
                els.phoneCount.textContent = secs;
                els.phoneCount.classList.remove('pop');
                void els.phoneCount.offsetWidth;
                els.phoneCount.classList.add('pop');
                navigator.vibrate?.(40);
            }
            break;
        }
        case 'playing':
            if (changed) navigator.vibrate?.(120);
            setView('tap');
            els.tapTime.textContent = Math.max(0, (state.endsAt - now) / 1000).toFixed(1);
            break;
        case 'judging':
            setView('judging');
            els.judgeTaps.textContent = taps;
            if (changed) syncTaps({ final: true });
            break;
        case 'finished':
            setView('result');
            if (changed) showResult();
            break;
    }

    if (phase !== 'countdown') lastCount = null;
}

function onTap(event) {
    event.preventDefault();
    if (!joined || !state || phaseOf(state, clock.now()) !== 'playing') return;

    taps += 1;
    els.myTaps.textContent = taps;
    els.tapBtn.classList.add('hit');
    navigator.vibrate?.(8);
}

els.tapBtn.addEventListener('pointerdown', onTap);
els.tapBtn.addEventListener('pointerup', () => els.tapBtn.classList.remove('hit'));
els.tapBtn.addEventListener('pointercancel', () => els.tapBtn.classList.remove('hit'));
els.tapBtn.addEventListener('contextmenu', (e) => e.preventDefault());
document.addEventListener('touchmove', (e) => { if (root.dataset.view === 'tap') e.preventDefault(); }, { passive: false });
document.addEventListener('dblclick', (e) => e.preventDefault());

els.joinForm.addEventListener('submit', (e) => {
    e.preventDefault();
    join(els.nameInput.value.trim());
});

els.retryBtn.addEventListener('click', () => {
    setView('join');
    join(els.nameInput.value.trim() || storage.get('name'));
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        poll();
        if (joined && !wakeLock) requestWakeLock();
    }
});

setInterval(tick, 50);
setInterval(() => {
    const phase = state ? phaseOf(state, clock.now()) : 'lobby';
    if (phase === 'playing' || phase === 'judging') syncTaps();
}, config.tapSyncMs ?? 400);

const echo = createEcho();
if (echo) {
    const channel = echo.channel(config.channel);
    channel.listen('.players.updated', (e) => {
        if (!state || e.gameId !== state.gameId) return;
        state.players = e.players;
        renderCounts();
    });
    channel.listen('.game.started', (e) => applyState(e));
    channel.listen('.score.updated', (e) => {
        if (state && e.gameId === state.gameId) renderMini(e.scores);
    });
    channel.listen('.game.finished', (e) => applyState(e));
    channel.listen('.game.reset', (e) => {
        applyState(e);
        if (joined) {
            setTimeout(() => join(storage.get('name')), Math.random() * 1500);
        }
    });
}

let pollTick = 0;
setInterval(() => {
    pollTick += 1;
    const phase = state ? phaseOf(state, clock.now()) : 'lobby';
    const connected = isEchoConnected(echo);
    if (phase === 'judging' || !connected || (pollTick % 4 === 0 && (phase === 'lobby' || phase === 'finished'))) {
        poll();
    }
}, 2500);

poll().then(() => {
    if (storage.get(joinedKey) === '1') join(storage.get('name'));
});
tick();

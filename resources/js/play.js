import { api, config, createClock, createEcho, escapeHtml, formatSeconds, isEchoConnected, phaseOf } from './common';

const $ = (selector) => document.querySelector(selector);

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
    leaderInfo: $('#leaderInfo'),
    myTaps: $('#myTaps'),
    myProgress: $('#myProgress'),
    doneTime: $('#doneTime'),
    judgeTaps: $('#judgeTaps'),
    resultTitle: $('#resultTitle'),
    resultSub: $('#resultSub'),
    resultTaps: $('#resultTaps'),
    resultTime: $('#resultTime'),
    resultView: $('#resultView'),
    winnerCard: $('#winnerCard'),
    winnerLabel: $('#winnerLabel'),
    resultWinner: $('#resultWinner'),
    resultWinnerMeta: $('#resultWinnerMeta'),
    resultTop: $('#resultTop'),
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
let reachedAt = null;
let sending = false;
let lastCount = null;
let renderedKey = null;
let buzzedFor = null;
let wakeLock = null;

els.nameInput.value = storage.get('name') ?? '';

const target = () => state?.targetTaps ?? 200;

function setView(name) {
    if (root.dataset.view !== name) root.dataset.view = name;
}

function renderTaps() {
    els.myTaps.textContent = taps;
    els.myProgress.style.width = `${Math.min(100, (taps / target()) * 100)}%`;
}

function renderLeader(leaders) {
    const leader = leaders?.[0];
    els.leaderInfo.textContent = leader ? `👑 ${leader.name} · ${leader.taps}` : '';
}

function applyState(next) {
    if (!next || typeof next.gameId !== 'number') return;
    if (state && next.gameId < state.gameId) return;

    if (!state || next.gameId !== state.gameId) {
        taps = 0;
        sent = 0;
        reachedAt = null;
    }

    const resultShown = renderedKey === `${next.gameId}:finished`;
    state = { ...state, ...next };
    els.waitCounts.textContent = `Ойыншылар · Игроков: ${state.players ?? 0}`;
    renderTaps();
    renderLeader(state.leaders);
    tick();

    if (resultShown && next.me) showResult();
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
        const res = await api('/join', { method: 'POST', body: { clientId, name: name || null } });

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
        applyState(await api(`/state?clientId=${encodeURIComponent(clientId)}`, { clock }));
    } catch {
        // retried by the interval
    }
}

async function syncTaps({ final = false } = {}) {
    if (!joined || sending || taps <= sent) return;
    sending = true;
    const value = taps;
    try {
        const res = await api('/taps', {
            method: 'POST',
            body: { clientId, taps: value, reachedAt },
            keepalive: final,
        });
        if (res.accepted) sent = Math.max(sent, value);
        if (res.reason === 'not_joined') join(storage.get('name'));
    } catch {
        // retried by the interval
    } finally {
        sending = false;
    }
    if (reachedAt && taps > sent) syncTaps({ final: true });
}

function showResult() {
    const me = state.me;
    const vibrate = (pattern) => {
        if (buzzedFor === state.gameId) return;
        buzzedFor = state.gameId;
        setTimeout(() => navigator.vibrate?.(pattern), 1800);
    };
    const winner = state.winner;
    const myTaps = Math.max(me?.taps ?? 0, sent);
    els.resultTaps.textContent = myTaps;
    els.resultTime.textContent = me?.timeMs != null ? `уақыт · время: ${formatSeconds(me.timeMs)}` : 'менің басуым · мои нажатия';
    els.winnerCard.classList.toggle('none', !winner);
    els.winnerLabel.textContent = me?.place === 1 ? 'Сіз жеңдіңіз! · Вы победили!' : 'Жеңімпаз · Победитель';
    els.resultWinner.textContent = winner?.name ?? '';
    els.resultWinnerMeta.textContent = winner
        ? `${winner.taps} басу${winner.timeMs != null ? ` · ${formatSeconds(winner.timeMs)}` : ' · нажатий'}`
        : '';
    els.resultTop.innerHTML = (state.leaders ?? []).slice(1, 3).map((p) => `
        <li><span class="medal m${p.place}">${p.place}</span><b>${escapeHtml(p.name)}</b><small>${p.taps}</small></li>`).join('');

    els.resultTitle.className = 'result-title';
    if (me?.place === 1) {
        els.resultTitle.textContent = 'Жеңіс!';
        els.resultTitle.classList.add('win');
        els.resultSub.textContent = 'Победа! Жілікті сіз сындырдыңыз!';
        vibrate([80, 60, 80, 60, 200]);
    } else if (me?.place) {
        els.resultTitle.textContent = `${me.place}-орын`;
        els.resultTitle.classList.add(me.place <= 3 ? 'win' : 'lose');
        els.resultSub.textContent = `${me.place}-е место · Келесі жолы жеңесіз!`;
        vibrate(200);
    } else {
        els.resultTitle.textContent = 'Ойын аяқталды';
        els.resultTitle.classList.add('lose');
        els.resultSub.textContent = me ? 'Игра окончена' : 'Нәтиже санауда · Считаем места…';
    }
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
            setView(reachedAt ? 'done' : 'tap');
            break;
        case 'judging':
            setView(reachedAt ? 'done' : 'judging');
            els.judgeTaps.textContent = taps;
            if (changed) syncTaps({ final: true });
            break;
        case 'finished':
            setView('result');
            if (changed) {
                els.resultView.classList.remove('go');
                void els.resultView.offsetWidth;
                els.resultView.classList.add('go');
                navigator.vibrate?.([0, 530, 40, 440, 40, 450, 120]);
                showResult();
                if (!state.me) setTimeout(poll, Math.random() * 600);
            }
            break;
    }

    if (phase !== 'countdown') lastCount = null;
}

function onTap(event) {
    event.preventDefault();
    if (!joined || !state || reachedAt || phaseOf(state, clock.now()) !== 'playing') return;

    taps += 1;
    renderTaps();
    els.tapBtn.classList.add('hit');
    navigator.vibrate?.(8);

    if (taps >= target()) {
        reachedAt = Math.round(clock.now());
        els.doneTime.textContent = formatSeconds(reachedAt - state.startsAt);
        navigator.vibrate?.([60, 40, 160]);
        setView('done');
        syncTaps({ final: true });
    }
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
        els.waitCounts.textContent = `Ойыншылар · Игроков: ${e.players}`;
    });
    channel.listen('.game.started', (e) => applyState(e));
    channel.listen('.score.updated', (e) => {
        if (state && e.gameId === state.gameId) renderLeader(e.leaders);
    });
    channel.listen('.round.stopped', (e) => applyState(e));
    channel.listen('.game.finished', (e) => {
        applyState({ ...e, me: null });
        setTimeout(poll, 200 + Math.random() * 800);
    });
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

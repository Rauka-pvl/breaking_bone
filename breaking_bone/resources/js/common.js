import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export const config = window.ZHILIK ?? {};

export const SIDES = {
    left: { kk: 'Көк жақ', ru: 'Синие', color: '#2f7cf6' },
    right: { kk: 'Қызыл жақ', ru: 'Красные', color: '#e5383b' },
};

export function createEcho() {
    const { reverb } = config;
    if (!reverb?.key || !reverb?.host) {
        return null;
    }

    window.Pusher = Pusher;
    const tls = reverb.scheme === 'https';

    return new Echo({
        broadcaster: 'reverb',
        key: reverb.key,
        wsHost: reverb.host,
        wsPort: reverb.port || (tls ? 443 : 80),
        wssPort: reverb.port || 443,
        forceTLS: tls,
        enabledTransports: ['ws', 'wss'],
    });
}

export function isEchoConnected(echo) {
    return echo?.connector?.pusher?.connection?.state === 'connected';
}

export function onEchoState(echo, callback) {
    const connection = echo?.connector?.pusher?.connection;
    if (!connection) return;
    connection.bind('state_change', ({ current }) => callback(current));
    callback(connection.state);
}

export function createClock() {
    let offset = 0;
    let bestRtt = Infinity;

    return {
        sync(serverNow, sentAt, receivedAt) {
            const rtt = receivedAt - sentAt;
            if (rtt <= bestRtt * 1.5 || bestRtt === Infinity) {
                bestRtt = Math.min(bestRtt, rtt);
                offset = serverNow - (sentAt + receivedAt) / 2;
            }
        },
        now() {
            return Date.now() + offset;
        },
    };
}

export async function api(path, { method = 'GET', body, headers = {}, clock, keepalive = false } = {}) {
    const sentAt = Date.now();
    const response = await fetch(`${config.api}${path}`, {
        method,
        keepalive,
        headers: {
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    const receivedAt = Date.now();
    const data = await response.json().catch(() => ({}));

    if (clock && typeof data.serverNow === 'number') {
        clock.sync(data.serverNow, sentAt, receivedAt);
    }

    if (!response.ok) {
        const error = new Error(data.message || `HTTP ${response.status}`);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

export function phaseOf(state, now) {
    if (!state) return 'lobby';
    if (state.status === 'lobby') return 'lobby';
    if (state.status === 'finished') return 'finished';
    if (now < state.startsAt) return 'countdown';
    if (now < state.endsAt) return 'playing';
    return 'judging';
}

let audioContext = null;

export function unlockAudio() {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    audioContext ??= new Ctx();
    if (audioContext.state === 'suspended') audioContext.resume();
    return audioContext;
}

export function beep(frequency = 660, duration = 0.12, volume = 0.25) {
    const ctx = audioContext;
    if (!ctx) return;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'triangle';
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(volume, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
    osc.connect(gain).connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

export function crackSound() {
    const ctx = audioContext;
    if (!ctx) return;
    const length = Math.floor(ctx.sampleRate * 0.6);
    const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < length; i++) {
        const t = i / length;
        const crackle = Math.random() < 0.02 ? 1 : 0.35;
        data[i] = (Math.random() * 2 - 1) * crackle * Math.pow(1 - t, 4);
    }
    const source = ctx.createBufferSource();
    source.buffer = buffer;
    const filter = ctx.createBiquadFilter();
    filter.type = 'bandpass';
    filter.frequency.value = 1800;
    filter.Q.value = 0.8;
    const gain = ctx.createGain();
    gain.gain.value = 0.9;
    source.connect(filter).connect(gain).connect(ctx.destination);
    source.start();

    const thump = ctx.createOscillator();
    const thumpGain = ctx.createGain();
    thump.frequency.setValueAtTime(140, ctx.currentTime);
    thump.frequency.exponentialRampToValueAtTime(40, ctx.currentTime + 0.3);
    thumpGain.gain.setValueAtTime(0.6, ctx.currentTime);
    thumpGain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
    thump.connect(thumpGain).connect(ctx.destination);
    thump.start();
    thump.stop(ctx.currentTime + 0.35);
}

export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[c]);
}

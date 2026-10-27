const STORAGE_KEY = 'zhilik.music';
const FADE_MS = 1200;

/**
 * Looping background tracks with cross-fades. Browsers may block playback until the
 * first user gesture, so a blocked track starts again from unlock().
 * A track is a URL or { src, start } where start (seconds) skips the intro, also on loop.
 */
export function createMusic(tracks, { volume = 0.55, onChange = () => {} } = {}) {
    const starts = new Map();
    const players = Object.fromEntries(Object.entries(tracks).map(([name, track]) => {
        const { src, start = 0 } = typeof track === 'string' ? { src: track } : track;
        const audio = new Audio(src);
        audio.preload = 'auto';
        audio.volume = 0;
        starts.set(audio, start);
        rewind(audio);
        audio.addEventListener('ended', () => {
            rewind(audio);
            audio.play().catch(() => {});
        });
        return [name, audio];
    }));

    function rewind(audio) {
        const start = starts.get(audio);
        if (audio.readyState >= 1) {
            audio.currentTime = start;
        } else {
            audio.addEventListener('loadedmetadata', () => { audio.currentTime = start; }, { once: true });
        }
    }
    const fades = new Map();
    let current = null;
    let blocked = false;
    let muted = localStorage.getItem(STORAGE_KEY) === 'off';

    function fade(audio, to, done) {
        clearInterval(fades.get(audio));
        const from = audio.volume;
        const started = performance.now();
        fades.set(audio, setInterval(() => {
            const k = Math.min(1, (performance.now() - started) / FADE_MS);
            audio.volume = from + (to - from) * k;
            if (k < 1) return;
            clearInterval(fades.get(audio));
            fades.delete(audio);
            done?.();
        }, 40));
    }

    function start(audio) {
        audio.play().then(() => {
            blocked = false;
            fade(audio, volume);
        }, () => {
            blocked = true;
        });
    }

    function stop(audio) {
        fade(audio, 0, () => audio.pause());
    }

    return {
        play(name, { restart = false } = {}) {
            const next = players[name];
            if (!next) return;
            if (current === name && !restart) return;
            if (current && current !== name) stop(players[current]);
            current = name;
            if (restart) rewind(next);
            if (!muted) start(next);
        },
        unlock() {
            if (blocked && current && !muted) start(players[current]);
        },
        toggle() {
            if (blocked && !muted && current) {
                start(players[current]);
                return muted;
            }
            muted = !muted;
            localStorage.setItem(STORAGE_KEY, muted ? 'off' : 'on');
            if (current) muted ? stop(players[current]) : start(players[current]);
            onChange(muted);
            return muted;
        },
        get muted() {
            return muted;
        },
    };
}

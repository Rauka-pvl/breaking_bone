<?php

namespace App\Http\Controllers;

use App\Models\HostKey;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class GameController extends Controller
{
    private const SESSION_KEY = 'host_key_id';

    public function __construct(private readonly GameService $games) {}

    public function screen(Request $request): View|RedirectResponse
    {
        if ($request->filled('key')) {
            return $this->loginHost($request, (string) $request->query('key'));
        }

        $room = $this->sessionRoom($request);

        if (! $room) {
            return view('host-login');
        }

        return view('screen', [
            'room' => $room,
            'joinUrl' => url("/play/{$room->code}"),
            'config' => $this->clientConfig($room) + ['hostKey' => $room->key],
        ]);
    }

    public function hostLogin(Request $request): RedirectResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:40']]);

        return $this->loginHost($request, $data['key']);
    }

    public function hostLogout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect('/');
    }

    public function play(HostKey $room): View
    {
        abort_unless($room->active, 404);

        return view('play', ['config' => $this->clientConfig($room)]);
    }

    public function state(Request $request, HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $clientId = $request->query('clientId');

        return response()->json($this->games->state(
            $this->games->currentFresh($room),
            is_string($clientId) && $clientId !== '' ? mb_substr($clientId, 0, 64) : null,
        ));
    }

    public function join(Request $request, HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $data = $request->validate([
            'clientId' => ['required', 'string', 'max:64'],
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        $name = isset($data['name']) ? trim(strip_tags($data['name'])) : null;

        try {
            return response()->json(
                $this->games->join($room, $data['clientId'], $name !== '' ? $name : null)
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'game_full') {
                throw $e;
            }

            return response()->json([
                'error' => 'game_full',
                'message' => 'Орын жоқ / Мест нет',
            ], 409);
        }
    }

    public function leave(Request $request, HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $data = $request->validate([
            'clientId' => ['required', 'string', 'max:64'],
        ]);

        $this->games->leave($room, $data['clientId']);

        return response()->json(['ok' => true]);
    }

    public function taps(Request $request, HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $data = $request->validate([
            'clientId' => ['required', 'string', 'max:64'],
            'taps' => ['required', 'integer', 'min:0', 'max:100000'],
            'reachedAt' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json($this->games->recordTaps(
            $room,
            $data['clientId'],
            (int) $data['taps'],
            isset($data['reachedAt']) ? (int) $data['reachedAt'] : null,
        ));
    }

    public function start(Request $request, HostKey $room): JsonResponse
    {
        $this->authorizeHost($request, $room);

        return response()->json($this->games->state($this->games->start($room)));
    }

    public function finish(HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        return response()->json($this->games->state($this->games->finish($room)));
    }

    public function reset(Request $request, HostKey $room): JsonResponse
    {
        $this->authorizeHost($request, $room);

        return response()->json($this->games->state($this->games->reset($room)));
    }

    private function loginHost(Request $request, string $key): RedirectResponse
    {
        $room = HostKey::findActiveByKey($key);

        if (! $room) {
            return redirect('/')
                ->withErrors(['key' => 'Кілт қате немесе өшірілген · Неверный или отключённый ключ'])
                ->withInput(['key' => $key]);
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $room->id);

        return redirect('/');
    }

    private function sessionRoom(Request $request): ?HostKey
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $room = $id ? HostKey::query()->whereKey($id)->where('active', true)->first() : null;

        if ($id && ! $room) {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $room;
    }

    private function ensureActive(HostKey $room): void
    {
        abort_unless($room->active, 404);
    }

    private function authorizeHost(Request $request, HostKey $room): void
    {
        $this->ensureActive($room);

        $key = strtoupper(trim((string) $request->header('X-Host-Key', '')));

        abort_unless($key !== '' && hash_equals($room->key, $key), 403);
    }

    private function clientConfig(HostKey $room): array
    {
        return [
            'reverb' => config('zhilik.client_reverb'),
            'room' => $room->code,
            'channel' => $room->channel(),
            'api' => url("/api/rooms/{$room->code}"),
        ];
    }
}

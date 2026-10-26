<?php

namespace App\Http\Controllers;

use App\Models\HostKey;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'joinUrls' => [
                'left' => url("/play/{$room->code}/left"),
                'right' => url("/play/{$room->code}/right"),
            ],
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

    public function play(HostKey $room, string $side): View
    {
        abort_unless($room->active, 404);
        abort_unless(in_array($side, config('zhilik.sides'), true), 404);

        return view('play', [
            'side' => $side,
            'config' => $this->clientConfig($room) + [
                'playUrls' => [
                    'left' => url("/play/{$room->code}/left"),
                    'right' => url("/play/{$room->code}/right"),
                ],
            ],
        ]);
    }

    public function state(HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        return response()->json($this->games->state($this->games->currentFresh($room)));
    }

    public function join(Request $request, HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $data = $request->validate([
            'clientId' => ['required', 'string', 'max:64'],
            'side' => ['required', Rule::in(config('zhilik.sides'))],
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        $name = isset($data['name']) ? trim(strip_tags($data['name'])) : null;

        try {
            return response()->json(
                $this->games->join($room, $data['clientId'], $data['side'], $name !== '' ? $name : null)
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'side_full') {
                throw $e;
            }

            return response()->json([
                'error' => 'side_full',
                'message' => 'Бұл жақта орын жоқ / На этой стороне нет мест',
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
        ]);

        return response()->json($this->games->recordTaps($room, $data['clientId'], (int) $data['taps']));
    }

    public function start(Request $request, HostKey $room): JsonResponse
    {
        $this->authorizeHost($request, $room);

        return response()->json($this->games->state($this->games->start($room)));
    }

    public function finish(HostKey $room): JsonResponse
    {
        $this->ensureActive($room);

        $game = $this->games->finish($room);
        $state = $this->games->state($game);

        if ($game->isFinished()) {
            $state['top'] = $this->games->topPlayers($game);
        }

        return response()->json($state);
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
            'reverb' => [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => (int) config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            ],
            'room' => $room->code,
            'channel' => $room->channel(),
            'api' => url("/api/rooms/{$room->code}"),
        ];
    }
}

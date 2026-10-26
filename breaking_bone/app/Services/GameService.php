<?php

namespace App\Services;

use App\Events\GameFinished;
use App\Events\GameReset;
use App\Events\GameStarted;
use App\Events\PlayersUpdated;
use App\Events\ScoreUpdated;
use App\Events\ZhilikEvent;
use App\Models\Game;
use App\Models\HostKey;
use App\Models\Player;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class GameService
{
    public function current(HostKey $room): Game
    {
        return Game::query()->where('host_key_id', $room->id)->latest('id')->first()
            ?? Game::query()->create(['host_key_id' => $room->id, 'status' => Game::LOBBY]);
    }

    /**
     * Returns the room's current game, finishing it first if its round is over.
     */
    public function currentFresh(HostKey $room): Game
    {
        $game = $this->current($room);

        if ($game->isPlaying() && $this->nowMs() >= $this->finishAllowedAtMs($game)) {
            $game = $this->finish($room, $game);
        }

        return $game;
    }

    public function state(Game $game): array
    {
        $counts = $this->playerCounts($game);
        $scores = $game->isFinished()
            ? ['left' => $game->left_taps, 'right' => $game->right_taps]
            : $this->tapTotals($game);

        return [
            'gameId' => $game->id,
            'status' => $game->status,
            'phase' => $this->phase($game),
            'startsAt' => $game->starts_at_ms,
            'endsAt' => $game->ends_at_ms,
            'serverNow' => $this->nowMs(),
            'players' => $counts,
            'scores' => $scores,
            'winner' => $game->winner,
            'maxPerSide' => config('zhilik.max_per_side'),
            'roundSeconds' => config('zhilik.round_seconds'),
            'tapSyncMs' => config('zhilik.tap_sync_ms'),
            'graceMs' => config('zhilik.grace_seconds') * 1000,
        ];
    }

    /**
     * @throws RuntimeException when the side is full
     */
    public function join(HostKey $room, string $clientId, string $side, ?string $name): array
    {
        $game = $this->currentFresh($room);
        $max = (int) config('zhilik.max_per_side');

        $player = DB::transaction(function () use ($game, $clientId, $side, $name, $max) {
            Game::query()->whereKey($game->id)->lockForUpdate()->first();

            $player = Player::query()
                ->where('game_id', $game->id)
                ->where('client_id', $clientId)
                ->first();

            if ($player && $player->side === $side) {
                if ($name !== null && $name !== $player->name) {
                    $player->update(['name' => $name]);
                }

                return $player;
            }

            if ($player && ! $game->isLobby()) {
                return $player;
            }

            $taken = Player::query()
                ->where('game_id', $game->id)
                ->where('side', $side)
                ->count();

            if ($taken >= $max) {
                throw new RuntimeException('side_full');
            }

            if ($player) {
                $player->update(['side' => $side, 'name' => $name ?? $player->name]);

                return $player;
            }

            return Player::query()->create([
                'game_id' => $game->id,
                'client_id' => $clientId,
                'side' => $side,
                'name' => $name ?: 'Ойыншы #'.($taken + 1),
            ]);
        });

        if ($player->wasRecentlyCreated || $player->wasChanged('side')) {
            $this->broadcast(new PlayersUpdated($room->channel(), [
                'gameId' => $game->id,
                'players' => $this->playerCounts($game),
                'lastJoined' => ['name' => $player->name, 'side' => $player->side],
            ]));
        }

        return [
            'player' => $this->playerPayload($player),
            'state' => $this->state($game),
        ];
    }

    public function leave(HostKey $room, string $clientId): void
    {
        $game = $this->current($room);

        if (! $game->isLobby()) {
            return;
        }

        $deleted = Player::query()
            ->where('game_id', $game->id)
            ->where('client_id', $clientId)
            ->delete();

        if ($deleted) {
            $this->broadcast(new PlayersUpdated($room->channel(), [
                'gameId' => $game->id,
                'players' => $this->playerCounts($game),
                'lastJoined' => null,
            ]));
        }
    }

    public function start(HostKey $room): Game
    {
        $game = $this->currentFresh($room);

        if ($game->isPlaying()) {
            return $game;
        }

        if ($game->isFinished()) {
            $game = $this->rematch($room, $game);
        }

        $startsAt = $this->nowMs() + config('zhilik.countdown_seconds') * 1000;

        $game->update([
            'status' => Game::PLAYING,
            'starts_at_ms' => $startsAt,
            'ends_at_ms' => $startsAt + config('zhilik.round_seconds') * 1000,
        ]);

        $room->forceFill(['last_used_at' => now()])->save();

        $this->broadcast(new GameStarted($room->channel(), $this->state($game)));

        return $game;
    }

    /**
     * Stores the player's cumulative tap count for the running round.
     *
     * @return array{accepted: bool, taps: int, reason?: string}
     */
    public function recordTaps(HostKey $room, string $clientId, int $taps): array
    {
        $game = $this->current($room);
        $now = $this->nowMs();

        if (! $game->isPlaying()) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_playing'];
        }

        if ($now < $game->starts_at_ms) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_started'];
        }

        if ($now > $game->ends_at_ms + config('zhilik.grace_seconds') * 1000) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'round_over'];
        }

        $player = Player::query()
            ->where('game_id', $game->id)
            ->where('client_id', $clientId)
            ->first();

        if (! $player) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_joined'];
        }

        $elapsedSeconds = (min($now, $game->ends_at_ms) - $game->starts_at_ms) / 1000;
        $cap = (int) ceil($elapsedSeconds * config('zhilik.max_taps_per_second')) + 5;
        $value = max(0, min($taps, $cap));

        if ($value > $player->taps) {
            Player::query()
                ->whereKey($player->id)
                ->where('taps', '<', $value)
                ->update(['taps' => $value, 'updated_at' => now()]);

            $this->maybeBroadcastScore($room, $game);
        }

        return ['accepted' => true, 'taps' => max($value, $player->taps)];
    }

    public function finish(HostKey $room, ?Game $game = null): Game
    {
        $game ??= $this->current($room);

        if (! $game->isPlaying() || $this->nowMs() < $this->finishAllowedAtMs($game)) {
            return $game;
        }

        $totals = $this->tapTotals($game);
        $winner = match (true) {
            $totals['left'] > $totals['right'] => 'left',
            $totals['right'] > $totals['left'] => 'right',
            default => 'draw',
        };

        $updated = Game::query()
            ->whereKey($game->id)
            ->where('status', Game::PLAYING)
            ->update([
                'status' => Game::FINISHED,
                'left_taps' => $totals['left'],
                'right_taps' => $totals['right'],
                'winner' => $winner,
                'updated_at' => now(),
            ]);

        $game->refresh();

        if ($updated) {
            $state = $this->state($game);
            $state['top'] = $this->topPlayers($game);
            $this->broadcast(new GameFinished($room->channel(), $state));
        }

        return $game;
    }

    /**
     * Starts a fresh lobby. Connected phones rejoin automatically on `game.reset`.
     */
    public function reset(HostKey $room): Game
    {
        $game = Game::query()->create(['host_key_id' => $room->id, 'status' => Game::LOBBY]);

        $this->broadcast(new GameReset($room->channel(), $this->state($game)));

        return $game;
    }

    /**
     * New lobby with the same players, so "Start" after a finished round replays instantly.
     */
    private function rematch(HostKey $room, Game $previous): Game
    {
        return DB::transaction(function () use ($room, $previous) {
            $game = Game::query()->create(['host_key_id' => $room->id, 'status' => Game::LOBBY]);

            $previous->players()->get(['client_id', 'side', 'name'])
                ->each(fn (Player $player) => Player::query()->create([
                    'game_id' => $game->id,
                    'client_id' => $player->client_id,
                    'side' => $player->side,
                    'name' => $player->name,
                ]));

            return $game;
        });
    }

    public function topPlayers(Game $game, int $limit = 3): array
    {
        $top = [];

        foreach (config('zhilik.sides') as $side) {
            $top[$side] = Player::query()
                ->where('game_id', $game->id)
                ->where('side', $side)
                ->where('taps', '>', 0)
                ->orderByDesc('taps')
                ->limit($limit)
                ->get(['name', 'taps'])
                ->map(fn (Player $player) => ['name' => $player->name, 'taps' => $player->taps])
                ->all();
        }

        return $top;
    }

    public function playerPayload(Player $player): array
    {
        return [
            'side' => $player->side,
            'name' => $player->name,
            'taps' => (int) $player->taps,
        ];
    }

    private function maybeBroadcastScore(HostKey $room, Game $game): void
    {
        $key = "zhilik:score-broadcast:{$game->id}";
        $now = $this->nowMs();
        $last = (int) Cache::get($key, 0);

        if ($now - $last < config('zhilik.score_broadcast_ms')) {
            return;
        }

        Cache::put($key, $now, 60);

        $this->broadcast(new ScoreUpdated($room->channel(), [
            'gameId' => $game->id,
            'scores' => $this->tapTotals($game),
            'serverNow' => $now,
        ]));
    }

    private function phase(Game $game): string
    {
        if ($game->isLobby()) {
            return 'lobby';
        }

        if ($game->isFinished()) {
            return 'finished';
        }

        $now = $this->nowMs();

        return match (true) {
            $now < $game->starts_at_ms => 'countdown',
            $now < $game->ends_at_ms => 'playing',
            default => 'judging',
        };
    }

    private function playerCounts(Game $game): array
    {
        $rows = Player::query()
            ->where('game_id', $game->id)
            ->selectRaw('side, COUNT(*) as total')
            ->groupBy('side')
            ->pluck('total', 'side');

        return [
            'left' => (int) ($rows['left'] ?? 0),
            'right' => (int) ($rows['right'] ?? 0),
        ];
    }

    private function tapTotals(Game $game): array
    {
        $rows = Player::query()
            ->where('game_id', $game->id)
            ->selectRaw('side, COALESCE(SUM(taps), 0) as total')
            ->groupBy('side')
            ->pluck('total', 'side');

        return [
            'left' => (int) ($rows['left'] ?? 0),
            'right' => (int) ($rows['right'] ?? 0),
        ];
    }

    private function finishAllowedAtMs(Game $game): int
    {
        return $game->ends_at_ms + config('zhilik.grace_seconds') * 1000;
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function broadcast(ZhilikEvent $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

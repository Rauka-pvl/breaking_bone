<?php

namespace App\Services;

use App\Events\GameFinished;
use App\Events\GameReset;
use App\Events\GameStarted;
use App\Events\PlayersUpdated;
use App\Events\RoundStopped;
use App\Events\ScoreUpdated;
use App\Events\ZhilikEvent;
use App\Models\Game;
use App\Models\HostKey;
use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
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

    public function state(Game $game, ?string $clientId = null): array
    {
        $state = [
            'gameId' => $game->id,
            'status' => $game->status,
            'phase' => $this->phase($game),
            'startsAt' => $game->starts_at_ms,
            'endsAt' => $game->ends_at_ms,
            'serverNow' => $this->nowMs(),
            'players' => $this->playerCount($game),
            'leaders' => $this->leaders($game),
            'winner' => $game->isFinished() ? $this->winnerPayload($game) : null,
            'maxPlayers' => config('zhilik.max_players'),
            'targetTaps' => config('zhilik.target_taps'),
            'maxSeconds' => config('zhilik.max_seconds'),
            'tapSyncMs' => config('zhilik.tap_sync_ms'),
            'graceMs' => config('zhilik.grace_seconds') * 1000,
        ];

        if ($clientId !== null) {
            $state['me'] = $this->mePayload($game, $clientId);
        }

        return $state;
    }

    /**
     * @throws RuntimeException when the game is full
     */
    public function join(HostKey $room, string $clientId, ?string $name): array
    {
        $game = $this->currentFresh($room);
        $max = (int) config('zhilik.max_players');

        $player = DB::transaction(function () use ($game, $clientId, $name, $max) {
            Game::query()->whereKey($game->id)->lockForUpdate()->first();

            $player = Player::query()
                ->where('game_id', $game->id)
                ->where('client_id', $clientId)
                ->first();

            if ($player) {
                if ($name !== null && $name !== $player->name) {
                    $player->update(['name' => $name]);
                }

                return $player;
            }

            $taken = Player::query()->where('game_id', $game->id)->count();

            if ($taken >= $max) {
                throw new RuntimeException('game_full');
            }

            return Player::query()->create([
                'game_id' => $game->id,
                'client_id' => $clientId,
                'name' => $name ?: 'Ойыншы #'.($taken + 1),
            ]);
        });

        if ($player->wasRecentlyCreated) {
            $this->broadcast(new PlayersUpdated($room->channel(), [
                'gameId' => $game->id,
                'players' => $this->playerCount($game),
                'lastJoined' => ['name' => $player->name],
            ]));
        }

        return [
            'player' => ['name' => $player->name, 'taps' => (int) $player->taps],
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
                'players' => $this->playerCount($game),
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
            'ends_at_ms' => $startsAt + config('zhilik.max_seconds') * 1000,
            'winner_player_id' => null,
        ]);

        $room->forceFill(['last_used_at' => now()])->save();

        $this->broadcast(new GameStarted($room->channel(), $this->state($game)));

        return $game;
    }

    /**
     * Stores the player's cumulative tap count. Reaching the target records the
     * finish time and stops the round for everyone.
     *
     * @param  int|null  $reachedAt  server-clock ms when the phone registered the target tap
     * @return array{accepted: bool, taps: int, reached?: bool, reason?: string}
     */
    public function recordTaps(HostKey $room, string $clientId, int $taps, ?int $reachedAt = null): array
    {
        $game = $this->current($room);
        $now = $this->nowMs();

        if (! $game->isPlaying()) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_playing'];
        }

        if ($now < $game->starts_at_ms) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_started'];
        }

        if ($now > $this->finishAllowedAtMs($game)) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'round_over'];
        }

        $player = Player::query()
            ->where('game_id', $game->id)
            ->where('client_id', $clientId)
            ->first();

        if (! $player) {
            return ['accepted' => false, 'taps' => 0, 'reason' => 'not_joined'];
        }

        $target = (int) config('zhilik.target_taps');
        $maxRate = (int) config('zhilik.max_taps_per_second');
        $elapsedSeconds = (min($now, $game->ends_at_ms) - $game->starts_at_ms) / 1000;
        $cap = (int) ceil($elapsedSeconds * $maxRate) + 5;
        $value = max(0, min($taps, $cap, $target));

        if ($value > $player->taps) {
            Player::query()
                ->whereKey($player->id)
                ->where('taps', '<', $value)
                ->update(['taps' => $value, 'updated_at' => now()]);
        }

        $reached = $player->reached_ms !== null;

        if ($value >= $target && ! $reached) {
            $earliest = $game->starts_at_ms + (int) ceil(max(0, $target - 5) / $maxRate * 1000);
            $reachedMs = max($earliest, min($reachedAt ?? $now, $now));

            Player::query()
                ->whereKey($player->id)
                ->whereNull('reached_ms')
                ->update(['reached_ms' => $reachedMs]);

            $reached = true;
            $this->stopRound($room, $game, $now);
        }

        if ($value > $player->taps) {
            $this->maybeBroadcastScore($room, $game);
        }

        return ['accepted' => true, 'taps' => max($value, (int) $player->taps), 'reached' => $reached];
    }

    public function finish(HostKey $room, ?Game $game = null): Game
    {
        $game ??= $this->current($room);

        if (! $game->isPlaying() || $this->nowMs() < $this->finishAllowedAtMs($game)) {
            return $game;
        }

        $winner = $this->ranked($game)->where('taps', '>', 0)->first();

        $updated = Game::query()
            ->whereKey($game->id)
            ->where('status', Game::PLAYING)
            ->update([
                'status' => Game::FINISHED,
                'winner_player_id' => $winner?->id,
                'updated_at' => now(),
            ]);

        $game->refresh();

        if ($updated) {
            $this->broadcast(new GameFinished($room->channel(), $this->state($game)));
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

            $previous->players()->get(['client_id', 'name'])
                ->each(fn (Player $player) => Player::query()->create([
                    'game_id' => $game->id,
                    'client_id' => $player->client_id,
                    'name' => $player->name,
                ]));

            return $game;
        });
    }

    /**
     * Moves the round end to now so phones stop tapping; finish() runs after the grace period.
     */
    private function stopRound(HostKey $room, Game $game, int $now): void
    {
        $stopped = Game::query()
            ->whereKey($game->id)
            ->where('status', Game::PLAYING)
            ->where('ends_at_ms', '>', $now)
            ->update(['ends_at_ms' => $now, 'updated_at' => now()]);

        if ($stopped) {
            $game->refresh();
            $this->broadcast(new RoundStopped($room->channel(), $this->state($game)));
        }
    }

    /**
     * Finishers first (earliest finish time wins), then by taps.
     */
    private function ranked(Game $game): Builder
    {
        return Player::query()
            ->where('game_id', $game->id)
            ->orderByRaw('reached_ms IS NULL')
            ->orderBy('reached_ms')
            ->orderByDesc('taps')
            ->orderBy('updated_at')
            ->orderBy('id');
    }

    private function leaders(Game $game): array
    {
        if ($game->isLobby()) {
            return [];
        }

        return $this->ranked($game)
            ->where('taps', '>', 0)
            ->limit(config('zhilik.leaderboard_size'))
            ->get()
            ->values()
            ->map(fn (Player $player, int $i) => $this->entry($game, $player, $i + 1))
            ->all();
    }

    private function winnerPayload(Game $game): ?array
    {
        $player = $game->winner_player_id ? Player::query()->find($game->winner_player_id) : null;

        return $player ? $this->entry($game, $player, 1) : null;
    }

    private function mePayload(Game $game, string $clientId): ?array
    {
        $player = Player::query()
            ->where('game_id', $game->id)
            ->where('client_id', $clientId)
            ->first();

        if (! $player) {
            return null;
        }

        $place = null;

        if ($game->isFinished() && $player->taps > 0) {
            $place = $this->ranked($game)->pluck('id')->search($player->id) + 1;
        }

        return $this->entry($game, $player, $place);
    }

    private function entry(Game $game, Player $player, ?int $place): array
    {
        return [
            'place' => $place,
            'name' => $player->name,
            'taps' => (int) $player->taps,
            'timeMs' => $player->reached_ms !== null ? $player->reached_ms - $game->starts_at_ms : null,
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
            'leaders' => $this->leaders($game),
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

    private function playerCount(Game $game): int
    {
        return Player::query()->where('game_id', $game->id)->count();
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

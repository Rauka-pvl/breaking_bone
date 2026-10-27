<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    public const LOBBY = 'lobby';

    public const PLAYING = 'playing';

    public const FINISHED = 'finished';

    protected $fillable = [
        'host_key_id',
        'status',
        'starts_at_ms',
        'ends_at_ms',
        'winner_player_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at_ms' => 'integer',
            'ends_at_ms' => 'integer',
            'winner_player_id' => 'integer',
        ];
    }

    public function hostKey(): BelongsTo
    {
        return $this->belongsTo(HostKey::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function isLobby(): bool
    {
        return $this->status === self::LOBBY;
    }

    public function isPlaying(): bool
    {
        return $this->status === self::PLAYING;
    }

    public function isFinished(): bool
    {
        return $this->status === self::FINISHED;
    }
}

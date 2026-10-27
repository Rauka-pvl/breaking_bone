<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'game_id',
        'client_id',
        'name',
        'taps',
        'reached_ms',
    ];

    protected function casts(): array
    {
        return [
            'taps' => 'integer',
            'reached_ms' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}

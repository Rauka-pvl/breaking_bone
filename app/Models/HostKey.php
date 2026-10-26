<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HostKey extends Model
{
    private const KEY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'name',
        'key',
        'code',
        'active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public static function findActiveByKey(string $key): ?self
    {
        $key = strtoupper(trim($key));

        if ($key === '') {
            return null;
        }

        return self::query()->where('key', $key)->where('active', true)->first();
    }

    public static function generateKey(): string
    {
        do {
            $key = self::randomChunk(4).'-'.self::randomChunk(4);
        } while (self::query()->where('key', $key)->exists());

        return $key;
    }

    public static function generateCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function channel(): string
    {
        return config('zhilik.channel').'.'.$this->code;
    }

    private static function randomChunk(int $length): string
    {
        $chunk = '';

        for ($i = 0; $i < $length; $i++) {
            $chunk .= self::KEY_ALPHABET[random_int(0, strlen(self::KEY_ALPHABET) - 1)];
        }

        return $chunk;
    }
}

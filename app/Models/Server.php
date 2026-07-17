<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    private const ONLINE_WINDOW_SECONDS = 75;

    protected $fillable = [
        'user_id',
        'name',
        'host',
        'port',
        'status',
        'minecraft_version',
        'players_online',
        'players_max',
        'last_seen_at',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'players_online' => 'integer',
            'players_max' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Server $server): void {
            $server->api_token ??= self::generateApiToken();
        });
    }

    public static function generateApiToken(): string
    {
        return hash('sha256', Str::random(64));
    }

    public static function findByApiToken(?string $token): ?self
    {
        $normalizedToken = trim((string) $token);

        if ($normalizedToken === '') {
            return null;
        }

        return self::query()
            ->where('api_token', $normalizedToken)
            ->first();
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThan(
                now()->subSeconds(self::ONLINE_WINDOW_SECONDS)
            );
    }

    public function currentPlayersOnline(): int
    {
        return $this->isOnline()
            ? (int) $this->players_online
            : 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(EchoActivity::class);
    }
}

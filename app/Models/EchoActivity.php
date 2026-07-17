<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'event_uuid',
        'type',
        'player_uuid',
        'player_name',
        'world',
        'x',
        'y',
        'z',
        'block_type',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'z' => 'float',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
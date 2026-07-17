<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCommand extends Model
{
    protected $fillable = [
        'server_id',
        'command_uuid',
        'type',
        'payload',
        'status',
        'message',
        'result',
        'claimed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'claimed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(
            Server::class
        );
    }
}

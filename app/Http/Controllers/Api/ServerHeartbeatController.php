<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerHeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Token da API não informado.',
            ], 401);
        }

        $server = Server::findByApiToken($token);

        if (! $server) {
            return response()->json([
                'message' => 'Token da API inválido.',
            ], 401);
        }

        $validated = $request->validate([
            'minecraft_version' => [
                'required',
                'string',
                'max:50',
            ],
            'players_online' => [
                'required',
                'integer',
                'min:0',
                'lte:players_max',
            ],
            'players_max' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $server->update([
            'status' => 'online',
            'minecraft_version' => $validated['minecraft_version'],
            'players_online' => $validated['players_online'],
            'players_max' => $validated['players_max'],
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Heartbeat recebido com sucesso.',
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'status' => $server->status,
                'minecraft_version' => $server->minecraft_version,
                'players_online' => $server->players_online,
                'players_max' => $server->players_max,
                'last_seen_at' => $server->last_seen_at,
            ],
        ]);
    }
}

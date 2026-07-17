<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EchoActivityController extends Controller
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
            'event_uuid' => [
                'required',
                'uuid',
            ],

            'type' => [
                'required',
                Rule::in([
                    'block_reconstruction',
                    'player_trace',
                ]),
            ],

            'player_uuid' => [
                'nullable',
                'uuid',
            ],

            'player_name' => [
                'nullable',
                'string',
                'max:16',
            ],

            'world' => [
                'required',
                'string',
                'max:100',
            ],

            'x' => [
                'required',
                'numeric',
            ],

            'y' => [
                'required',
                'numeric',
            ],

            'z' => [
                'required',
                'numeric',
            ],

            'block_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],

            'occurred_at' => [
                'required',
                'date',
            ],
        ]);

        $activity = $server->activities()->firstOrCreate(
            [
                'event_uuid' => $validated['event_uuid'],
            ],
            $validated
        );

        return response()->json([
            'message' => $activity->wasRecentlyCreated
                ? 'Atividade registrada com sucesso.'
                : 'Atividade já havia sido registrada.',

            'created' => $activity->wasRecentlyCreated,

            'activity' => [
                'id' => $activity->id,
                'event_uuid' => $activity->event_uuid,
                'type' => $activity->type,
                'occurred_at' => $activity->occurred_at,
            ],
        ], $activity->wasRecentlyCreated ? 201 : 200);
    }
}
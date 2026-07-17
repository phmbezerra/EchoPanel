<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerCommandCompleteController extends Controller
{
    public function __invoke(
        Request $request,
        ServerCommand $command
    ): JsonResponse {
        $server = $this->authenticateServer(
            $request
        );

        if ($server instanceof JsonResponse) {
            return $server;
        }

        if ($command->server_id !== $server->id) {
            return response()->json([
                'message' =>
                    'O comando não pertence a este servidor.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'completed',
                    'failed',
                ]),
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'result' => [
                'nullable',
                'array',
            ],
            'result.restored_blocks' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'result.failures' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $command->forceFill([
            'status' => $validated['status'],
            'message' =>
                $validated['message'] ?? null,
            'result' =>
                $validated['result'] ?? null,
            'completed_at' => now(),
        ])->save();

        return response()->json([
            'message' =>
                'Resultado do comando registrado.',
            'command' => [
                'id' => $command->id,
                'status' => $command->status,
                'message' => $command->message,
                'result' => $command->result,
                'completed_at' =>
                    $command->completed_at,
            ],
        ]);
    }

    private function authenticateServer(
        Request $request
    ): Server|JsonResponse {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' =>
                    'Token da API não informado.',
            ], 401);
        }

        $server = Server::query()
            ->where(
                'api_token',
                $token
            )
            ->first();

        if (! $server) {
            return response()->json([
                'message' =>
                    'Token da API inválido.',
            ], 401);
        }

        return $server;
    }
}

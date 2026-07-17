<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ServerCommandClaimController extends Controller
{
    public function __invoke(
        Request $request
    ): JsonResponse|Response {
        $server = $this->authenticateServer(
            $request
        );

        if ($server instanceof JsonResponse) {
            return $server;
        }

        $command = DB::transaction(
            function () use ($server): ?ServerCommand {
                $staleBefore = now()
                    ->subMinutes(2);

                $hasActiveCommand = ServerCommand::query()
                    ->where(
                        'server_id',
                        $server->id
                    )
                    ->where(
                        'status',
                        'processing'
                    )
                    ->where(
                        'claimed_at',
                        '>=',
                        $staleBefore
                    )
                    ->exists();

                if ($hasActiveCommand) {
                    return null;
                }

                $command = ServerCommand::query()
                    ->where(
                        'server_id',
                        $server->id
                    )
                    ->where(
                        function ($query) use (
                            $staleBefore
                        ): void {
                            $query->where(
                                'status',
                                'pending'
                            )->orWhere(
                                function ($query) use (
                                    $staleBefore
                                ): void {
                                    $query->where(
                                        'status',
                                        'processing'
                                    )->where(
                                        'claimed_at',
                                        '<',
                                        $staleBefore
                                    );
                                }
                            );
                        }
                    )
                    ->oldest()
                    ->lockForUpdate()
                    ->first();

                if (! $command) {
                    return null;
                }

                $command->forceFill([
                    'status' => 'processing',
                    'message' =>
                        'Comando recebido pelo servidor.',
                    'claimed_at' => now(),
                    'completed_at' => null,
                ])->save();

                return $command->fresh();
            }
        );

        if (! $command) {
            return response()->noContent();
        }

        return response()->json([
            'command' => [
                'id' => $command->id,
                'command_uuid' =>
                    $command->command_uuid,
                'type' => $command->type,
                'payload' => $command->payload,
                'status' => $command->status,
                'claimed_at' =>
                    $command->claimed_at,
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

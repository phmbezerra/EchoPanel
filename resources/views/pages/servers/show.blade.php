<?php

use App\Models\Server;
use App\Models\ServerCommand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Server $server;

    public bool $showApiToken = false;

    public string $tokenNotice = '';

    public string $restoreNotice = '';

    public function mount(Server $server): void
    {
        abort_unless(
            $server->user_id === auth()->id(),
            403
        );

        $this->server = $server;
    }

    public function refreshServer(): void
    {
        $this->server->refresh();

        unset(
            $this->activities,
            $this->commands
        );
    }

    public function toggleApiToken(): void
    {
        $this->showApiToken = ! $this->showApiToken;
    }

    #[Computed]
    public function activities(): Collection
    {
        return $this->server
            ->activities()
            ->latest('occurred_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function commands(): Collection
    {
        return ServerCommand::query()
            ->where(
                'server_id',
                $this->server->id
            )
            ->latest()
            ->limit(100)
            ->get();
    }

    public function requestRestore(
        int $activityId
    ): void {
        abort_unless(
            $this->server->user_id === auth()->id(),
            403
        );

        $activity = $this->server
            ->activities()
            ->findOrFail($activityId);

        $metadata = $activity->metadata ?? [];

        if (
            data_get(
                $metadata,
                'activity_kind'
            ) !== 'timeline_snapshot'
        ) {
            $this->restoreNotice =
                'Essa atividade não representa uma versão restaurável.';
            return;
        }

        $snapshotId = (int) data_get(
            $metadata,
            'snapshot_id',
            0
        );

        if ($snapshotId <= 0) {
            $this->restoreNotice =
                'A versão não possui um ID de memória válido.';
            return;
        }

        $activeCommand = ServerCommand::query()
            ->where(
                'server_id',
                $this->server->id
            )
            ->where(
                'type',
                'restore_snapshot'
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'processing',
                ]
            )
            ->get()
            ->first(
                fn (ServerCommand $command): bool =>
                    (int) data_get(
                        $command->payload,
                        'snapshot_id',
                        0
                    ) === $snapshotId
            );

        if ($activeCommand) {
            $this->restoreNotice =
                'Essa memória já possui uma restauração em andamento.';
            return;
        }

        ServerCommand::query()->create([
            'server_id' => $this->server->id,
            'command_uuid' => (string) Str::uuid(),
            'type' => 'restore_snapshot',
            'payload' => [
                'snapshot_id' => $snapshotId,
                'timeline_id' => data_get(
                    $metadata,
                    'timeline_id'
                ),
                'version_number' => data_get(
                    $metadata,
                    'version_number'
                ),
                'activity_id' => $activity->id,
            ],
            'status' => 'pending',
            'message' => $this->server->isOnline()
                ? 'Aguardando o EchoCraft receber o comando.'
                : 'Servidor offline. O comando será entregue quando ele voltar.',
        ]);

        $this->restoreNotice =
            'Solicitação de restauração enviada para a memória #'
            . $snapshotId
            . '.';

        unset($this->commands);
    }

    public function regenerateApiToken(): void
    {
        abort_unless(
            $this->server->user_id === auth()->id(),
            403
        );

        $this->server->forceFill([
            'api_token' => Server::generateApiToken(),
        ])->save();

        $this->server->refresh();
        $this->showApiToken = true;
        $this->tokenNotice =
            'Nova chave gerada. Atualize o config.yml do EchoCraft.';
    }

    public function deleteActivity(int $activityId): void
    {
        abort_unless(
            $this->server->user_id === auth()->id(),
            403
        );

        $activity = $this->server
            ->activities()
            ->findOrFail($activityId);

        $activity->delete();

        unset($this->activities);
    }

    public function clearActivities(): void
    {
        abort_unless(
            $this->server->user_id === auth()->id(),
            403
        );

        $this->server
            ->activities()
            ->delete();

        unset($this->activities);
    }
};
?>

<div
    wire:poll.5s="refreshServer"
    class="flex h-full w-full flex-1 flex-col gap-6"
>
    <div>
        <a
            href="{{ route('servers.index') }}"
            wire:navigate
            class="text-sm font-medium text-blue-500 hover:text-blue-400"
        >
            ← Voltar para servidores
        </a>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    EchoCraft
                </p>

                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $server->name }}
                </h1>

                <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $server->host }}:{{ $server->port }}
                </p>
            </div>

            <span
                @class([
                    'w-fit rounded-full px-3 py-1 text-sm font-semibold',
                    'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' => $server->isOnline(),
                    'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! $server->isOnline(),
                ])
            >
                {{ $server->isOnline() ? 'Online' : 'Offline' }}
            </span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Jogadores
            </p>

            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $server->currentPlayersOnline() }}
                /
                {{ $server->players_max }}
            </p>
        </article>

        <article class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Versão
            </p>

            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $server->minecraft_version ?? 'Desconhecida' }}
            </p>
        </article>

        <article class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Última conexão
            </p>

            <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">
                {{ $server->last_seen_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? 'Nunca' }}
            </p>
        </article>

        <article class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Cadastrado em
            </p>

            <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">
                {{ $server->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
            </p>
        </article>
    </div>

    <section
        x-data="{
            copied: false,

            copyToken() {
                const token = this.$refs.token.textContent.trim();

                const fallbackCopy = () => {
                    const textarea = document.createElement('textarea');

                    textarea.value = token;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';

                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand('copy');
                    textarea.remove();
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard
                        .writeText(token)
                        .catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }

                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            }
        }"
        class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Chave da API
                </h2>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Essa chave é usada pelo EchoCraft para se comunicar com o painel.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="toggleApiToken"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    {{ $showApiToken ? 'Ocultar chave' : 'Mostrar chave' }}
                </button>

                <button
                    type="button"
                    wire:click="regenerateApiToken"
                    wire:confirm="A chave atual deixará de funcionar. Deseja realmente gerar uma nova?"
                    wire:loading.attr="disabled"
                    wire:target="regenerateApiToken"
                    class="rounded-lg border border-red-500/50 px-4 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="regenerateApiToken">
                        Regenerar chave
                    </span>

                    <span wire:loading wire:target="regenerateApiToken">
                        Gerando...
                    </span>
                </button>
            </div>
        </div>

        @if ($tokenNotice)
            <div class="mt-5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-500">
                {{ $tokenNotice }}
            </div>
        @endif

        @if ($showApiToken)
            <div class="mt-5 flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950 sm:flex-row sm:items-center sm:justify-between">
                <code
                    x-ref="token"
                    class="break-all text-sm text-zinc-700 dark:text-zinc-300"
                >
                    {{ $server->api_token }}
                </code>

                <button
                    type="button"
                    x-on:click="copyToken"
                    class="shrink-0 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    <span x-text="copied ? 'Copiado!' : 'Copiar'"></span>
                </button>
            </div>
        @else
            <div class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50 p-4 font-mono text-sm tracking-widest text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                ••••••••••••••••••••••••••••••••••••••••••••••••
            </div>
        @endif

        <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
            Não compartilhe essa chave publicamente.
        </p>
    </section>

    @php
        $allActivities = $this->activities;

        $commandsBySnapshot = $this->commands
            ->groupBy(
                fn (ServerCommand $command) =>
                    (string) data_get(
                        $command->payload,
                        'snapshot_id',
                        'unknown'
                    )
            )
            ->map(
                fn (Collection $commands) =>
                    $commands
                        ->sortByDesc('id')
                        ->first()
            );

        $timelineActivities = $allActivities->filter(
            fn ($activity) =>
                data_get($activity->metadata, 'activity_kind')
                    === 'timeline_snapshot'
        );

        $recentActivities = $allActivities->reject(
            fn ($activity) =>
                data_get($activity->metadata, 'activity_kind')
                    === 'timeline_snapshot'
        );

        $timelines = $timelineActivities
            ->groupBy(
                fn ($activity) =>
                    (string) data_get(
                        $activity->metadata,
                        'timeline_id',
                        'unknown'
                    )
            )
            ->sortByDesc(
                fn ($events) =>
                    $events->max(
                        fn ($event) =>
                            $event->occurred_at?->timestamp ?? 0
                    )
            );

        $formatCause = static function ($cause): ?string {
            if ($cause === null || $cause === '') {
                return null;
            }

            if (str_starts_with((string) $cause, 'RESTORE_FROM_')) {
                return 'Restauração da memória #'
                    . str_replace('RESTORE_FROM_', '', (string) $cause);
            }

            return match ($cause) {
                'TNT', 'PRIMED_TNT' => 'Explosão de TNT',
                'CREEPER' => 'Explosão de Creeper',
                'BLOCK_EXPLOSION' => 'Explosão causada por bloco',
                'Bloco quebrado' => 'Bloco quebrado',
                default => ucfirst(
                    strtolower(
                        str_replace('_', ' ', (string) $cause)
                    )
                ),
            };
        };

        $formatMemoryType = static fn ($type): string => match ($type) {
            'EXPLOSION' => 'Explosão registrada',
            'MANUAL_BREAK' => 'Alteração manual',
            'RESTORE' => 'Restauração temporal',
            default => 'Versão temporal',
        };
    @endphp

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Linhas do tempo
                </h2>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Evolução visual das construções registradas pelo EchoCraft.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-purple-500/10 px-3 py-1 text-xs font-semibold text-purple-500">
                    {{ $timelines->count() }}
                    {{ $timelines->count() === 1 ? 'linha' : 'linhas' }}
                </span>

                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $timelineActivities->count() }}
                    {{ $timelineActivities->count() === 1 ? 'versão' : 'versões' }}
                </span>
            </div>
        </div>

        @if ($restoreNotice)
            <div class="mt-5 rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-3 text-sm text-blue-500">
                {{ $restoreNotice }}
            </div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($timelines as $timelineKey => $timelineEvents)
                @php
                    $orderedVersions = $timelineEvents
                        ->sortBy(
                            fn ($event) =>
                                (int) data_get(
                                    $event->metadata,
                                    'version_number',
                                    0
                                )
                        )
                        ->values();

                    $latestVersion = $orderedVersions->last();
                    $latestMetadata = $latestVersion?->metadata ?? [];

                    $timelineId = data_get(
                        $latestMetadata,
                        'timeline_id',
                        $timelineKey
                    );

                    $timelineName = data_get(
                        $latestMetadata,
                        'timeline_name'
                    );

                    $timelineTitle = $timelineName
                        ?: 'Linha temporal #' . $timelineId;
                @endphp

                <article
                    x-data="{ open: true }"
                    wire:key="timeline-{{ $timelineKey }}"
                    class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50/60 dark:border-zinc-700 dark:bg-zinc-950/40"
                >
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-zinc-100/70 dark:hover:bg-zinc-800/50"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate font-semibold text-zinc-900 dark:text-white">
                                    {{ $timelineTitle }}
                                </h3>

                                <span class="rounded-full bg-purple-500/10 px-2.5 py-1 text-xs font-semibold text-purple-500">
                                    {{ $orderedVersions->count() }}
                                    {{ $orderedVersions->count() === 1 ? 'versão' : 'versões' }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-zinc-500">
                                Mundo:
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $latestVersion?->world ?? 'Desconhecido' }}
                                </span>

                                <span class="mx-2 text-zinc-300 dark:text-zinc-700">•</span>

                                Última alteração:
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $latestVersion?->occurred_at
                                        ?->timezone('America/Sao_Paulo')
                                        ->format('d/m/Y H:i') }}
                                </span>
                            </p>
                        </div>

                        <span
                            class="shrink-0 text-lg font-bold text-zinc-400"
                            x-text="open ? '−' : '+'"
                        ></span>
                    </button>

                    <div
                        x-show="open"
                        class="border-t border-zinc-200 px-5 py-5 dark:border-zinc-700"
                    >
                        <div class="relative space-y-0">
                            <div class="absolute bottom-4 left-[15px] top-4 w-px bg-zinc-200 dark:bg-zinc-700"></div>

                            @foreach ($orderedVersions as $versionEvent)
                                @php
                                    $versionMetadata =
                                        $versionEvent->metadata ?? [];

                                    $memoryType = data_get(
                                        $versionMetadata,
                                        'memory_type',
                                        'UNKNOWN'
                                    );

                                    $versionNumber = (int) data_get(
                                        $versionMetadata,
                                        'version_number',
                                        0
                                    );

                                    $snapshotId = data_get(
                                        $versionMetadata,
                                        'snapshot_id'
                                    );

                                    $snapshotName = data_get(
                                        $versionMetadata,
                                        'snapshot_name'
                                    );

                                    $snapshotBlocks = (int) data_get(
                                        $versionMetadata,
                                        'snapshot_blocks',
                                        0
                                    );

                                    $traceCount = (int) data_get(
                                        $versionMetadata,
                                        'trace_count',
                                        0
                                    );

                                    $actionCount = (int) data_get(
                                        $versionMetadata,
                                        'action_count',
                                        0
                                    );

                                    $causeLabel = $formatCause(
                                        data_get(
                                            $versionMetadata,
                                            'cause'
                                        )
                                    );

                                    $typeLabel = $formatMemoryType(
                                        $memoryType
                                    );

                                    $dotClasses = match ($memoryType) {
                                        'EXPLOSION' =>
                                            'border-orange-500 bg-orange-500/20 text-orange-500',
                                        'MANUAL_BREAK' =>
                                            'border-cyan-500 bg-cyan-500/20 text-cyan-500',
                                        'RESTORE' =>
                                            'border-purple-500 bg-purple-500/20 text-purple-500',
                                        default =>
                                            'border-zinc-400 bg-zinc-400/20 text-zinc-400',
                                    };

                                    $cardClasses = match ($memoryType) {
                                        'EXPLOSION' =>
                                            'border-orange-500/20 bg-orange-500/5',
                                        'MANUAL_BREAK' =>
                                            'border-cyan-500/20 bg-cyan-500/5',
                                        'RESTORE' =>
                                            'border-purple-500/20 bg-purple-500/5',
                                        default =>
                                            'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900',
                                    };

                                    $restoreCommand = $snapshotId !== null
                                        ? $commandsBySnapshot->get(
                                            (string) $snapshotId
                                        )
                                        : null;

                                    $commandStatus =
                                        $restoreCommand?->status;

                                    $commandStatusLabel = match (
                                        $commandStatus
                                    ) {
                                        'pending' => 'Aguardando servidor',
                                        'processing' => 'Restaurando',
                                        'completed' => 'Concluído',
                                        'failed' => 'Falhou',
                                        default => null,
                                    };

                                    $commandStatusClasses = match (
                                        $commandStatus
                                    ) {
                                        'pending' =>
                                            'bg-amber-500/10 text-amber-500',
                                        'processing' =>
                                            'bg-blue-500/10 text-blue-500',
                                        'completed' =>
                                            'bg-green-500/10 text-green-500',
                                        'failed' =>
                                            'bg-red-500/10 text-red-500',
                                        default =>
                                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                                    };

                                    $restoreButtonLabel = match (
                                        $commandStatus
                                    ) {
                                        'pending' => 'Aguardando servidor...',
                                        'processing' => 'Restaurando...',
                                        'completed' => 'Restaurar novamente',
                                        'failed' => 'Tentar novamente',
                                        default => 'Restaurar esta versão',
                                    };

                                    $restoreButtonDisabled = in_array(
                                        $commandStatus,
                                        [
                                            'pending',
                                            'processing',
                                        ],
                                        true
                                    );
                                @endphp

                                <div
                                    wire:key="timeline-version-{{ $versionEvent->id }}"
                                    class="relative flex gap-4 pb-5 last:pb-0"
                                >
                                    <div class="relative z-10 mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 {{ $dotClasses }}">
                                        {{ $versionNumber }}
                                    </div>

                                    <div class="min-w-0 flex-1 rounded-xl border p-4 {{ $cardClasses }}">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="font-semibold text-zinc-900 dark:text-white">
                                                        Versão {{ $versionNumber }}
                                                        — {{ $typeLabel }}
                                                    </h4>

                                                    @if ($snapshotId !== null)
                                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                            Memória #{{ $snapshotId }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($snapshotName)
                                                    <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                                        {{ $snapshotName }}
                                                    </p>
                                                @endif

                                                @if ($causeLabel)
                                                    <p class="mt-1 text-sm text-zinc-500">
                                                        {{ $causeLabel }}
                                                    </p>
                                                @endif

                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="rounded-lg bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-500">
                                                        {{ $snapshotBlocks }}
                                                        {{ $snapshotBlocks === 1 ? 'bloco' : 'blocos' }}
                                                    </span>

                                                    <span class="rounded-lg bg-purple-500/10 px-2.5 py-1 text-xs font-semibold text-purple-500">
                                                        {{ $traceCount }}
                                                        {{ $traceCount === 1 ? 'rastro' : 'rastros' }}
                                                    </span>

                                                    <span class="rounded-lg bg-cyan-500/10 px-2.5 py-1 text-xs font-semibold text-cyan-500">
                                                        {{ $actionCount }}
                                                        {{ $actionCount === 1 ? 'ação' : 'ações' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                                @if ($snapshotId !== null)
                                                    <button
                                                        type="button"
                                                        wire:click="requestRestore({{ $versionEvent->id }})"
                                                        wire:confirm="Deseja restaurar a memória #{{ $snapshotId }}? Os blocos atuais serão reescritos."
                                                        wire:loading.attr="disabled"
                                                        wire:target="requestRestore({{ $versionEvent->id }})"
                                                        @disabled($restoreButtonDisabled)
                                                        class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <span
                                                            wire:loading.remove
                                                            wire:target="requestRestore({{ $versionEvent->id }})"
                                                        >
                                                            {{ $restoreButtonLabel }}
                                                        </span>

                                                        <span
                                                            wire:loading
                                                            wire:target="requestRestore({{ $versionEvent->id }})"
                                                        >
                                                            Enviando...
                                                        </span>
                                                    </button>
                                                @endif

                                                @if ($commandStatusLabel)
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $commandStatusClasses }}">
                                                        {{ $commandStatusLabel }}
                                                    </span>
                                                @endif

                                                @if ($restoreCommand?->message)
                                                    <p class="max-w-xs text-left text-xs text-zinc-500 sm:text-right">
                                                        {{ $restoreCommand->message }}
                                                    </p>
                                                @endif

                                                @if ($commandStatus === 'completed')
                                                    @php
                                                        $restoredBlocks = (int) data_get(
                                                            $restoreCommand->result,
                                                            'restored_blocks',
                                                            0
                                                        );

                                                        $restoreFailures = (int) data_get(
                                                            $restoreCommand->result,
                                                            'failures',
                                                            0
                                                        );
                                                    @endphp

                                                    <p class="text-xs font-medium text-green-500">
                                                        {{ $restoredBlocks }}
                                                        {{ $restoredBlocks === 1 ? 'bloco restaurado' : 'blocos restaurados' }}

                                                        @if ($restoreFailures > 0)
                                                            · {{ $restoreFailures }}
                                                            {{ $restoreFailures === 1 ? 'falha' : 'falhas' }}
                                                        @endif
                                                    </p>
                                                @endif

                                                <button
                                                    type="button"
                                                    wire:click="deleteActivity({{ $versionEvent->id }})"
                                                    wire:confirm="Deseja excluir esta versão da linha do tempo?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteActivity({{ $versionEvent->id }})"
                                                    class="rounded-lg border border-red-500/40 px-2.5 py-1 text-xs font-semibold text-red-500 transition hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    Excluir
                                                </button>

                                                <p class="font-mono text-xs text-zinc-500">
                                                    X: {{ number_format($versionEvent->x, 1) }}
                                                    Y: {{ number_format($versionEvent->y, 1) }}
                                                    Z: {{ number_format($versionEvent->z, 1) }}
                                                </p>

                                                <p class="text-xs text-zinc-400">
                                                    {{ $versionEvent->occurred_at
                                                        ->timezone('America/Sao_Paulo')
                                                        ->format('d/m/Y H:i:s') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                    <p class="font-semibold text-zinc-900 dark:text-white">
                        Nenhuma linha do tempo recebida ainda
                    </p>

                    <p class="mt-1 text-sm text-zinc-500">
                        Crie ou restaure uma nova memória para registrar a primeira versão visual.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Atividades recentes
                </h2>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Reconstruções e rastros recebidos do EchoCraft.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $recentActivities->count() }} evento(s)
                </span>

                @if ($allActivities->isNotEmpty())
                    <button
                        type="button"
                        wire:click="clearActivities"
                        wire:confirm="Deseja excluir todas as atividades e linhas do tempo deste servidor?"
                        wire:loading.attr="disabled"
                        wire:target="clearActivities"
                        class="rounded-lg border border-red-500/40 px-3 py-1 text-xs font-semibold text-red-500 transition hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="clearActivities">
                            Limpar tudo
                        </span>

                        <span wire:loading wire:target="clearActivities">
                            Limpando...
                        </span>
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($recentActivities as $activity)
                @php
                    $metadata = $activity->metadata ?? [];
                    $blocksCount = $metadata['blocks_count'] ?? null;
                    $tracePoints = $metadata['trace_points'] ?? null;
                    $actionsCount = $metadata['actions_count'] ?? null;
                    $snapshotId = $metadata['snapshot_id'] ?? null;
                    $timelineId = $metadata['timeline_id'] ?? null;
                    $versionNumber = $metadata['version_number'] ?? null;
                    $causeLabel = $formatCause(
                        $metadata['cause'] ?? null
                    );
                @endphp

                <article
                    wire:key="activity-{{ $activity->id }}"
                    class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                @class([
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg',
                                    'bg-blue-500/10 text-blue-500' => $activity->type === 'block_reconstruction',
                                    'bg-purple-500/10 text-purple-500' => $activity->type === 'player_trace',
                                ])
                            >
                                {{ $activity->type === 'block_reconstruction' ? '◆' : '◉' }}
                            </div>

                            <div class="min-w-0">
                                <h3 class="font-semibold text-zinc-900 dark:text-white">
                                    @if ($activity->type === 'block_reconstruction')
                                        @if ($blocksCount !== null)
                                            {{ (int) $blocksCount === 1
                                                ? '1 bloco reconstruído'
                                                : (int) $blocksCount . ' blocos reconstruídos' }}
                                        @else
                                            Bloco reconstruído
                                        @endif
                                    @else
                                        Rastro temporal detectado
                                    @endif
                                </h3>

                                <p class="mt-1 text-sm text-zinc-500">
                                    Mundo:
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $activity->world }}
                                    </span>
                                </p>

                                @if ($activity->block_type)
                                    <p class="mt-1 text-sm text-zinc-500">
                                        Bloco:
                                        <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $activity->block_type }}
                                        </span>
                                    </p>
                                @endif

                                @if ($activity->player_name)
                                    <p class="mt-1 text-sm text-zinc-500">
                                        Jogador:
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $activity->player_name }}
                                        </span>
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($blocksCount !== null)
                                        <span class="rounded-lg bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-500">
                                            {{ $blocksCount }}
                                            {{ (int) $blocksCount === 1 ? 'bloco' : 'blocos' }}
                                        </span>
                                    @endif

                                    @if ($tracePoints !== null)
                                        <span class="rounded-lg bg-purple-500/10 px-2.5 py-1 text-xs font-semibold text-purple-500">
                                            {{ $tracePoints }}
                                            {{ (int) $tracePoints === 1 ? 'ponto' : 'pontos' }}
                                        </span>
                                    @endif

                                    @if ($actionsCount !== null)
                                        <span class="rounded-lg bg-cyan-500/10 px-2.5 py-1 text-xs font-semibold text-cyan-500">
                                            {{ $actionsCount }}
                                            {{ (int) $actionsCount === 1 ? 'ação' : 'ações' }}
                                        </span>
                                    @endif

                                    @if ($causeLabel !== null)
                                        <span class="rounded-lg bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-500">
                                            {{ $causeLabel }}
                                        </span>
                                    @endif

                                    @if ($snapshotId !== null)
                                        <span class="rounded-lg bg-purple-500/10 px-2.5 py-1 text-xs font-semibold text-purple-500">
                                            Memória #{{ $snapshotId }}
                                        </span>
                                    @endif

                                    @if ($timelineId !== null)
                                        <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            Linha #{{ $timelineId }}
                                            @if ($versionNumber !== null)
                                                · V{{ $versionNumber }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-start gap-3 sm:items-end">
                            <button
                                type="button"
                                wire:click="deleteActivity({{ $activity->id }})"
                                wire:confirm="Deseja excluir esta atividade?"
                                wire:loading.attr="disabled"
                                wire:target="deleteActivity({{ $activity->id }})"
                                class="rounded-lg border border-red-500/40 px-2.5 py-1 text-xs font-semibold text-red-500 transition hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="deleteActivity({{ $activity->id }})"
                                >
                                    Excluir
                                </span>

                                <span
                                    wire:loading
                                    wire:target="deleteActivity({{ $activity->id }})"
                                >
                                    Excluindo...
                                </span>
                            </button>

                            <div class="text-left sm:text-right">
                                <p class="font-mono text-sm text-zinc-600 dark:text-zinc-300">
                                    X: {{ number_format($activity->x, 1) }}
                                    Y: {{ number_format($activity->y, 1) }}
                                    Z: {{ number_format($activity->z, 1) }}
                                </p>

                                <p class="mt-2 text-xs text-zinc-400">
                                    {{ $activity->occurred_at
                                        ->timezone('America/Sao_Paulo')
                                        ->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                    <p class="font-semibold text-zinc-900 dark:text-white">
                        Nenhuma atividade recebida ainda
                    </p>

                    <p class="mt-1 text-sm text-zinc-500">
                        As reconstruções e os rastros aparecerão aqui.
                    </p>
                </div>
            @endforelse
        </div>
    </section>
</div>

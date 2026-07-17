<?php

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function servers(): Collection
    {
        return Server::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function totalServers(): int
    {
        return $this->servers->count();
    }

    #[Computed]
    public function onlineServers(): int
    {
        return $this->servers
            ->filter(
                fn (Server $server): bool => $server->isOnline()
            )
            ->count();
    }

    #[Computed]
    public function playersOnline(): int
    {
        return $this->servers
            ->sum(
                fn (Server $server): int =>
                    $server->currentPlayersOnline()
            );
    }

    #[Computed]
    public function latestServer(): ?Server
    {
        return $this->servers->first(
            fn (Server $server): bool =>
                $server->last_seen_at !== null
        );
    }
};
?>

<div
    wire:poll.15s
    class="flex h-full w-full flex-1 flex-col gap-6"
>
    <header>
        <p class="text-sm font-medium text-blue-500">
            EchoPanel
        </p>

        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Dashboard
        </h1>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Acompanhe seus servidores e a integração com o EchoCraft.
        </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Servidores cadastrados
            </p>

            <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ $this->totalServers }}
            </p>
        </article>

        <article
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Servidores online
            </p>

            <div class="mt-3 flex items-center gap-3">
                <p class="text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->onlineServers }}
                </p>

                @if ($this->onlineServers > 0)
                    <span
                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300"
                    >
                        Ativo
                    </span>
                @else
                    <span
                        class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                    >
                        Nenhum
                    </span>
                @endif
            </div>
        </article>

        <article
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Jogadores conectados
            </p>

            <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ $this->playersOnline }}
            </p>
        </article>

        <article
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Última comunicação
            </p>

            @if ($this->latestServer)
                <p class="mt-3 text-lg font-bold text-zinc-900 dark:text-white">
                    {{ $this->latestServer->last_seen_at
                        ->timezone('America/Sao_Paulo')
                        ->format('d/m/Y H:i') }}
                </p>

                <p class="mt-1 truncate text-xs text-zinc-500">
                    {{ $this->latestServer->name }}
                </p>
            @else
                <p class="mt-3 text-lg font-bold text-zinc-900 dark:text-white">
                    Nunca
                </p>
            @endif
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Seus servidores
                    </h2>

                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Status atualizado automaticamente.
                    </p>
                </div>

                <a
                    href="{{ route('servers.index') }}"
                    wire:navigate
                    class="text-sm font-semibold text-blue-500 hover:text-blue-400"
                >
                    Gerenciar servidores →
                </a>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @forelse ($this->servers as $server)
                    <a
                        href="{{ route('servers.show', $server) }}"
                        wire:navigate
                        wire:key="dashboard-server-{{ $server->id }}"
                        class="rounded-xl border border-zinc-200 p-4 transition hover:border-blue-500 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3
                                    class="truncate font-semibold text-zinc-900 dark:text-white"
                                >
                                    {{ $server->name }}
                                </h3>

                                <p
                                    class="mt-1 truncate font-mono text-xs text-zinc-500"
                                >
                                    {{ $server->host }}:{{ $server->port }}
                                </p>
                            </div>

                            <span
                                @class([
                                    'shrink-0 rounded-full px-2 py-1 text-xs font-semibold',
                                    'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' => $server->isOnline(),
                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => ! $server->isOnline(),
                                ])
                            >
                                {{ $server->isOnline() ? 'Online' : 'Offline' }}
                            </span>
                        </div>

                        <div
                            class="mt-5 flex items-end justify-between gap-3"
                        >
                            <div>
                                <p class="text-xs text-zinc-500">
                                    Jogadores
                                </p>

                                <p
                                    class="mt-1 text-lg font-bold text-zinc-900 dark:text-white"
                                >
                                    {{ $server->currentPlayersOnline() }}
                                    /
                                    {{ $server->players_max }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-zinc-500">
                                    Versão
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                                >
                                    {{ $server->minecraft_version ?? 'Desconhecida' }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div
                        class="col-span-full rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700"
                    >
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            Nenhum servidor cadastrado
                        </p>

                        <p class="mt-1 text-sm text-zinc-500">
                            Adicione seu primeiro servidor para começar.
                        </p>

                        <a
                            href="{{ route('servers.index') }}"
                            wire:navigate
                            class="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Adicionar servidor
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        <aside
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Integração EchoCraft
            </h2>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Situação atual da comunicação entre Java e Laravel.
            </p>

            <div class="mt-6 space-y-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm text-zinc-500">
                        API Laravel
                    </span>

                    <span
                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300"
                    >
                        Funcionando
                    </span>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm text-zinc-500">
                        Heartbeat
                    </span>

                    @if ($this->onlineServers > 0)
                        <span
                            class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300"
                        >
                            Recebendo
                        </span>
                    @else
                        <span
                            class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                        >
                            Aguardando
                        </span>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm text-zinc-500">
                        Atualização
                    </span>

                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        A cada 15s
                    </span>
                </div>
            </div>

            <div
                class="mt-6 rounded-xl border border-blue-500/30 bg-blue-500/10 p-4"
            >
                <p class="text-sm font-semibold text-blue-500">
                    Próxima etapa
                </p>

                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                    Receber reconstruções de blocos e rastros diretamente do plugin.
                </p>
            </div>
        </aside>
    </div>
</div>
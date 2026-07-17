<?php

use App\Models\Server;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $host = '';

    public $port = 25565;

    #[Computed]
    public function servers()
    {
        return Server::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
        ], [
            'name.required' => 'Digite um nome para o servidor.',
            'host.required' => 'Digite o endereço do servidor.',
            'port.required' => 'Digite a porta do servidor.',
            'port.integer' => 'A porta precisa ser um número.',
            'port.between' => 'A porta deve estar entre 1 e 65535.',
        ]);

        Server::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'host' => $validated['host'],
            'port' => (int) $validated['port'],
        ]);

        $this->reset('name', 'host');

        $this->port = 25565;

        unset($this->servers);

        session()->flash(
            'success',
            'Servidor cadastrado com sucesso!'
        );
    }
};
?>

<div wire:poll.15s class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
            EchoCraft
        </p>

        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Servidores Minecraft
        </h1>

        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Cadastre os servidores que serão conectados ao EchoPanel.
        </p>
    </div>

    @if (session('success'))
        <div
            class="rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
        >
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Adicionar servidor
            </h2>

            <form wire:submit="save" class="mt-5 space-y-5">
                <div>
                    <label
                        for="name"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Nome
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        placeholder="Servidor principal"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="host"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Endereço ou IP
                    </label>

                    <input
                        id="host"
                        type="text"
                        wire:model="host"
                        placeholder="play.meuservidor.com"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >

                    @error('host')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="port"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Porta
                    </label>

                    <input
                        id="port"
                        type="number"
                        wire:model="port"
                        min="1"
                        max="65535"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >

                    @error('port')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">
                        Cadastrar servidor
                    </span>

                    <span wire:loading wire:target="save">
                        Cadastrando...
                    </span>
                </button>
            </form>
        </section>

        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Seus servidores
                    </h2>

                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $this->servers->count() }}
                        servidor(es) cadastrado(s)
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->servers as $server)
                    <article
                        wire:key="server-{{ $server->id }}"
                        class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-zinc-900 dark:text-white">
                                    {{ $server->name }}
                                </h3>

                                <span
    @class([
        'rounded-full px-2 py-1 text-xs font-medium',
        'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' => $server->isOnline(),
        'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => ! $server->isOnline(),
    ])
>
    {{ $server->isOnline() ? 'Online' : 'Offline' }}
</span>
                            </div>

                            <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $server->host }}:{{ $server->port }}
                            </p>
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $server->currentPlayersOnline() }}
                                /
                                {{ $server->players_max }}
                                jogadores
                            </p>

                            <p class="mt-1 text-xs text-zinc-400">
                                Criado em
                                {{ $server->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                            </p>
                            <a
    href="{{ route('servers.show', $server) }}"
    wire:navigate
    class="mt-2 inline-block text-sm font-semibold text-blue-500 hover:text-blue-400"
>
    Ver detalhes →
</a>
                        </div>
                    </article>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700"
                    >
                        <p class="font-medium text-zinc-700 dark:text-zinc-300">
                            Nenhum servidor cadastrado
                        </p>

                        <p class="mt-1 text-sm text-zinc-500">
                            Preencha o formulário para adicionar o primeiro.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
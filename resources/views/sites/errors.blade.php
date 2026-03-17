<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Error Log</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link active">Errors</a>
        </nav>
    </div>

    @if($errors->isEmpty())
        <div class="wb-card p-12 text-center">
            <p class="text-white/80 text-sm">No errors reported.</p>
        </div>
    @else
        <div class="wb-card">
            <div class="px-6 py-4 border-b border-white/[0.04]">
                <span class="wb-label">{{ $errors->total() }} Error(s)</span>
            </div>
            @foreach($errors as $error)
                <div class="px-6 py-4 border-b border-white/[0.04]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @switch($error->severity->value ?? $error->severity)
                                @case('critical') <span class="wb-badge-red">Critical</span> @break
                                @case('error') <span class="wb-badge-red">Error</span> @break
                                @case('warning') <span class="wb-badge-orange">Warning</span> @break
                                @default <span class="wb-badge-gray">{{ ucfirst($error->severity->value ?? $error->severity) }}</span>
                            @endswitch
                            <span class="text-xs text-white/80 font-mono">{{ $error->source }}</span>
                        </div>
                        <span class="text-xs text-white/80 font-mono">{{ $error->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2 text-sm text-white font-mono">{{ $error->message }}</p>
                    @if($error->context)
                        <details class="mt-2">
                            <summary class="text-xs text-white/80 cursor-pointer hover:text-wb-teal">Context</summary>
                            <pre class="mt-1 text-xs text-white/80 font-mono bg-white/[0.02] p-3 rounded-md overflow-x-auto">{{ json_encode($error->context, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $errors->links() }}
        </div>
    @endif
</x-app-layout>

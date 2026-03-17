<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Available Updates</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link active">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link">Errors</a>
        </nav>
    </div>

    @if($pendingUpdates->isEmpty())
        <div class="wb-card p-12 text-center">
            <p class="text-white/80 text-sm">Everything is up to date.</p>
        </div>
    @else
        <form method="POST" action="{{ route('updates.trigger', $site) }}">
            @csrf
            <div class="wb-card">
                <div class="px-6 py-4 border-b border-white/[0.04] flex justify-between items-center">
                    <span class="wb-label">{{ $pendingUpdates->count() }} Available Updates</span>
                    <button type="submit" class="wb-btn-primary" onclick="return confirm('Trigger updates for selected items?')">
                        Update Selected
                    </button>
                </div>
                @foreach($pendingUpdates->groupBy(fn($item) => $item->type->label()) as $typeLabel => $items)
                    <div class="px-6 py-2.5 bg-white/[0.02] border-b border-white/[0.04]">
                        <h3 class="wb-label text-wb-teal">{{ $typeLabel }}</h3>
                    </div>
                    @foreach($items as $item)
                        <label class="flex items-center px-6 py-3 border-b border-white/[0.04] hover:bg-white/[0.02] cursor-pointer transition-colors">
                            <input type="checkbox" name="installed_item_ids[]" value="{{ $item->id }}" class="rounded bg-wb-card border-white/20 text-wb-teal focus:ring-wb-teal/30 mr-4">
                            <div class="flex-1">
                                <div class="text-sm text-white">{{ $item->name }}</div>
                                <div class="text-xs text-white/20 font-mono">{{ $item->slug }}</div>
                            </div>
                            <div class="text-sm font-mono text-white/80">
                                {{ $item->current_version }} &rarr; <span class="text-white">{{ $item->available_version }}</span>
                            </div>
                        </label>
                    @endforeach
                @endforeach
            </div>
        </form>
    @endif
</x-app-layout>

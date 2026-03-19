<x-app-layout>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">WUM Agent Plugin</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Changelog</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="wb-badge-green">Current: v{{ $version }}</span>
            <a href="{{ route('dashboard') }}" class="wb-btn-secondary">&larr; Dashboard</a>
        </div>
    </div>

    {{-- Version history --}}
    <div class="space-y-6">
        @foreach($versions as $index => $release)
            <div class="wb-card">
                <div class="px-6 py-4 border-b border-white/[0.04] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-lg text-white font-semibold">v{{ $release['version'] }}</span>
                        @if($index === 0)
                            <span class="wb-badge-green text-xs">Latest</span>
                        @endif
                    </div>
                </div>
                <div class="px-6 py-4">
                    <ul class="space-y-2">
                        @foreach($release['entries'] as $entry)
                            <li class="flex gap-3 text-sm">
                                <span class="text-wb-teal flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-white/80 font-body">{{ $entry }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>

<x-app-layout>
    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Total Sites</div>
            <div class="wb-metric">{{ $stats['total_sites'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Connected</div>
            <div class="wb-metric text-emerald-400">{{ $stats['connected_sites'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Pending Updates</div>
            <div class="wb-metric text-orange-400">{{ $stats['total_pending_updates'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Errors</div>
            <div class="wb-metric text-red-400">0</div>
        </div>
    </div>

    {{-- Sites table --}}
    <div class="wb-card">
        <div class="px-6 py-4 border-b border-white/[0.04] flex justify-between items-center">
            <span class="wb-label">Connected Sites</span>
            <a href="{{ route('sites.create') }}" class="wb-btn-primary">+ Add Site</a>
        </div>

        @if($sites->isEmpty())
            <div class="p-12 text-center">
                <p class="text-white/40 font-body text-sm">No sites connected yet.</p>
                <a href="{{ route('sites.create') }}" class="text-wb-teal text-sm mt-2 inline-block hover:underline">Add your first site</a>
            </div>
        @else
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.04]">
                        <th class="wb-table-header px-6 py-3">Site</th>
                        <th class="wb-table-header px-4 py-3">Status</th>
                        <th class="wb-table-header px-4 py-3">Updates</th>
                        <th class="wb-table-header px-4 py-3 text-right">Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sites as $site)
                        <tr class="border-b border-white/[0.04] hover:bg-white/[0.02] transition-colors cursor-pointer" onclick="window.location='{{ route('sites.show', $site) }}'">
                            <td class="px-6 py-3">
                                <div class="text-sm text-white font-medium">{{ $site->name }}</div>
                                <div class="text-xs text-white/30 font-mono">{{ $site->url }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @switch($site->status->value)
                                    @case('connected') <span class="wb-badge-green">Connected</span> @break
                                    @case('pending') <span class="wb-badge-yellow">Pending</span> @break
                                    @case('disconnected') <span class="wb-badge-gray">Disconnected</span> @break
                                    @case('error') <span class="wb-badge-red">Error</span> @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @if($site->pending_updates_count > 0)
                                    <span class="wb-badge-orange">{{ $site->pending_updates_count }} pending</span>
                                @else
                                    <span class="text-xs text-white/30 font-mono">Up to date</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-xs text-white/30 font-mono">
                                    @if($site->last_seen_at)
                                        {{ $site->last_seen_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>

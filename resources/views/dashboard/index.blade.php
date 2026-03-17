<x-app-layout>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-body">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm font-body">
            {{ session('error') }}
        </div>
    @endif

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

    {{-- Action bar: Update All + Auto-Updates --}}
    <div class="flex flex-wrap items-center gap-4 mb-8">
        {{-- Update All Sites button --}}
        <button onclick="document.getElementById('updateAllModal').classList.remove('hidden')"
                class="wb-btn-primary flex items-center gap-2"
                @if($stats['total_pending_updates'] === 0) disabled style="opacity:0.5;cursor:not-allowed" @endif>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Update All Sites
        </button>

        {{-- Auto-Update Toggle --}}
        <form method="POST" action="{{ route('updates.toggle-auto') }}" class="flex items-center gap-3">
            @csrf
            <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-lg border transition-colors text-sm font-mono tracking-wide
                {{ $autoUpdatesEnabled
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'
                    : 'border-white/10 bg-white/[0.03] text-white/60 hover:bg-white/[0.06]' }}">
                <span class="w-2 h-2 rounded-full {{ $autoUpdatesEnabled ? 'bg-emerald-400' : 'bg-white/30' }}"></span>
                Auto-Updates: {{ $autoUpdatesEnabled ? 'ON' : 'OFF' }}
            </button>
        </form>

        @if($autoUpdatesEnabled)
            <span class="text-sm text-white/50 font-body">1 site updated per hour, rotating through all connected sites</span>
        @endif
    </div>

    {{-- Sites table --}}
    <div class="wb-card">
        <div class="px-6 py-4 border-b border-white/[0.04] flex justify-between items-center">
            <span class="wb-label">Connected Sites</span>
            <a href="{{ route('sites.create') }}" class="wb-btn-primary">+ Add Site</a>
        </div>

        @if($sites->isEmpty())
            <div class="p-12 text-center">
                <p class="text-white/80 font-body text-sm">No sites connected yet.</p>
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
                                <div class="text-sm text-white/70 font-mono">{{ $site->url }}</div>
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
                                    <span class="text-sm text-white/80 font-mono">Up to date</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-white/80 font-mono">
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

    {{-- Update All Sites Confirmation Modal --}}
    <div id="updateAllModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="wb-card p-8 max-w-md w-full mx-4 shadow-2xl">
            <h2 class="text-lg font-sans font-semibold text-white mb-2">Update All Sites</h2>
            <p class="text-sm text-white/70 font-body mb-1">
                This will update <strong class="text-white">all connected sites</strong> in order:
            </p>
            <ol class="text-sm text-white/70 font-body mb-4 list-decimal list-inside space-y-1">
                <li>WordPress Core</li>
                <li>Themes</li>
                <li>Plugins</li>
            </ol>
            <p class="text-sm text-orange-400 font-body mb-4">
                Type <strong class="text-white font-mono">Update</strong> below to confirm.
            </p>

            <form method="POST" action="{{ route('updates.all-sites') }}" id="updateAllForm">
                @csrf
                <input type="text"
                       id="confirmationInput"
                       name="confirmation_text"
                       class="wb-input w-full mb-4"
                       placeholder="Type Update to confirm"
                       autocomplete="off"
                       oninput="document.getElementById('confirmBtn').disabled = this.value !== 'Update'">

                <div class="flex gap-3 justify-end">
                    <button type="button"
                            onclick="document.getElementById('updateAllModal').classList.add('hidden'); document.getElementById('confirmationInput').value = '';"
                            class="wb-btn-secondary">
                        Cancel
                    </button>
                    <button type="submit"
                            id="confirmBtn"
                            disabled
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-mono text-sm tracking-wide hover:bg-red-500 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                        Confirm Update All
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

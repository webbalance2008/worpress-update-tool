<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">{{ $site->url }}</p>
        </div>
        <div class="flex items-center gap-3">
            @switch($site->status->value)
                @case('connected') <span class="wb-badge-green">Connected</span> @break
                @case('pending') <span class="wb-badge-yellow">Pending</span> @break
                @case('disconnected') <span class="wb-badge-gray">Disconnected</span> @break
                @case('error') <span class="wb-badge-red">Error</span> @break
            @endswitch
            <form method="POST" action="{{ route('sites.sync', $site) }}">
                @csrf
                <button type="submit" class="wb-btn-secondary">Sync Now</button>
            </form>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link {{ request()->routeIs('sites.show') ? 'active' : '' }}">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link {{ request()->routeIs('sites.updates') ? 'active' : '' }}">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link {{ request()->routeIs('sites.history') ? 'active' : '' }}">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link {{ request()->routeIs('sites.health') ? 'active' : '' }}">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link {{ request()->routeIs('sites.errors') ? 'active' : '' }}">Errors</a>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Site metadata --}}
        <div class="wb-card p-6">
            <h3 class="wb-label mb-4">Site Info</h3>
            <dl class="space-y-4">
                <div>
                    <dt class="font-mono text-[0.625rem] uppercase tracking-[0.12em] text-white/80">WordPress Version</dt>
                    <dd class="text-sm text-white mt-0.5">{{ $site->wp_version ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt class="font-mono text-[0.625rem] uppercase tracking-[0.12em] text-white/80">PHP Version</dt>
                    <dd class="text-sm text-white mt-0.5">{{ $site->php_version ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt class="font-mono text-[0.625rem] uppercase tracking-[0.12em] text-white/80">Active Theme</dt>
                    <dd class="text-sm text-white mt-0.5">{{ $site->active_theme ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt class="font-mono text-[0.625rem] uppercase tracking-[0.12em] text-white/80">Last Seen</dt>
                    <dd class="text-sm text-white mt-0.5">{{ $site->last_seen_at?->diffForHumans() ?? 'Never' }}</dd>
                </div>
            </dl>

            @if($site->status->value === 'pending' && $site->registration_token)
                <div class="mt-6 p-4 rounded-md bg-wb-teal/10 border border-wb-teal/30">
                    <p class="wb-label text-wb-teal mb-2">Registration Token</p>
                    <code class="text-sm text-wb-teal break-all font-mono">{{ $site->registration_token }}</code>
                    <p class="text-xs text-wb-teal/60 mt-2">Enter this token in the WordPress plugin settings to connect.</p>
                </div>
            @endif
        </div>

        {{-- Pending updates --}}
        <div class="wb-card p-6">
            <h3 class="wb-label mb-4">
                Pending Updates ({{ $pendingUpdates->count() }})
            </h3>
            @if($pendingUpdates->isEmpty())
                <p class="text-sm text-white/80">All up to date.</p>
            @else
                <ul class="space-y-3">
                    @foreach($pendingUpdates->take(8) as $item)
                        <li class="flex justify-between items-center">
                            <span class="text-sm text-white/80">{{ $item->name }}</span>
                            <span class="font-mono text-[0.625rem] text-white/80">{{ $item->current_version }} &rarr; {{ $item->available_version }}</span>
                        </li>
                    @endforeach
                </ul>
                @if($pendingUpdates->count() > 8)
                    <a href="{{ route('sites.updates', $site) }}" class="block mt-4 text-sm text-wb-teal hover:underline">
                        View all {{ $pendingUpdates->count() }} updates
                    </a>
                @endif
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="wb-card p-6">
            <h3 class="wb-label mb-4">Recent Jobs</h3>
            @if($recentJobs->isEmpty())
                <p class="text-sm text-white/80">No update history yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach($recentJobs->take(5) as $job)
                        <li>
                            <a href="{{ route('updates.show', [$site, $job->id]) }}" class="text-sm text-wb-teal hover:underline">
                                Job #{{ $job->id }}
                            </a>
                            <span class="text-sm text-white/80"> &mdash; {{ $job->status->label() }}</span>
                            <span class="block text-xs text-white/80 font-mono mt-0.5">{{ $job->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($latestHealthCheck)
                <div class="mt-5 pt-5 border-t border-white/[0.04]">
                    <h4 class="wb-label mb-2">Latest Health Check</h4>
                    @switch($latestHealthCheck->status->value)
                        @case('passed') <span class="wb-badge-green">Passed</span> @break
                        @case('degraded') <span class="wb-badge-orange">Degraded</span> @break
                        @case('failed') <span class="wb-badge-red">Failed</span> @break
                        @default <span class="wb-badge-gray">{{ $latestHealthCheck->status->label() }}</span>
                    @endswitch
                    <span class="text-xs text-white/80 font-mono ml-2">{{ $latestHealthCheck->created_at->diffForHumans() }}</span>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Health Checks</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link active">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link">Errors</a>
        </nav>
    </div>

    @if($checks->isEmpty())
        <div class="wb-card p-12 text-center">
            <p class="text-white/80 text-sm">No health checks recorded yet.</p>
        </div>
    @else
        <div class="wb-card">
            <div class="px-6 py-4 border-b border-white/[0.04]">
                <span class="wb-label">Health Check Results</span>
            </div>
            @foreach($checks as $check)
                <div class="px-6 py-4 border-b border-white/[0.04]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @switch($check->status->value)
                                @case('passed') <span class="wb-badge-green">Passed</span> @break
                                @case('degraded') <span class="wb-badge-orange">Degraded</span> @break
                                @case('failed') <span class="wb-badge-red">Failed</span> @break
                                @default <span class="wb-badge-gray">{{ $check->status->label() }}</span>
                            @endswitch
                            @if($check->updateJob)
                                <span class="text-sm text-white/80">Job #{{ $check->update_job_id }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-white/80 font-mono">{{ $check->created_at->diffForHumans() }}</span>
                    </div>
                    @if($check->response_time_ms)
                        <div class="mt-2 text-sm text-white/80 font-mono">
                            Response: {{ $check->response_time_ms }}ms
                            @if($check->http_status)
                                &middot; HTTP {{ $check->http_status }}
                            @endif
                        </div>
                    @endif
                    @if($check->notes)
                        <p class="mt-2 text-sm text-white/80">{{ $check->notes }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $checks->links() }}
        </div>
    @endif
</x-app-layout>

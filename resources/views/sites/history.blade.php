<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Update History</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link active">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link">Errors</a>
        </nav>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-md bg-emerald-500/10 border border-emerald-500/30 text-sm text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if($jobs->isEmpty())
        <div class="wb-card p-12 text-center">
            <p class="text-white/80 text-sm">No update jobs yet.</p>
        </div>
    @else
        <div class="wb-card">
            <div class="px-6 py-4 border-b border-white/[0.04]">
                <span class="wb-label">Update Jobs</span>
            </div>
            @foreach($jobs as $job)
                <a href="{{ route('updates.show', [$site, $job->id]) }}" class="block px-6 py-4 border-b border-white/[0.04] hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm text-white font-medium">Job #{{ $job->id }}</span>
                            <span class="text-sm text-white/80 ml-2">{{ $job->items->count() }} item(s)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            @switch($job->status->value)
                                @case('completed') <span class="wb-badge-green">Completed</span> @break
                                @case('failed') <span class="wb-badge-red">Failed</span> @break
                                @case('in_progress') <span class="wb-badge bg-blue-500/15 text-blue-400 border border-blue-500/30">In Progress</span> @break
                                @case('queued') <span class="wb-badge-yellow">Queued</span> @break
                                @default <span class="wb-badge-gray">{{ $job->status->label() }}</span>
                            @endswitch
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-1">
                        <span class="text-xs text-white/80 font-mono">{{ $job->created_at->diffForHumans() }}</span>
                        @if($job->riskAssessment)
                            <span class="text-xs text-white/80 font-mono">Risk: {{ $job->riskAssessment->score }} ({{ ucfirst($job->riskAssessment->level->value) }})</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $jobs->links() }}
        </div>
    @endif
</x-app-layout>

<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('sites.history', $site) }}" class="font-mono text-xs text-wb-teal uppercase tracking-[0.12em] hover:underline">&larr; Back to History</a>
    </div>

    <h1 class="font-sans text-xl font-medium text-white mb-6">
        Update Job #{{ $job->id }} &mdash; {{ $site->name }}
    </h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="wb-card p-6">
            <h3 class="wb-label mb-3">Status</h3>
            @switch($job->status->value)
                @case('completed') <span class="wb-badge-green">Completed</span> @break
                @case('failed') <span class="wb-badge-red">Failed</span> @break
                @case('in_progress') <span class="wb-badge bg-blue-500/15 text-blue-400 border border-blue-500/30">In Progress</span> @break
                @default <span class="wb-badge-yellow">{{ $job->status->label() }}</span>
            @endswitch
            @if($job->summary)
                <p class="text-sm text-white/90 mt-3">{{ $job->summary }}</p>
            @endif
        </div>

        <div class="wb-card p-6">
            <h3 class="wb-label mb-3">Risk Assessment</h3>
            @if($job->riskAssessment)
                <div class="flex items-center gap-3 mb-3">
                    <span class="wb-metric">{{ $job->riskAssessment->score }}</span>
                    <span class="text-sm text-white/60">{{ ucfirst($job->riskAssessment->level->value) }}</span>
                </div>
                <p class="text-sm text-white/80">{{ $job->riskAssessment->explanation }}</p>
            @else
                <p class="text-sm text-white/80">No risk assessment recorded.</p>
            @endif
        </div>

        <div class="wb-card p-6">
            <h3 class="wb-label mb-3">Post-Update Health</h3>
            @if($job->healthChecks->isEmpty())
                <p class="text-sm text-white/80">No health check results yet.</p>
            @else
                @php $hc = $job->healthChecks->first(); @endphp
                @switch($hc->status->value)
                    @case('passed') <span class="wb-badge-green">Passed</span> @break
                    @case('degraded') <span class="wb-badge-orange">Degraded</span> @break
                    @case('failed') <span class="wb-badge-red">Failed</span> @break
                    @default <span class="wb-badge-gray">{{ $hc->status->label() }}</span>
                @endswitch
            @endif
        </div>
    </div>

    <div class="wb-card">
        <div class="px-6 py-4 border-b border-white/[0.04]">
            <span class="wb-label">Update Items</span>
        </div>
        @foreach($job->items as $item)
            <div class="px-6 py-4 border-b border-white/[0.04]">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-white">{{ $item->slug }} <span class="text-xs text-white/80 font-mono">({{ $item->type }})</span></span>
                    @if($item->status === 'completed')
                        <span class="wb-badge-green">Completed</span>
                    @else
                        <span class="wb-badge-red">{{ ucfirst($item->status) }}</span>
                    @endif
                </div>
                <div class="text-sm text-white/80 font-mono mt-1">{{ $item->old_version }} &rarr; {{ $item->resulting_version ?? $item->requested_version }}</div>
                @if($item->error_message)
                    <div class="mt-2 text-sm text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded-md font-mono text-xs">{{ $item->error_message }}</div>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>

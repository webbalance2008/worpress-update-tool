@extends('layouts.app')

@section('title', 'Update Job #' . $job->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('sites.history', $site) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to History</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Update Job #{{ $job->id }}</h1>
        <p class="text-sm text-gray-500">{{ $site->name }} &mdash; {{ $job->created_at->format('M j, Y g:ia') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Status --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Status</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                @switch($job->status->value)
                    @case('completed') bg-green-100 text-green-800 @break
                    @case('failed') bg-red-100 text-red-800 @break
                    @case('in_progress') bg-blue-100 text-blue-800 @break
                    @case('partially_failed') bg-orange-100 text-orange-800 @break
                    @default bg-yellow-100 text-yellow-800
                @endswitch
            ">
                {{ $job->status->label() }}
            </span>
            @if($job->summary)
                <p class="text-sm text-gray-600 mt-3">{{ $job->summary }}</p>
            @endif
            <dl class="mt-4 space-y-2 text-sm">
                <div><dt class="text-gray-500 inline">Started:</dt> <dd class="inline">{{ $job->started_at?->format('g:ia') ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500 inline">Completed:</dt> <dd class="inline">{{ $job->completed_at?->format('g:ia') ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500 inline">Duration:</dt>
                    <dd class="inline">
                        @if($job->started_at && $job->completed_at)
                            {{ $job->started_at->diffInSeconds($job->completed_at) }}s
                        @else
                            N/A
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Risk Assessment --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Risk Assessment</h3>
            @if($job->riskAssessment)
                <div class="flex items-center space-x-3 mb-3">
                    <span class="text-3xl font-bold
                        @switch($job->riskAssessment->level->value)
                            @case('low') text-green-600 @break
                            @case('medium') text-yellow-600 @break
                            @case('high') text-red-600 @break
                        @endswitch
                    ">{{ $job->riskAssessment->score }}</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @switch($job->riskAssessment->level->value)
                            @case('low') bg-green-100 text-green-800 @break
                            @case('medium') bg-yellow-100 text-yellow-800 @break
                            @case('high') bg-red-100 text-red-800 @break
                        @endswitch
                    ">{{ ucfirst($job->riskAssessment->level->value) }}</span>
                </div>
                <p class="text-sm text-gray-600">{{ $job->riskAssessment->explanation }}</p>
                @if(is_array($job->riskAssessment->factors) && count($job->riskAssessment->factors) > 0)
                    <ul class="mt-3 space-y-1">
                        @foreach($job->riskAssessment->factors as $factor)
                            <li class="text-xs text-gray-500">
                                +{{ $factor['score'] }} &mdash; {{ $factor['description'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                <p class="text-sm text-gray-400">No risk assessment recorded.</p>
            @endif
        </div>

        {{-- Health Checks --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Post-Update Health</h3>
            @if($job->healthChecks->isEmpty())
                <p class="text-sm text-gray-400">No health check results yet.</p>
            @else
                @php $hc = $job->healthChecks->first(); @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mb-3
                    @switch($hc->status->value)
                        @case('passed') bg-green-100 text-green-800 @break
                        @case('degraded') bg-orange-100 text-orange-800 @break
                        @case('failed') bg-red-100 text-red-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch
                ">{{ $hc->status->label() }}</span>
                @if(is_array($hc->checks))
                    <dl class="space-y-2">
                        @foreach($hc->checks as $name => $check)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $name)) }}</span>
                                @if($check['passed'] ?? false)
                                    <span class="text-green-600 text-xs font-medium">PASS</span>
                                @else
                                    <span class="text-red-600 text-xs font-medium">FAIL</span>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                @endif
            @endif
        </div>
    </div>

    {{-- Update items --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Update Items</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($job->items as $item)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-900">{{ $item->slug }}</span>
                            <span class="text-xs text-gray-500 ml-2">({{ $item->type }})</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $item->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ $item->old_version }} &rarr;
                        {{ $item->resulting_version ?? $item->requested_version }}
                        @if($item->resulting_version && $item->resulting_version !== $item->requested_version)
                            <span class="text-red-600">(expected {{ $item->requested_version }})</span>
                        @endif
                    </div>
                    @if($item->error_message)
                        <div class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded">{{ $item->error_message }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Errors --}}
    @if($job->errorLogs->isNotEmpty())
        <div class="mt-6 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Errors</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($job->errorLogs as $error)
                    <div class="px-6 py-3 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mr-2
                            {{ $error->severity->value === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $error->severity->value }}
                        </span>
                        {{ $error->message }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection

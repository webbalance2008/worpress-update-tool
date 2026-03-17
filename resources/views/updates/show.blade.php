<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Update Job #{{ $job->id }} &mdash; {{ $site->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('sites.history', $site) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to History</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Status</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @switch($job->status->value)
                            @case('completed') bg-green-100 text-green-800 @break
                            @case('failed') bg-red-100 text-red-800 @break
                            @case('in_progress') bg-blue-100 text-blue-800 @break
                            @default bg-yellow-100 text-yellow-800
                        @endswitch
                    ">{{ $job->status->label() }}</span>
                    @if($job->summary)
                        <p class="text-sm text-gray-600 mt-3">{{ $job->summary }}</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Risk Assessment</h3>
                    @if($job->riskAssessment)
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="text-3xl font-bold">{{ $job->riskAssessment->score }}</span>
                            <span class="text-sm font-medium">{{ ucfirst($job->riskAssessment->level->value) }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ $job->riskAssessment->explanation }}</p>
                    @else
                        <p class="text-sm text-gray-400">No risk assessment recorded.</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Update Items</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($job->items as $item)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">{{ $item->slug }} <span class="text-xs text-gray-500">({{ $item->type }})</span></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $item->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($item->status) }}</span>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">{{ $item->old_version }} &rarr; {{ $item->resulting_version ?? $item->requested_version }}</div>
                            @if($item->error_message)
                                <div class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded">{{ $item->error_message }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $site->name }}</h2>
                <p class="text-sm text-gray-500">{{ $site->url }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @switch($site->status->value)
                        @case('connected') bg-green-100 text-green-800 @break
                        @case('pending') bg-yellow-100 text-yellow-800 @break
                        @case('disconnected') bg-gray-100 text-gray-800 @break
                        @case('error') bg-red-100 text-red-800 @break
                    @endswitch
                ">{{ $site->status->label() }}</span>
                <form method="POST" action="{{ route('sites.sync', $site) }}">
                    @csrf
                    <x-secondary-button type="submit">Sync Now</x-secondary-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Navigation tabs --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-8">
                    <a href="{{ route('sites.show', $site) }}" class="border-b-2 border-indigo-500 pb-3 text-sm font-medium text-indigo-600">Overview</a>
                    <a href="{{ route('sites.updates', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Updates</a>
                    <a href="{{ route('sites.history', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">History</a>
                    <a href="{{ route('sites.health', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Health</a>
                    <a href="{{ route('sites.errors', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Errors</a>
                </nav>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Site metadata --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Site Info</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs text-gray-500">WordPress Version</dt>
                            <dd class="text-sm font-medium">{{ $site->wp_version ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">PHP Version</dt>
                            <dd class="text-sm font-medium">{{ $site->php_version ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Active Theme</dt>
                            <dd class="text-sm font-medium">{{ $site->active_theme ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Last Seen</dt>
                            <dd class="text-sm font-medium">{{ $site->last_seen_at?->diffForHumans() ?? 'Never' }}</dd>
                        </div>
                    </dl>

                    @if($site->status->value === 'pending' && $site->registration_token)
                        <div class="mt-6 p-3 bg-yellow-50 rounded border border-yellow-200">
                            <p class="text-xs text-yellow-800 font-medium mb-1">Registration Token</p>
                            <code class="text-sm break-all">{{ $site->registration_token }}</code>
                            <p class="text-xs text-yellow-600 mt-2">Enter this token in the WordPress plugin settings to connect.</p>
                        </div>
                    @endif
                </div>

                {{-- Pending updates --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        Pending Updates ({{ $pendingUpdates->count() }})
                    </h3>
                    @if($pendingUpdates->isEmpty())
                        <p class="text-sm text-gray-400">All up to date.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($pendingUpdates->take(8) as $item)
                                <li class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700">{{ $item->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $item->current_version }} &rarr; {{ $item->available_version }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if($pendingUpdates->count() > 8)
                            <a href="{{ route('sites.updates', $site) }}" class="block mt-3 text-sm text-indigo-600 hover:underline">
                                View all {{ $pendingUpdates->count() }} updates
                            </a>
                        @endif
                    @endif
                </div>

                {{-- Recent activity --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Recent Jobs</h3>
                    @if($recentJobs->isEmpty())
                        <p class="text-sm text-gray-400">No update history yet.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($recentJobs->take(5) as $job)
                                <li class="text-sm">
                                    <a href="{{ route('updates.show', [$site, $job->id]) }}" class="text-indigo-600 hover:underline">
                                        Job #{{ $job->id }}
                                    </a>
                                    <span class="text-gray-500">- {{ $job->status->label() }}</span>
                                    <span class="text-xs text-gray-400 block">{{ $job->created_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($latestHealthCheck)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Latest Health Check</h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @switch($latestHealthCheck->status->value)
                                    @case('passed') bg-green-100 text-green-800 @break
                                    @case('degraded') bg-orange-100 text-orange-800 @break
                                    @case('failed') bg-red-100 text-red-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch
                            ">{{ $latestHealthCheck->status->label() }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $latestHealthCheck->created_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

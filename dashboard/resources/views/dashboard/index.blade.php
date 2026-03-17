@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Total Sites</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_sites'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Connected</div>
            <div class="text-3xl font-bold text-green-600 mt-1">{{ $stats['connected_sites'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Pending Updates</div>
            <div class="text-3xl font-bold text-orange-600 mt-1">{{ $stats['total_pending_updates'] }}</div>
        </div>
    </div>

    {{-- Sites grid --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Connected Sites</h2>
            <a href="{{ route('sites.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
                Add Site
            </a>
        </div>

        @if($sites->isEmpty())
            <div class="p-12 text-center text-gray-500">
                <p>No sites connected yet.</p>
                <a href="{{ route('sites.create') }}" class="text-indigo-600 hover:underline mt-2 inline-block">Add your first site</a>
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($sites as $site)
                    <a href="{{ route('sites.show', $site) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900">{{ $site->name }}</div>
                                <div class="text-sm text-gray-500">{{ $site->url }}</div>
                            </div>
                            <div class="flex items-center space-x-4">
                                @if($site->pending_updates_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        {{ $site->pending_updates_count }} updates
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($site->status->value)
                                        @case('connected') bg-green-100 text-green-800 @break
                                        @case('pending') bg-yellow-100 text-yellow-800 @break
                                        @case('disconnected') bg-gray-100 text-gray-800 @break
                                        @case('error') bg-red-100 text-red-800 @break
                                    @endswitch
                                ">
                                    {{ $site->status->label() }}
                                </span>
                                <div class="text-xs text-gray-400">
                                    @if($site->last_seen_at)
                                        Last seen {{ $site->last_seen_at->diffForHumans() }}
                                    @else
                                        Never connected
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

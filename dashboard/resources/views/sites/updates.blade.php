@extends('layouts.app')

@section('title', 'Updates - ' . $site->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $site->name }}</h1>
            <p class="text-sm text-gray-500">Available Updates</p>
        </div>
        @if($pendingUpdates->where('type', App\Enums\InstalledItemType::Plugin)->isNotEmpty())
            <form method="POST" action="{{ route('updates.all-plugins', $site) }}">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700"
                        onclick="return confirm('Update all plugins?')">
                    Update All Plugins
                </button>
            </form>
        @endif
    </div>

    {{-- Navigation tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-8">
            <a href="{{ route('sites.show', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="border-b-2 border-indigo-500 pb-3 text-sm font-medium text-indigo-600">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">History</a>
            <a href="{{ route('sites.health', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Errors</a>
        </nav>
    </div>

    @if($pendingUpdates->isEmpty())
        <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
            Everything is up to date.
        </div>
    @else
        {{-- Batch update form --}}
        <form method="POST" action="{{ route('updates.trigger', $site) }}">
            @csrf

            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $pendingUpdates->count() }} Available Updates</h2>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700"
                            onclick="return confirm('Trigger updates for selected items?')">
                        Update Selected
                    </button>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($pendingUpdates->groupBy(fn($item) => $item->type->label()) as $typeLabel => $items)
                        <div class="px-6 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-600">{{ $typeLabel }}</h3>
                        </div>
                        @foreach($items as $item)
                            <label class="flex items-center px-6 py-3 hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="installed_item_ids[]" value="{{ $item->id }}"
                                       class="rounded border-gray-300 text-indigo-600 mr-4">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 text-sm">{{ $item->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->slug }}</div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $item->current_version }}
                                    <span class="mx-1">&rarr;</span>
                                    <span class="font-medium text-gray-900">{{ $item->available_version }}</span>
                                </div>
                                <div class="ml-4">
                                    @if($item->isMajorUpdate())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            Major
                                        </span>
                                    @endif
                                    @if(!$item->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </form>
    @endif

    {{-- Core update --}}
    @php
        $coreItem = $site->installedItems->firstWhere('type', App\Enums\InstalledItemType::Core);
    @endphp
    @if($coreItem && $coreItem->hasUpdate())
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">WordPress Core Update</h3>
                    <p class="text-sm text-gray-500">
                        {{ $coreItem->current_version }} &rarr; {{ $coreItem->available_version }}
                    </p>
                </div>
                <form method="POST" action="{{ route('updates.core', $site) }}">
                    @csrf
                    <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700"
                            onclick="return confirm('Update WordPress core?')">
                        Update Core
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $site->name }}</h2>
                <p class="text-sm text-gray-500">Available Updates</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($pendingUpdates->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center text-gray-500">
                    Everything is up to date.
                </div>
            @else
                <form method="POST" action="{{ route('updates.trigger', $site) }}">
                    @csrf
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $pendingUpdates->count() }} Available Updates</h2>
                            <x-primary-button onclick="return confirm('Trigger updates for selected items?')">
                                Update Selected
                            </x-primary-button>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($pendingUpdates->groupBy(fn($item) => $item->type->label()) as $typeLabel => $items)
                                <div class="px-6 py-3 bg-gray-50">
                                    <h3 class="text-sm font-semibold text-gray-600">{{ $typeLabel }}</h3>
                                </div>
                                @foreach($items as $item)
                                    <label class="flex items-center px-6 py-3 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="installed_item_ids[]" value="{{ $item->id }}" class="rounded border-gray-300 text-indigo-600 mr-4">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 text-sm">{{ $item->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->slug }}</div>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $item->current_version }} &rarr; <span class="font-medium text-gray-900">{{ $item->available_version }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

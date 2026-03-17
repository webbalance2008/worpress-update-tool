@extends('layouts.app')

@section('title', 'Add Site')

@section('content')
    <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Add a New Site</h1>

        <form method="POST" action="{{ route('sites.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Site Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="url" class="block text-sm font-medium text-gray-700">Site URL</label>
                <input type="url" name="url" id="url" value="{{ old('url') }}" required placeholder="https://example.com"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                @error('url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Create Site
                </button>
            </div>
        </form>

        <p class="mt-4 text-sm text-gray-500 text-center">
            After creating the site, you'll receive a registration token to configure in the WordPress agent plugin.
        </p>
    </div>
@endsection

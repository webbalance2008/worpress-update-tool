<x-app-layout>
    <div class="max-w-lg mx-auto">
        <h1 class="font-sans text-xl font-medium text-white mb-6">Add a New Site</h1>

        <form method="POST" action="{{ route('sites.store') }}" class="wb-card p-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="wb-label block mb-2">Site Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="wb-input w-full" />
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="url" class="wb-label block mb-2">Site URL</label>
                <input id="url" name="url" type="url" value="{{ old('url') }}" required placeholder="https://example.com" class="wb-input w-full" />
                @error('url') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="wb-btn-primary w-full text-center">Create Site</button>
            </div>
        </form>

        <p class="mt-4 text-sm text-white/80 text-center font-body">
            After creating the site, you'll receive a registration token to configure in the WordPress agent plugin.
        </p>
    </div>
</x-app-layout>

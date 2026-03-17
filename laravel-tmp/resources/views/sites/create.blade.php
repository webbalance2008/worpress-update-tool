<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add a New Site</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sites.store') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Site Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="url" value="Site URL" />
                    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url')" required placeholder="https://example.com" />
                    <x-input-error :messages="$errors->get('url')" class="mt-2" />
                </div>

                <div class="pt-4">
                    <x-primary-button class="w-full justify-center">
                        Create Site
                    </x-primary-button>
                </div>
            </form>

            <p class="mt-4 text-sm text-gray-500 text-center">
                After creating the site, you'll receive a registration token to configure in the WordPress agent plugin.
            </p>
        </div>
    </div>
</x-app-layout>

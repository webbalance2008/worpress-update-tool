<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'WP Update Manager') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body antialiased">
        <div class="min-h-screen bg-wb-bg">
            {{-- Header --}}
            <nav class="bg-wb-card border-b border-white/[0.08]">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <div class="flex justify-between h-14 items-center">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard') }}" class="font-mono text-sm tracking-[0.15em] uppercase text-white/90">WP Update Manager</a>
                            <span class="text-white/20">&middot;</span>
                            <span class="font-mono text-sm tracking-[0.15em] uppercase text-wb-teal">Admin</span>
                        </div>

                        <div class="flex items-center gap-6">
                            <span class="font-mono text-[0.6875rem] text-white/40 uppercase tracking-[0.12em]">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="font-mono text-[0.6875rem] uppercase tracking-[0.15em] text-white/50 hover:text-wb-teal transition-colors">Sign Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            {{-- Tab Navigation --}}
            <div class="bg-wb-card border-b border-white/[0.08]">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <nav class="flex gap-0 scrollbar-hide overflow-x-auto">
                        <a href="{{ route('dashboard') }}" class="wb-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    </nav>
                </div>
            </div>

            {{-- Page Content --}}
            <main class="max-w-7xl mx-auto px-6 lg:px-8 py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

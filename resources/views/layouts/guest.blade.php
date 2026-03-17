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
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-wb-bg">
            <div class="mb-6">
                <a href="/" class="font-mono text-sm tracking-[0.15em] uppercase text-white/90">
                    WP Update Manager
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-8 wb-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

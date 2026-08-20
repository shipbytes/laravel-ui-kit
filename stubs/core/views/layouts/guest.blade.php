<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('ui-kit.brand.name') }}</title>

        <x-ui-kit::head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:600,700|montserrat:400,500&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased">
        <div class="relative min-h-screen flex flex-col items-center justify-center px-6 py-12 bg-white dark:bg-zinc-950 overflow-hidden">
            {{-- Decorative blurs --}}
            <div class="absolute top-0 left-1/4 w-[500px] h-[400px] bg-indigo-100/60 dark:bg-indigo-500/10 rounded-full blur-3xl -translate-y-1/2"></div>
            <div class="absolute top-20 right-1/4 w-[400px] h-[300px] bg-violet-100/40 dark:bg-violet-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/3 w-[400px] h-[300px] bg-indigo-50/80 dark:bg-indigo-400/10 rounded-full blur-3xl translate-y-1/2"></div>

            {{-- Logo --}}
            <div class="relative mb-8">
                <a href="{{ \Shipbytes\UiKit\Support\UiKit::homeUrl() }}" wire:navigate class="flex items-center gap-3">
                    <img src="{{ asset(config('ui-kit.brand.logo')) }}" class="h-8 w-auto" alt="{{ config('ui-kit.brand.name') }}">
                    <span class="font-boldtext text-xl tracking-tight text-gray-950 dark:text-zinc-50">{{ config('ui-kit.brand.name') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="relative w-full sm:max-w-md">
                <div class="rounded-3xl bg-white dark:bg-zinc-900 ring-1 ring-gray-950/5 dark:ring-white/10 shadow-xl shadow-gray-950/5 dark:shadow-none p-8 sm:p-10">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative mt-8 text-center">
                <p class="text-xs text-gray-400 dark:text-zinc-500 font-booktext">&copy; {{ date('Y') }} {{ config('ui-kit.brand.name') }}. All rights reserved.</p>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>

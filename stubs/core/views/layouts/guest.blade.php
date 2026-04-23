<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('ui-kit.brand.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased">
        <div class="relative min-h-screen flex flex-col items-center justify-center px-6 py-12 bg-white overflow-hidden">
            {{-- Decorative blurs --}}
            <div class="absolute top-0 left-1/4 w-[500px] h-[400px] bg-indigo-100/60 rounded-full blur-3xl -translate-y-1/2"></div>
            <div class="absolute top-20 right-1/4 w-[400px] h-[300px] bg-violet-100/40 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/3 w-[400px] h-[300px] bg-indigo-50/80 rounded-full blur-3xl translate-y-1/2"></div>

            {{-- Logo --}}
            <div class="relative mb-8">
                <a href="{{ route(config('ui-kit.brand.home_route')) }}" wire:navigate class="flex items-center gap-3">
                    <img src="{{ asset(config('ui-kit.brand.logo')) }}" class="h-8 w-auto" alt="{{ config('ui-kit.brand.name') }}">
                    <span class="font-boldtext text-xl tracking-tight text-gray-950">{{ config('ui-kit.brand.name') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="relative w-full sm:max-w-md">
                <div class="rounded-3xl bg-white ring-1 ring-gray-950/5 shadow-xl shadow-gray-950/5 p-8 sm:p-10">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative mt-8 text-center">
                <p class="text-xs text-gray-400 font-booktext">&copy; {{ date('Y') }} {{ config('ui-kit.brand.name') }}. All rights reserved.</p>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>

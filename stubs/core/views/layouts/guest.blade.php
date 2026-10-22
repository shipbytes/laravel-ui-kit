<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('ui-kit.brand.name') }}</title>

        <x-ui-kit::head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="bg-surface-sunken text-fg font-sans antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 py-12">
            {{-- Decorative blurs --}}
            <div class="absolute top-0 left-1/4 h-[400px] w-[500px] -translate-y-1/2 rounded-full bg-accent-wash blur-3xl" aria-hidden="true"></div>
            <div class="absolute right-1/3 bottom-0 h-[300px] w-[400px] translate-y-1/2 rounded-full bg-accent-wash blur-3xl" aria-hidden="true"></div>

            {{-- Logo --}}
            <div class="relative mb-8">
                <a href="{{ \Shipbytes\UiKit\Support\UiKit::homeUrl() }}" wire:navigate class="flex items-center gap-3">
                    @include('layouts.partials.brand-mark', ['markClass' => 'h-8 w-auto', 'badgeClass' => 'size-8'])
                    <span class="text-title font-semibold tracking-tight text-fg">{{ config('ui-kit.brand.name') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="relative w-full sm:max-w-md">
                <div class="rounded-panel border border-border bg-surface p-8 shadow-float sm:p-10">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative mt-8 text-center">
                <p class="text-meta text-fg-muted">&copy; {{ date('Y') }} {{ config('ui-kit.brand.name') }}. All rights reserved.</p>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>

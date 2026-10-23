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
    <body class="min-h-svh bg-surface-sunken text-fg font-sans antialiased">
        <x-ui-kit::banners />

        @php
            $shellUser = auth()->user();
            $shellIsAdmin = \Shipbytes\UiKit\Support\UiKit::isAdmin($shellUser);
        @endphp

        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-4 sm:px-6">
                <a href="{{ \Shipbytes\UiKit\Support\UiKit::homeUrl() }}" wire:navigate class="flex items-center gap-2">
                    @include('layouts.partials.brand-mark', ['markClass' => 'h-6 w-auto', 'badgeClass' => 'size-6'])
                    <span class="text-section font-semibold text-fg">{{ config('ui-kit.brand.name') }}</span>
                </a>

                <div class="flex items-center gap-1">
                    @if ($shellIsAdmin && Illuminate\Support\Facades\Route::has('admin.dashboard'))
                        <x-ds::button variant="ghost" size="sm" :href="route('admin.dashboard')" wire:navigate>
                            Admin
                        </x-ds::button>
                    @endif

                    <x-theme-toggle />

                    @if (Illuminate\Support\Facades\Route::has('profile'))
                        <a href="{{ route('profile') }}" wire:navigate aria-label="Profile" title="Profile"
                           class="rounded-full p-1 transition-colors hover:bg-fg/5">
                            <x-ds::avatar size="xs" :name="$shellUser->name ?? null" :src="\Shipbytes\UiKit\Support\UiKit::avatarUrl($shellUser)" :decorative="true" />
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ds::button type="submit" variant="ghost" size="sm">
                            Log out
                        </x-ds::button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>

        @stack('scripts')
    </body>
</html>

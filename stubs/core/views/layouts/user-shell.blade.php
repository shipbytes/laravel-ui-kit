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
    <body class="font-sans antialiased bg-zinc-50 dark:bg-zinc-950 min-h-svh">
        <x-ui-kit::banners />

        @php
            $shellUser = auth()->user();
            $shellIsAdmin = $shellUser && (
                (method_exists($shellUser, 'hasRole') && $shellUser->hasRole('admin'))
                || (bool) ($shellUser->is_admin ?? false)
            );
        @endphp

        <header class="bg-white dark:bg-zinc-900 border-b border-zinc-950/5 dark:border-white/10">
            <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-4 sm:px-6">
                <a href="{{ \Shipbytes\UiKit\Support\UiKit::homeUrl() }}" wire:navigate class="flex items-center gap-2">
                    @include('layouts.partials.brand-mark', ['markClass' => 'h-6 w-auto', 'badgeClass' => 'size-6'])
                    <span class="font-boldtext text-sm font-semibold text-zinc-950 dark:text-zinc-50">{{ config('ui-kit.brand.name') }}</span>
                </a>

                <div class="flex items-center gap-2">
                    @if ($shellIsAdmin && Illuminate\Support\Facades\Route::has('admin.dashboard'))
                        <a href="{{ route('admin.dashboard') }}" wire:navigate
                           class="rounded-md px-2.5 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-950/5 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-zinc-50">
                            Admin
                        </a>
                    @endif

                    <x-theme-toggle />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md px-2.5 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-950/5 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-zinc-50">
                            Log out
                        </button>
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

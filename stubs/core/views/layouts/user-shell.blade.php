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

            // A module's page appears here once its route exists, the way
            // Profile does. Support needs an account, so guests never see it.
            $shellLinks = array_filter([
                'Support' => $shellUser && \Illuminate\Support\Facades\Route::has('support.index') ? ['support.index', 'support.*'] : null,
                'Changelog' => \Illuminate\Support\Facades\Route::has('changelog.public') ? ['changelog.public', 'changelog.public'] : null,
            ]);
        @endphp

        <header class="border-b border-border bg-surface">
            {{-- One row on a wide screen. On a phone the links wrap onto a second
                 row under the brand: a brand, two links, the theme toggle and the
                 account controls do not fit across 375px. --}}
            <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-x-4 px-4 sm:px-6">
                <a href="{{ \Shipbytes\UiKit\Support\UiKit::homeUrl() }}" wire:navigate class="flex h-14 items-center gap-2">
                    @include('layouts.partials.brand-mark', ['markClass' => 'h-6 w-auto', 'badgeClass' => 'size-6'])
                    <span class="text-section font-semibold text-fg">{{ config('ui-kit.brand.name') }}</span>
                </a>

                @if ($shellLinks !== [])
                    <nav aria-label="Main" class="order-last -mx-2 flex w-full items-center gap-1 pb-2 sm:order-none sm:mx-0 sm:w-auto sm:pb-0">
                        @foreach ($shellLinks as $label => [$name, $pattern])
                            <x-ds::button
                                variant="ghost"
                                size="sm"
                                :href="route($name)"
                                wire:navigate
                                :aria-current="request()->routeIs($pattern) ? 'page' : null"
                            >{{ $label }}</x-ds::button>
                        @endforeach
                    </nav>
                @endif

                <div class="ml-auto flex h-14 items-center gap-1">
                    @if ($shellIsAdmin && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
                        <x-ds::button variant="ghost" size="sm" :href="route('admin.dashboard')" wire:navigate>
                            Admin
                        </x-ds::button>
                    @endif

                    <x-theme-toggle />

                    @auth
                        @if (\Illuminate\Support\Facades\Route::has('profile'))
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
                    @else
                        @if (\Illuminate\Support\Facades\Route::has('login'))
                            <x-ds::button variant="ghost" size="sm" :href="route('login')" wire:navigate>Sign in</x-ds::button>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('register'))
                            <x-ds::button size="sm" :href="route('register')" wire:navigate>Register</x-ds::button>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>

        @stack('scripts')
    </body>
</html>

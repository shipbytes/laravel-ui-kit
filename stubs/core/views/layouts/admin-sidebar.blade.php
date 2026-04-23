@php
    $nav = config('admin.nav', []);
    $badges = app(\Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class)->counts();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ moreSheetOpen: false }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin — {{ config('ui-kit.brand.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased">
        <div class="relative isolate flex min-h-svh w-full bg-white max-lg:flex-col lg:bg-zinc-100 dark:bg-zinc-950 dark:lg:bg-zinc-900">

            {{-- Desktop sidebar --}}
            <div
                :class="$store.sidebar.collapsed ? 'w-16' : 'w-64'"
                class="fixed inset-y-0 left-0 max-lg:hidden transition-[width] duration-200 ease-in-out bg-zinc-100 dark:bg-zinc-900"
            >
                <div class="h-full w-full p-2">
                    <nav class="flex h-full min-h-0 w-full flex-col bg-white dark:bg-zinc-800 rounded-xl">
                        {{-- Header --}}
                        <div
                            :class="$store.sidebar.collapsed ? 'flex-col items-center gap-1.5 py-3' : 'flex-row items-center justify-between gap-1 py-2'"
                            class="flex border-b border-zinc-950/5 dark:border-white/5 px-2"
                        >
                            <a href="{{ route(config('admin.route_name_prefix').'dashboard') }}" wire:navigate
                               :class="$store.sidebar.collapsed ? 'p-1' : 'px-1.5 py-1.5'"
                               class="flex items-center gap-2 rounded-lg hover:bg-zinc-950/5 dark:hover:bg-white/5 transition-colors">
                                <img src="{{ asset(config('ui-kit.brand.logo')) }}" alt="{{ config('ui-kit.brand.name') }}" class="size-6 shrink-0">
                                <span x-show="!$store.sidebar.collapsed" class="truncate text-sm font-semibold text-zinc-950 dark:text-zinc-100">Admin</span>
                            </a>
                            <button
                                @click="$store.sidebar.toggle()"
                                type="button"
                                :title="$store.sidebar.collapsed ? 'Expand' : 'Collapse'"
                                class="flex items-center justify-center size-7 rounded-md text-zinc-400 hover:text-zinc-600 hover:bg-zinc-950/5 dark:hover:bg-white/5 transition-colors shrink-0"
                            >
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <path d="M9 3v18"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Nav items --}}
                        <div class="flex flex-1 flex-col overflow-y-auto px-2 py-4 gap-1">
                            @foreach ($nav as $item)
                                @if (isset($item['section']))
                                    <div x-show="!$store.sidebar.collapsed" class="pt-3 pb-1 px-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                        {{ $item['section'] }}
                                    </div>
                                @else
                                    @php
                                        $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                                        $badgeCount = isset($item['badge']) ? ($badges[$item['badge']] ?? 0) : 0;
                                    @endphp
                                    <a
                                        href="{{ route($item['route']) }}"
                                        wire:navigate
                                        :class="$store.sidebar.collapsed ? 'size-8 p-2 justify-center' : 'w-full gap-3 px-2 py-2'"
                                        class="flex items-center rounded-lg text-left text-sm font-medium transition-colors {{ $active ? 'border border-zinc-200 bg-white text-zinc-950 shadow-sm dark:bg-zinc-700 dark:text-zinc-50 dark:border-zinc-600' : 'border border-transparent text-zinc-600 hover:bg-zinc-950/5 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-zinc-50' }}"
                                    >
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                            @include('layouts.partials.nav-icons', ['name' => $item['icon'] ?? 'grid'])
                                        </span>
                                        <span x-show="!$store.sidebar.collapsed" class="flex-1 truncate">{{ $item['label'] }}</span>
                                        @if ($badgeCount > 0)
                                            <span x-show="!$store.sidebar.collapsed" class="ml-auto inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">{{ $badgeCount }}</span>
                                        @endif
                                    </a>
                                @endif
                            @endforeach
                        </div>

                        {{-- Footer --}}
                        <div class="border-t border-zinc-950/5 dark:border-white/5 p-2">
                            <a href="{{ route(config('ui-kit.brand.home_route')) }}" wire:navigate
                               :class="$store.sidebar.collapsed ? 'size-8 p-2 justify-center' : 'w-full gap-3 px-2 py-2'"
                               class="flex items-center rounded-lg text-sm font-medium text-zinc-600 hover:bg-zinc-950/5 dark:text-zinc-300 dark:hover:bg-white/5">
                                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                <span x-show="!$store.sidebar.collapsed">Back to site</span>
                            </a>
                        </div>
                    </nav>
                </div>
            </div>

            {{-- Main content --}}
            <main
                :class="$store.sidebar.collapsed ? 'lg:pl-16' : 'lg:pl-64'"
                class="flex-1 transition-[padding] duration-200 ease-in-out"
            >
                <div class="lg:p-2 min-h-svh">
                    <div class="lg:rounded-xl lg:bg-white lg:shadow-sm lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-800 dark:lg:ring-white/5 p-4 sm:p-6 lg:p-8 min-h-full pb-20 lg:pb-8">
                        @stack('admin-header-extras')
                        {{ $slot }}
                    </div>
                </div>
            </main>

            {{-- Mobile bottom nav --}}
            <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white ring-1 ring-zinc-950/5 safe-area-bottom z-30 dark:bg-zinc-800 dark:ring-white/5">
                <div class="flex justify-around items-stretch h-14">
                    @foreach (array_slice(array_filter($nav, fn ($i) => ! isset($i['section'])), 0, 3) as $item)
                        @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                        <a href="{{ route($item['route']) }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 text-xs {{ $active ? 'text-indigo-600 font-semibold' : 'text-zinc-500' }}">
                            <span class="size-5">@include('layouts.partials.nav-icons', ['name' => $item['icon'] ?? 'grid'])</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <button @click="moreSheetOpen = true" class="flex flex-1 flex-col items-center justify-center gap-1 text-xs text-zinc-500">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                        More
                    </button>
                </div>
            </nav>

            {{-- Mobile "More" sheet --}}
            <div x-show="moreSheetOpen" x-cloak @click="moreSheetOpen = false" class="lg:hidden fixed inset-0 bg-zinc-950/40 z-40" x-transition.opacity></div>
            <div x-show="moreSheetOpen" x-cloak class="lg:hidden fixed bottom-0 inset-x-0 bg-white rounded-t-2xl z-50 p-4 safe-area-bottom dark:bg-zinc-800" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0">
                <div class="grid grid-cols-3 gap-3">
                    @foreach (array_filter($nav, fn ($i) => ! isset($i['section'])) as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <span class="size-6 text-zinc-500">@include('layouts.partials.nav-icons', ['name' => $item['icon'] ?? 'grid'])</span>
                            <span class="text-xs text-center text-zinc-700 dark:text-zinc-200">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>

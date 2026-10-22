@php
    $nav = config('admin.nav', []);
    $navLinks = array_values(array_filter($nav, fn ($i) => ! isset($i['section'])));
    $badges = app(\Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class)->counts();
    $isActive = fn (array $item) => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ moreSheetOpen: false }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin — {{ config('ui-kit.brand.name') }}</title>
        <x-ui-kit::head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="bg-surface-sunken text-fg font-sans antialiased">
        <div class="relative isolate flex min-h-svh w-full max-lg:flex-col">

            {{-- Desktop sidebar --}}
            <div
                :class="$store.sidebar.collapsed ? 'w-16' : 'w-64'"
                class="fixed inset-y-0 left-0 w-64 max-lg:hidden transition-[width] duration-200 ease-in-out"
            >
                <div class="h-full w-full p-2">
                    <nav aria-label="Admin" class="flex h-full min-h-0 w-full flex-col rounded-panel border border-border bg-surface">
                        {{-- Header --}}
                        <div
                            :class="$store.sidebar.collapsed ? 'flex-col items-center gap-1.5 py-3' : 'flex-row items-center justify-between gap-1 py-2'"
                            class="flex border-b border-divider px-2"
                        >
                            <a href="{{ route(config('admin.route_name_prefix').'dashboard') }}" wire:navigate
                               :class="$store.sidebar.collapsed ? 'p-1' : 'px-1.5 py-1.5'"
                               class="flex items-center gap-2 rounded-control transition-colors hover:bg-fg/5">
                                @include('layouts.partials.brand-mark', ['markClass' => 'size-6 shrink-0', 'badgeClass' => 'size-6'])
                                <span x-show="!$store.sidebar.collapsed" class="truncate text-section font-semibold text-fg">Admin</span>
                            </a>
                            <div :class="$store.sidebar.collapsed ? 'flex-col' : 'flex-row'" class="flex shrink-0 items-center gap-0.5">
                                <x-theme-toggle />
                                <x-ds::button
                                    variant="ghost"
                                    size="sm"
                                    iconOnly
                                    @click="$store.sidebar.toggle()"
                                    aria-label="Collapse sidebar"
                                    ::aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                                    ::title="$store.sidebar.collapsed ? 'Expand' : 'Collapse'"
                                >
                                    <x-ds::icon name="chevron-double-left" x-show="!$store.sidebar.collapsed" />
                                    <x-ds::icon name="chevron-double-right" x-show="$store.sidebar.collapsed" x-cloak />
                                </x-ds::button>
                            </div>
                        </div>

                        {{-- Nav items --}}
                        <div
                            :class="$store.sidebar.collapsed ? 'items-center px-1' : 'px-2'"
                            class="flex flex-1 flex-col gap-1 overflow-y-auto px-2 py-4"
                        >
                            @foreach ($nav as $item)
                                @if (isset($item['section']))
                                    <div x-show="!$store.sidebar.collapsed" class="px-2 pt-3 pb-1 text-overline uppercase text-fg-muted">
                                        {{ $item['section'] }}
                                    </div>
                                @else
                                    @php
                                        $active = $isActive($item);
                                        $badgeCount = isset($item['badge']) ? ($badges[$item['badge']] ?? 0) : 0;
                                    @endphp
                                    <x-ds::nav-item
                                        :href="route($item['route'])"
                                        :label="$item['label']"
                                        chip
                                        :active="$active"
                                        :badge="$badgeCount ?: null"
                                        collapsedWhen="$store.sidebar.collapsed"
                                        wire:navigate
                                    >
                                        <x-slot:icon><x-ds::icon :name="$item['icon'] ?? 'squares-2x2'" /></x-slot:icon>
                                    </x-ds::nav-item>
                                @endif
                            @endforeach
                        </div>

                        {{-- Footer --}}
                        <div
                            :class="$store.sidebar.collapsed ? 'items-center px-1' : 'px-2'"
                            class="flex flex-col gap-1 border-t border-divider px-2 py-2"
                        >
                            <x-ds::nav-item
                                :href="\Shipbytes\UiKit\Support\UiKit::homeUrl()"
                                label="Back to site"
                                collapsedWhen="$store.sidebar.collapsed"
                                wire:navigate
                            >
                                <x-slot:icon><x-ds::icon name="arrow-left" /></x-slot:icon>
                            </x-ds::nav-item>

                            {{-- Current user + logout --}}
                            <div :class="$store.sidebar.collapsed ? 'flex-col gap-1 py-1' : 'flex-row gap-2 px-2 py-1.5'"
                                 class="flex items-center">
                                <x-ds::avatar :name="auth()->user()->name ?? null" size="sm" :title="auth()->user()->name ?? ''" />
                                <div x-show="!$store.sidebar.collapsed" class="min-w-0 flex-1">
                                    <div class="truncate text-meta font-medium text-fg">{{ auth()->user()->name ?? '' }}</div>
                                    @if (Illuminate\Support\Facades\Route::has('profile'))
                                        <a href="{{ route('profile') }}" wire:navigate class="text-meta text-fg-muted hover:text-fg">Profile</a>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-ds::button type="submit" variant="ghost" size="sm" iconOnly aria-label="Log out" title="Log out">
                                        <x-ds::icon name="arrow-right-start-on-rectangle" />
                                    </x-ds::button>
                                </form>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            {{-- Main content --}}
            <main
                :class="$store.sidebar.collapsed ? 'lg:pl-16' : 'lg:pl-64'"
                class="flex-1 transition-[padding] duration-200 ease-in-out lg:pl-64"
            >
                <div class="lg:p-2">
                    {{-- Full-bleed on a phone, a framed panel beside the rail on a desktop.
                         pb-20 keeps the last row clear of the fixed bottom bar. --}}
                    <div class="min-h-svh bg-surface p-4 pb-20 sm:p-6 sm:pb-20 lg:min-h-[calc(100svh-1rem)] lg:rounded-panel lg:border lg:border-border lg:p-8 lg:pb-8">
                        @stack('admin-header-extras')
                        {{ $slot }}
                    </div>
                </div>
            </main>

            {{-- Phone: the first three nav entries plus "More" --}}
            <x-ds::bottom-nav>
                @foreach (array_slice($navLinks, 0, 3) as $item)
                    <x-ds::bottom-nav-item
                        :href="route($item['route'])"
                        :label="$item['label']"
                        :active="$isActive($item)"
                        wire:navigate
                    >
                        <x-slot:icon><x-ds::icon :name="$item['icon'] ?? 'squares-2x2'" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                @endforeach
                <x-ds::bottom-nav-item label="More" @click="moreSheetOpen = true">
                    <x-slot:icon><x-ds::icon name="ellipsis-horizontal" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
            </x-ds::bottom-nav>

            {{-- Phone: every nav entry. Each row closes the sheet on its way out,
                 because wire:navigate keeps <html>, and with it moreSheetOpen. --}}
            <x-ds::sheet open="moreSheetOpen" title="More">
                <div class="px-2 safe-area-bottom">
                    @foreach ($navLinks as $item)
                        <x-ds::sheet-item
                            :href="route($item['route'])"
                            :label="$item['label']"
                            wire:navigate
                            @click="moreSheetOpen = false"
                        >
                            <x-slot:icon><x-ds::icon :name="$item['icon'] ?? 'squares-2x2'" size="5" /></x-slot:icon>
                        </x-ds::sheet-item>
                    @endforeach
                </div>
            </x-ds::sheet>
        </div>
        @stack('scripts')
    </body>
</html>

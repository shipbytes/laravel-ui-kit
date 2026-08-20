{{--
    Usage:  <x-theme-toggle />
    Drop this into the admin-sidebar header (or any layout). It binds to the
    Alpine `theme` store provided by ui-kit.js.
--}}
<button
    type="button"
    x-data
    x-on:click="$store.theme.toggle()"
    :aria-label="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    {{ $attributes->merge(['class' => 'inline-flex size-8 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700']) }}
>
    <svg x-show="$store.theme.value !== 'dark'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </svg>
    <svg x-show="$store.theme.value === 'dark'" x-cloak class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
</button>

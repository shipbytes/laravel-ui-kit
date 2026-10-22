{{--
    Usage:  <x-theme-toggle />
    Drop this into the admin-sidebar header (or any layout). It binds to the
    Alpine `theme` store provided by ui-kit.js. A `class` you pass is merged
    onto the button, for layout only (margin, placement).

    The static aria-label is what a reader gets before Alpine boots; the bound
    one takes over from there.
--}}
<x-ds::button
    variant="ghost"
    size="sm"
    iconOnly
    x-data
    x-on:click="$store.theme.toggle()"
    aria-label="Toggle dark mode"
    ::aria-label="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    {{ $attributes }}
>
    <x-ds::icon name="moon" x-show="$store.theme.value !== 'dark'" />
    <x-ds::icon name="sun" x-show="$store.theme.value === 'dark'" x-cloak />
</x-ds::button>

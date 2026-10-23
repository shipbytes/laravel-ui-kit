/**
 * UI Kit Alpine directives and stores.
 *
 * Import once from resources/js/app.js:
 *   import './ui-kit';
 *
 * Requires Alpine to be loaded from Livewire (which happens automatically
 * when Livewire is present on the page).
 */

document.addEventListener('alpine:init', () => {
    // Sidebar collapse state persisted to localStorage.
    window.Alpine.store('sidebar', {
        collapsed: localStorage.getItem('ui-kit.sidebar.collapsed') === 'true',
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('ui-kit.sidebar.collapsed', this.collapsed ? 'true' : 'false');
        },
    });

    // Theme store — backs <x-theme-toggle /> and mirrors the no-flash snippet
    // in <x-ui-kit::head />, so first paint and the store always agree.
    //
    // Exactly one of `light` / `dark` sits on <html>. The design system's
    // tokens follow the OS preference whenever <html> carries NEITHER class,
    // so leaving `light` off would give a dark-OS user who chose light the
    // dark tokens under light-variant markup. With no stored choice the value
    // follows the OS; the toggle stores an explicit one.
    window.Alpine.store('theme', {
        value: localStorage.getItem('ui-kit.theme')
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        init() {
            this.apply();
        },
        toggle() {
            this.value = this.value === 'dark' ? 'light' : 'dark';
            localStorage.setItem('ui-kit.theme', this.value);
            this.apply();
        },
        apply() {
            const root = document.documentElement.classList;
            root.toggle('dark', this.value === 'dark');
            root.toggle('light', this.value !== 'dark');
        },
    });
});

// wire:navigate copies the next page's html element attributes over this one,
// and that markup carries neither theme class: the head snippet adds it at
// runtime and is not re-run on a swap. Without this, every page reached by
// wire:navigate (the 2FA challenge after login, any wire:navigate link)
// rendered light whatever the choice. onSwap runs before paint, so no flash.
document.addEventListener('livewire:navigating', (event) => {
    event.detail.onSwap(() => window.Alpine.store('theme').apply());
});

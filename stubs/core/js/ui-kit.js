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

    // Theme store — backs <x-theme-toggle /> and the no-flash snippet in
    // <x-ui-kit::head />.
    window.Alpine.store('theme', {
        value: localStorage.getItem('ui-kit.theme') || 'light',
        init() {
            this.apply();
        },
        toggle() {
            this.value = this.value === 'dark' ? 'light' : 'dark';
            localStorage.setItem('ui-kit.theme', this.value);
            this.apply();
        },
        apply() {
            document.documentElement.classList.toggle('dark', this.value === 'dark');
        },
    });
});

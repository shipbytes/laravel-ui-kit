# Laravel UI Kit

Admin panel + auth UI scaffolding for Laravel 10/11/12/13 with Livewire 3, Volt, Fortify, and Tailwind 3. Extracted from ResumeOpen.

## Status

- **v0.1 (unreleased)** — Core + all 9 optional modules are implemented. CI matrix runs L10/L11/L12 × PHP 8.1–8.4. Not yet smoke-tested on a fresh Laravel install.

## What you get

### Always installed (core)
- **Auth pages** — login, register, forgot/reset password, email verification, confirm password (Livewire Volt + Fortify)
- **Admin shell** — collapsible sidebar, mobile bottom nav, nav-config-driven from `config/admin.php`
- **Dashboard stub + Users CRUD**
- **Tailwind preset** — fonts (Inter/Poppins/Montserrat), brand palette, dark-mode class strategy
- **Alpine stores** — sidebar collapse (localStorage-persisted), theme toggle

### Optional modules
| Slug | What it adds | Composer deps |
|---|---|---|
| `admin-middleware` | Spatie Permissions wiring (falls back to `is_admin` boolean if skipped) | `spatie/laravel-permission:^6.0` |
| `support-tickets` | Admin ticket queue + replies (Mailables left to you) | — |
| `changelog` | Admin-authored changelog with public feed | `mews/purifier:^3.4` |
| `contacts` | Contact-form submission inbox | — |
| `analytics` | UTM tracking, GA4, PostHog (pick any combination) | — |
| `profile` | Self-service name/email/password/avatar + 2FA toggle | — (optional: `intervention/image`) |
| `impersonation` | Login-as-user with exit ribbon + button partial | `lab404/laravel-impersonate:^1.7` |
| `activity-log` | Spatie activity log + filterable admin viewer | `spatie/laravel-activitylog:^4.8` |
| `dark-mode` | `<x-theme-toggle />` component + no-flash inline snippet | — |

Every module prints a numbered **Next steps** checklist after `ui-kit:install-module` — we don't auto-patch your route files or `config/admin.php`.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- Livewire 3 + Volt 1.x

> ⚠️ **Laravel 10 is past its security window.** The package supports it for compatibility, but new projects should target L11+.

## Install

```bash
composer require shipbytes/laravel-ui-kit
php artisan ui-kit:install
```

The installer walks you through an interactive module picker. Run `php artisan ui-kit:install --modules=admin-middleware,profile` to skip the prompt.

### Finish wiring

1. Add the Tailwind preset:
   ```js
   // tailwind.config.js
   module.exports = {
       presets: [require('shipbytes/laravel-ui-kit/tailwind-preset')],
       content: [
           './resources/**/*.blade.php',
           './resources/**/*.js',
           './app/**/*.php',
       ],
   };
   ```
2. Import Alpine + CSS into your bundles:
   ```js
   // resources/js/app.js
   import './ui-kit';
   ```
   ```css
   /* resources/css/app.css */
   @import './ui-kit.css';
   ```
3. Load the admin routes. Add to `bootstrap/app.php` (L11+) or `RouteServiceProvider` (L10):
   ```php
   Route::middleware('web')->group(base_path('routes/admin.php'));
   Route::middleware('web')->group(base_path('routes/auth.php'));
   ```
4. `php artisan migrate && npm install && npm run dev`

## Configuration

### Brand

```php
// config/ui-kit.php
'brand' => [
    'name' => env('UI_KIT_BRAND_NAME', config('app.name')),
    'logo' => '/images/logo.png',
    'home_route' => 'dashboard',
],
```

### Sidebar navigation

```php
// config/admin.php
'nav' => [
    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
    ['section' => 'Support'],
    ['label' => 'Tickets', 'route' => 'admin.tickets.index', 'icon' => 'ticket', 'badge' => 'open_tickets'],
],
```

Each installed module appends its own entries.

### Sidebar badges

Bind your own resolver:

```php
$this->app->bind(
    \Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class,
    \App\Support\AdminBadgeResolver::class,
);
```

The resolver returns `['open_tickets' => 12, ...]` — keys match the `badge` field on nav items.

## Installing modules later

```bash
php artisan ui-kit:install-module support-tickets
php artisan ui-kit:install-module analytics --providers=utm,posthog
php artisan ui-kit:list-modules
```

## Module deep-dives

### `admin-middleware`
Ships `EnsureUserIsAdmin` (Spatie role check) + `IsAdminUser` trait + `AdminRoleSeeder`. Swap the fallback middleware in `config/admin.php`, `vendor:publish` Spatie, migrate, and assign the role.

### `support-tickets`
Admin-only queue (public form is yours to build). Search by name/email, filter by status/priority, inline replies. Mailables are intentionally omitted so you plug in your own notification flow.

### `changelog`
Admin CRUD + public feed helper. HTML sanitization via `mews/purifier`. Each entry has a status (`published`/`draft`) and a category.

### `contacts`
Inbox for a public contact form that writes to `contact_submissions`. When the `support-tickets` module is also installed, a **Copy to Ticket** button auto-appears — no config needed.

### `analytics`
Three providers, install any combo:
- **UTM** — middleware captures `?utm_*` → session, model + link-builder Livewire page.
- **GA4** — consent-gated `@include('partials.ga4')` loader.
- **PostHog** — consent-gated loader + Livewire→PostHog JS bridge (`$this->dispatch('posthog-capture', event: 'x', properties: [...])`).

Both GA4 and PostHog gate on `cookie_consent=accepted` — set that cookie from your consent banner.

### `profile`
Four Livewire/Volt cards under a single `ProfilePage`: update info + avatar, update password, 2FA (Fortify, auto-hidden if not installed), delete account. Ships `x-modal` and `x-action-message` components. Resizes avatars to 200×200 if `intervention/image` is installed, otherwise stores the raw upload.

### `impersonation`
Two Blade partials (`impersonation-banner`, `impersonation-button`) over `lab404/laravel-impersonate`. The package auto-registers routes; you just `@include` the banner in your layout and the button in the user detail view.

### `activity-log`
Paginated admin viewer over `spatie/laravel-activitylog`'s `activity_log` table. Filters: log stream, causer email, date range. Add the `LogsActivity` trait on your models per package README.

### `dark-mode`
Alpine `$store.theme` ships in core/`ui-kit.js`. Drop `<x-theme-toggle />` anywhere and inline the no-flash snippet before `</head>`. Every core view and every shipped module has `dark:` variants already.

## Laravel version caveats

- **L10:** Volt routes require explicit `Volt::mount()`. The service provider calls it automatically when it detects published Volt pages. EOL'd security support — bump to L11+ when you can.
- **L11:** middleware registration moved to `bootstrap/app.php`. Post-install notes call out the relevant `bootstrap/app.php` vs `Http/Kernel.php` snippet so you know where to drop in the new middleware.
- **L12:** current LTS-ish target — the default for new projects using this kit.
- **L13:** newly released (per PHP/Fortify/Spatie peer-dep readiness). CI runs 10/11/12 until the ecosystem catches up; bump `composer.json` locally if you want to try it early.

## Testing the package

```bash
composer install
vendor/bin/phpunit
```

Tests run against Orchestra Testbench. CI (`.github/workflows/tests.yml`) matrixes PHP 8.1–8.4 × Laravel 10/11/12.

## Credits

Extracted from [ResumeOpen](https://github.com/shipbytes/resumeopen)'s admin + auth layers.

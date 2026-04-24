# Laravel UI Kit

Admin panel + auth UI scaffolding for Laravel 10/11/12/13 with Livewire 3, Volt, Fortify, and Tailwind 3.

Core + 9 optional modules, installed interactively via `php artisan ui-kit:install`. CI covers PHP 8.1–8.4 × Laravel 10/11/12. See [CHANGELOG.md](CHANGELOG.md) for release notes.

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
- Node 18+ (for Vite / Tailwind build)

> ⚠️ **Laravel 10 is past its security window.** The package supports it for compatibility, but new projects should target L11+.

## Before you install — fresh vs. existing app

The kit is designed for **fresh Laravel installs** (no auth scaffolding yet). Running it on top of Breeze, Jetstream, or a custom auth setup will collide.

The installer does a **preflight check** for you — it reads your `composer.lock` for `laravel/breeze` / `laravel/jetstream` and scans for colliding file paths (`routes/auth.php`, `app/Livewire/Forms/LoginForm.php`, `resources/views/livewire/pages/auth/*`). Behaviour:

- **Jetstream detected** → aborts. Pass `--force` to override (not recommended).
- **Breeze detected** (or stray auth files) → warns, lists the collisions, and prompts you to confirm.
- Running `--no-interaction` with collisions present → aborts unless `--force` is set. Keeps CI safe.

### ✅ Do install on

- **A fresh `laravel new` project** with no auth starter. Zero conflicts, ~2 minutes to a working admin + auth UI.
- **An existing app that has no auth UI yet** (e.g. an API-only app you're now adding an admin panel to). You'll still want to uninstall any partial auth views from `resources/views/auth/` first.

### ⚠️ Avoid installing on top of

- **Breeze (any stack)** — collides on `routes/auth.php`, `app/Livewire/Forms/LoginForm.php` (Breeze Livewire), and `resources/views/livewire/pages/auth/*`. Without `--force` Laravel silently skips them, leaving you with Breeze's code running under this kit's layouts — usually broken. With `--force`, Breeze is overwritten and may leave orphaned files behind.
- **Jetstream** — heavy footprint (teams, API tokens, Sanctum-based auth) that this kit doesn't understand. Do not combine.
- **An app with customized Fortify views/actions** — your customizations will either be skipped or overwritten depending on `--force`.

If you *must* use the kit on top of an existing auth setup, remove the starter first:

```bash
# Breeze — no official uninstaller, remove by hand:
composer remove laravel/breeze
rm -rf app/Http/Controllers/Auth resources/views/auth routes/auth.php
rm -f app/Livewire/Forms/LoginForm.php app/Livewire/Actions/Logout.php
rm -rf resources/views/livewire/pages/auth resources/views/components/{input-error,input-label,primary-button,text-input}.blade.php

# Jetstream — no uninstaller, migration is significant. Start from a fresh app.
```

Then run the kit installer. Review the generated files before committing — merge anything you wanted to keep from your old setup by hand.

### What the installer publishes (so you can eyeball conflicts)

| Destination | From |
|---|---|
| `config/ui-kit.php`, `config/admin.php` | kit-specific, safe |
| `resources/views/layouts/*`, `components/auth-session-status.blade.php` | **collides with Breeze** |
| `resources/views/livewire/pages/auth/*` | **collides with Breeze Livewire** |
| `resources/views/livewire/admin/*` | kit-specific, safe |
| `app/Livewire/Admin/*`, `app/Livewire/Forms/LoginForm.php` | **`LoginForm` collides with Breeze Livewire** |
| `routes/auth.php` | **collides with Breeze** |
| `routes/admin.php` | kit-specific, safe |
| `resources/js/ui-kit.js`, `resources/css/ui-kit.css` | kit-specific, safe |
| `database/migrations/..._add_is_admin_to_users_table.php` | kit-specific, timestamped, safe |

## Install

```bash
composer require shipbytes/laravel-ui-kit
php artisan ui-kit:install
```

The installer walks you through an interactive module picker. Run `php artisan ui-kit:install --modules=admin-middleware,profile` to skip the prompt.

<details>
<summary><strong>Installing before a Packagist release (or straight from GitHub / a local path)</strong></summary>

Until a version is tagged and submitted to Packagist, or if you're hacking on the kit locally, point Composer at the source directly.

**From GitHub (VCS repository)** — good for tracking `main`:

```bash
composer config repositories.laravel-ui-kit vcs https://github.com/shipbytes/laravel-ui-kit
composer config minimum-stability dev
composer config prefer-stable true
composer require "shipbytes/laravel-ui-kit:dev-main"
```

**From a local checkout (path repository)** — good for contributors; symlinks the source into `vendor/` so edits are live:

```bash
composer config repositories.laravel-ui-kit path /absolute/path/to/laravel-ui-kit
composer require "shipbytes/laravel-ui-kit:*"
```

If symlinking causes trouble (e.g. WSL file-permission quirks), disable it:

```bash
composer config repositories.laravel-ui-kit '{"type":"path","url":"/absolute/path/to/laravel-ui-kit","options":{"symlink":false}}'
composer update shipbytes/laravel-ui-kit
```

</details>

### Finish wiring

The installer does the heavy lifting (publishes files, patches `config/fortify.php`, auto-loads the kit's routes from the service provider). You still need:

1. **Add the Tailwind preset** so your utility classes include the brand palette and dark-mode strategy.
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
2. **Import Alpine stores + CSS** into your existing Vite bundles.
   ```js
   // resources/js/app.js
   import './ui-kit';
   ```
   ```css
   /* resources/css/app.css */
   @import './ui-kit.css';
   ```
3. **Run migrations and build assets.**
   ```bash
   php artisan migrate
   npm install
   npm run dev    # or: npm run build
   ```
4. **Configure mail** (see [Mail (for auth emails)](#mail-for-auth-emails) below) so password-reset and email-verification links actually get delivered.

That's the whole happy path. You should be able to hit `/register`, `/login`, and `/admin` immediately.

> **What the installer handles for you**
> - Publishes `config/fortify.php` and flips `views` → `false`, so Fortify's default view routes don't collide with the kit's Volt pages. (Without this, `/register` 500s with "RegisterViewResponse is not instantiable".)
> - Auto-loads `routes/auth.php` and `routes/admin.php` from the service provider. **No `bootstrap/app.php` edit required.** If you'd rather wire them yourself, just delete the published route files and register them your own way.
> - If interactive prompts render blank on your terminal (Windows cmd, some WSL emulators), the installer falls back to Symfony Console's numbered-list prompts automatically. Override with `UI_KIT_PROMPTS_FALLBACK=0` (force fancy) or `=1` (force plain).

## Configuration

### Brand

```php
// config/ui-kit.php
'brand' => [
    'name' => env('UI_KIT_BRAND_NAME', config('app.name')),
    'logo' => env('UI_KIT_BRAND_LOGO', '/images/logo.png'),
    'home_route' => env('UI_KIT_HOME_ROUTE', 'home'),
],
```

Drop your logo PNG/SVG at `public/images/logo.png` (or override `UI_KIT_BRAND_LOGO` to point anywhere else). `home_route` is the route name used by the "back to site" link in the admin shell.

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

Each installed module's `Next steps` checklist tells you the exact nav entry to paste.

### Sidebar badges

Bind your own resolver so sidebar counters (e.g. "open tickets: 12") reflect your data:

```php
// In a service provider
$this->app->bind(
    \Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class,
    \App\Support\AdminBadgeResolver::class,
);
```

The resolver returns `['open_tickets' => 12, 'unread_contacts' => 3, ...]` — keys match the `badge` field on nav items.

## Environment & credentials

This section is a single place to see **every `.env` key** the kit can read, with links to where to generate the values. Only the **Mail** block is required for a production-ready install; everything else depends on which modules you enable.

### `.env` reference

```dotenv
# --- Branding (all optional; sensible defaults if unset) -----------------
UI_KIT_BRAND_NAME="Acme"
UI_KIT_BRAND_LOGO="/images/logo.png"
UI_KIT_HOME_ROUTE="home"

# --- Mail (required for password reset, email verification) --------------
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.example.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# --- Analytics module: GA4 (only if you installed analytics+ga4) ---------
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX

# --- Analytics module: PostHog (only if you installed analytics+posthog) -
POSTHOG_PUBLIC_KEY=phc_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
POSTHOG_HOST=https://us.i.posthog.com   # or https://eu.i.posthog.com
```

### Mail (for auth emails)

Fortify sends password-reset and email-verification messages through Laravel's mailer. Out of the box, `MAIL_MAILER=log` works for local dev (mail goes to `storage/logs/laravel.log`). For production, pick any supported driver — Mailgun, Postmark, SES, Resend, or SMTP. See the [Laravel mail docs](https://laravel.com/docs/mail) for driver-specific setup.

If you don't configure mail, the UI will appear to work but users will never receive verification or reset emails.

### GA4 (Google Analytics 4)

**1. Generate a Measurement ID.**
1. Go to [analytics.google.com](https://analytics.google.com).
2. Admin (gear icon, bottom-left) → **Create** → **Property** (or pick an existing one).
3. Inside the property: **Data streams** → **Add stream** → **Web** → enter your site URL.
4. Copy the **Measurement ID**. It looks like `G-XXXXXXXXXX`.

**2. Paste it into `.env`.**

```dotenv
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
```

**3. Register the config key** in `config/services.php` (add this once):

```php
'google' => [
    'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
],
```

**4. Include the loader** in your app layout, just before `</head>`:

```blade
@include('partials.ga4')
```

**5. Consent gating.** The loader only fires once a `cookie_consent=accepted` cookie is present. Set that cookie from your consent banner (or manually in dev via DevTools) — otherwise the GA4 script never runs. This is intentional so you're GDPR/CCPA-ready.

Verify it's working: open your site, accept the cookie banner, and watch **Realtime** in the GA4 UI. You should see yourself within ~30 seconds.

### PostHog

**1. Grab your Project API Key.**
1. Sign up / log in at [posthog.com](https://posthog.com) (or run self-hosted).
2. Pick the project you want to track into.
3. **Settings** (gear icon, bottom-left) → **Project** → **General** → copy **Project API Key**. It starts with `phc_…`.
4. Note your **host**: `https://us.i.posthog.com` for PostHog Cloud US, `https://eu.i.posthog.com` for EU, or your own URL for self-hosted.

**2. Paste into `.env`.**

```dotenv
POSTHOG_PUBLIC_KEY=phc_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
POSTHOG_HOST=https://us.i.posthog.com
```

**3. Register config keys** in `config/services.php`:

```php
'posthog' => [
    'public_key' => env('POSTHOG_PUBLIC_KEY'),
    'host'       => env('POSTHOG_HOST', 'https://us.i.posthog.com'),
],
```

**4. Include the loader** in your app layout, just before `</head>`:

```blade
@include('partials.posthog')
```

**5. Install the JS SDK + bridge** so server-side Livewire events can capture PostHog events:

```bash
npm install posthog-js
```

```js
// resources/js/app.js
import './posthog-bridge';
```

**6. Capture events from Livewire:**

```php
$this->dispatch('posthog-capture', event: 'ticket_replied', properties: [
    'ticket_id' => $ticket->id,
]);
```

**7. Consent gating.** Same as GA4 — the loader waits for `cookie_consent=accepted`. Verify in the PostHog **Live events** tab.

> ⚠️ Only use your **public** project key (`phc_…`). The personal / private API key should never land in frontend code.

### UTM tracking (analytics module, UTM provider)

No external service or key needed. Once you register the middleware, anyone who hits your site with `?utm_source=…&utm_medium=…&utm_campaign=…` on the URL gets the values stashed in their session (and attached to the User model on signup). The UTM Link Builder page (`/admin/analytics/utm`) generates tagged URLs for your campaigns.

## Installing modules later

```bash
php artisan ui-kit:install-module support-tickets
php artisan ui-kit:install-module analytics --providers=utm,posthog
php artisan ui-kit:list-modules
```

## Module deep-dives

### `admin-middleware`
Ships `EnsureUserIsAdmin` (Spatie role check) + `IsAdminUser` trait + `AdminRoleSeeder`. After install: publish Spatie config/migrations, migrate, seed the `admin` role, assign it to a user, and swap the middleware binding in `config/admin.php` from the fallback to `App\Http\Middleware\EnsureUserIsAdmin::class`.

### `support-tickets`
Admin-only queue (public form is yours to build). Search by name/email, filter by status/priority, inline replies. Mailables are intentionally omitted so you plug in your own notification flow.

### `changelog`
Admin CRUD + public feed helper. HTML sanitization via `mews/purifier`. Each entry has a status (`published`/`draft`) and a category.

### `contacts`
Inbox for a public contact form that writes to `contact_submissions`. When the `support-tickets` module is also installed, a **Copy to Ticket** button auto-appears — no config needed.

### `analytics`
Three providers — pick any combination at install time. See [Environment & credentials](#environment--credentials) above for the full GA4 / PostHog setup walkthroughs.

- **UTM** — middleware captures `?utm_*` → session, User model columns, and a Livewire-powered link builder at `/admin/analytics/utm`. No external service required.
- **GA4** — consent-gated `@include('partials.ga4')` loader. Needs `GOOGLE_ANALYTICS_ID`.
- **PostHog** — consent-gated loader + Livewire→PostHog JS bridge. Needs `POSTHOG_PUBLIC_KEY` (+ optional `POSTHOG_HOST`).

Both GA4 and PostHog loaders gate on `cookie_consent=accepted`. Set that cookie from your consent banner (or your tests).

### `profile`
Four Livewire/Volt cards under a single `ProfilePage`: update info + avatar, update password, 2FA (Fortify, auto-hidden if not installed), delete account. Ships `x-modal` and `x-action-message` components. Resizes avatars to 200×200 if `intervention/image` is installed, otherwise stores the raw upload. Don't forget `php artisan storage:link` so `/storage/avatars/...` is publicly reachable.

### `impersonation`
Two Blade partials (`impersonation-banner`, `impersonation-button`) over `lab404/laravel-impersonate`. The package auto-registers routes; you just `@include` the banner in your layout and the button in the user detail view. Requires `canImpersonate()` + `canBeImpersonated()` methods on your User model.

### `activity-log`
Paginated admin viewer over `spatie/laravel-activitylog`'s `activity_log` table. Filters: log stream, causer email, date range. Add the `LogsActivity` trait on your models per [Spatie's README](https://spatie.be/docs/laravel-activitylog).

### `dark-mode`
Alpine `$store.theme` ships in core/`ui-kit.js`. Drop `<x-theme-toggle />` anywhere and inline the no-flash snippet before `</head>`. Every core view and every shipped module has `dark:` variants already.

## Laravel version caveats

- **L10:** Volt routes require explicit `Volt::mount()`. The service provider calls it automatically when it detects published Volt pages. EOL'd security support — bump to L11+ when you can.
- **L11:** middleware registration moved to `bootstrap/app.php`. Post-install notes call out the relevant `bootstrap/app.php` vs `Http/Kernel.php` snippet so you know where to drop in the new middleware.
- **L12:** current LTS-ish target — the default for new projects using this kit.
- **L13:** newly released (per PHP/Fortify/Spatie peer-dep readiness). CI runs 10/11/12 until the ecosystem catches up; bump `composer.json` locally if you want to try it early.

## Troubleshooting

- **`/login` returns 500 with "Vite manifest not found"** — run `npm run dev` or `npm run build`. Vite must emit a manifest before Blade's `@vite` directive can resolve it.
- **Password reset / verification emails never arrive** — check `MAIL_*` in `.env`. In local dev, set `MAIL_MAILER=log` and tail `storage/logs/laravel.log`.
- **GA4 / PostHog not firing** — open DevTools → Application → Cookies and confirm `cookie_consent=accepted` is set. Both loaders are consent-gated by design.
- **Sidebar badges show 0 / blank** — bind your own `SidebarBadgeResolver`; the default returns an empty array.
- **"Unauthorized" on `/admin`** — either the default fallback middleware is rejecting you (it requires `$user->is_admin` to be truthy) or you installed `admin-middleware` and haven't assigned the `admin` role yet.

## Testing the package

```bash
composer install
vendor/bin/phpunit
```

Tests run against Orchestra Testbench. CI (`.github/workflows/tests.yml`) matrixes PHP 8.1–8.4 × Laravel 10/11/12.

## License

MIT — see [LICENSE](LICENSE).

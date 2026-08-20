# Laravel UI Kit

Admin panel + auth UI scaffolding for **Laravel 12+** with Livewire 3, Volt, Fortify, and **Tailwind CSS v4**.

Core + 7 optional modules, installed by a turnkey `php artisan ui-kit:install`. CI covers PHP 8.2–8.4 on Laravel 12 (plus an experimental Laravel 13 leg) and runs a full fresh-app install → build → `route:cache` → HTTP smoke on every push. See [CHANGELOG.md](CHANGELOG.md) for release notes.

## What you get

### Always installed (core)

- **Auth pages** — login, register, forgot/reset password, email verification, confirm password, and a **two-factor challenge** page (Livewire Volt + Fortify), all with dark-mode variants
- **Working 2FA** — the login flow stages a TOTP/recovery-code challenge for users who enabled two-factor auth (enable/confirm UI ships with the `profile` module)
- **Admin shell** — collapsible sidebar, mobile bottom nav, theme toggle, current-user footer with profile link + logout; nav is config-driven from `config/admin.php`
- **User shell** — a light authenticated layout (`layouts/user-shell`) used by user-facing pages like `/profile`
- **Dashboard stub + Users list/detail**
- **Tailwind v4 theme** — CSS-first `@theme` tokens (brand palette, Inter/Poppins/Montserrat font utilities) and a class-strategy `dark:` variant
- **Alpine stores** — sidebar collapse (localStorage-persisted), theme toggle with no-flash snippet
- **`ui-kit:doctor`** — a health check that verifies the install is fully wired

### Optional modules

| Slug | What it adds | Composer deps |
|---|---|---|
| `admin-middleware` | Spatie Permissions wiring (falls back to `is_admin` boolean if skipped) | `spatie/laravel-permission:^6.10` |
| `support-tickets` | Admin ticket queue + replies (Mailables left to you) | — |
| `changelog` | Admin-authored changelog with public feed helper | `mews/purifier:^3.4` |
| `contacts` | Contact-form submission inbox | — |
| `profile` | Self-service name/email/password/avatar + full 2FA management | — (optional: `intervention/image`) |
| `impersonation` | Login-as-user with exit ribbon + button partial | `lab404/laravel-impersonate:^1.7.5` |
| `activity-log` | Spatie activity log + filterable admin viewer | `spatie/laravel-activitylog:^4.10` |

Modules that ship models also ship **factories**, so `SupportTicket::factory()` works in your tests out of the box.

## Requirements

- PHP 8.2+
- Laravel 12 (Laravel 13 supported in composer constraints; CI leg is experimental)
- Livewire 3.6+ / Volt 1.7+
- Tailwind CSS v4 (the default in fresh Laravel 12 apps)
- Node 18+ (for the Vite build)

## Before you install — fresh vs. existing app

The kit is designed for **fresh Laravel installs** (no auth scaffolding yet). Running it on top of Breeze, Jetstream, or a custom auth setup will collide.

### Creating the fresh app — recommended choices

```bash
laravel new my-app        # or: composer create-project laravel/laravel my-app
```

When the Laravel installer prompts you:

| Prompt | Pick | Why |
| --- | --- | --- |
| Starter kit | **None** | The kit *is* the auth/admin scaffolding — every starter kit (Breeze-style Livewire, React, Vue) collides with it. |
| Frontend stack | **Blade** | Gives you plain Vite + Tailwind CSS v4, which the kit's `@theme` file plugs straight into. Livewire 3 + Volt arrive via Composer with the kit itself. |
| Testing framework | Pest or PHPUnit | No interaction with the kit — pick your preference. |
| Database | Any (the SQLite default is fine) | The kit's migrations run on anything Laravel supports. |
| Run `npm install` / migrations | Yes | The kit's Vite wiring expects `resources/css/app.css` and `resources/js/app.js` to exist, and its installer migrates on top of the base tables. |

> **Heads-up:** the stock Laravel welcome page shows a **Dashboard** button to
> logged-in users that links to `/dashboard` — a route neither Laravel nor the
> kit registers, so it 404s. The kit's panel lives at **`/admin`** (admin users
> only). Point that button at `/admin` or replace the welcome page after
> installing.

### The preflight check

The installer runs a **preflight check**: it reads `composer.lock` for `laravel/breeze` / `laravel/jetstream` and scans for colliding files (`routes/auth.php`, `app/Livewire/Forms/LoginForm.php`, auth page views). Behaviour:

- **Jetstream detected** → aborts. Pass `--force` to override (not recommended).
- **Breeze detected** (or stray auth files) → warns, lists the collisions, and prompts to confirm.
- **A previous kit install detected** (via the `ui-kit:managed` header) → switches to update mode and re-runs cleanly, including with `--no-interaction`.
- `--no-interaction` with foreign collisions present → aborts unless `--force` is set. Keeps CI safe.

The kit only auto-loads route files that carry its `// ui-kit:managed` header — a pre-existing `routes/auth.php` from Breeze or your own code is never hijacked or double-registered.

If you must install over Breeze, remove it first:

```bash
composer remove laravel/breeze
rm -rf app/Http/Controllers/Auth resources/views/auth routes/auth.php
rm -f app/Livewire/Forms/LoginForm.php app/Livewire/Actions/Logout.php
rm -rf resources/views/livewire/pages/auth resources/views/components/{input-error,input-label,primary-button,text-input}.blade.php
```

Jetstream has no clean migration path — start from a fresh app.

## Install

```bash
composer require shipbytes/laravel-ui-kit
php artisan ui-kit:install
```

The installer walks you through a module picker. Non-interactive variants:

```bash
php artisan ui-kit:install --modules=admin-middleware,profile
php artisan ui-kit:install --modules=all
```

<details>
<summary><strong>Installing straight from GitHub or a local path (contributors)</strong></summary>

**From GitHub (VCS repository):**

```bash
composer config repositories.laravel-ui-kit vcs https://github.com/shipbytes/laravel-ui-kit
composer config minimum-stability dev
composer config prefer-stable true
composer require "shipbytes/laravel-ui-kit:dev-master"
```

**From a local checkout (path repository)** — symlinks the source into `vendor/` so edits are live:

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

### What the installer automates

- Publishes every kit + dependency config, view, class, route file, and migration
- Patches `config/fortify.php` (`views=false`, `home=/`, passkeys feature off) and publishes Fortify's 2FA columns migration
- Patches `config/admin.php` nav and `routes/admin.php` / `routes/ui-kit-user.php` between `/* ui-kit:* */` markers — **idempotently**, and without wiping other modules' entries
- **Wires your Vite entrypoints**: adds `@import './ui-kit.css';` to `resources/css/app.css` and `import './ui-kit';` to `resources/js/app.js`
- Runs `vendor:publish` for every dependent package, one `php artisan migrate`, seeds the admin role, runs `storage:link`
- Generates `app/Models/Concerns/UiKitUser.php` bundling exactly the traits your chosen modules need (Spatie `HasRoles`, lab404 `Impersonate`, Fortify `TwoFactorAuthenticatable`) — **and applies it to `app/Models/User.php` automatically** (the patch is lint-checked and reverted if it would break the file; you only edit by hand if the installer says so)
- Auto-loads `routes/auth.php`, `routes/admin.php`, `routes/ui-kit-user.php` from the service provider — no `bootstrap/app.php` edit
- Registers sane `login` / `two-factor` rate limiters unless your app already defines them

### Finish wiring (the irreducible checklist)

1. **Set `.env` mail keys** (password reset / verification emails):

   ```dotenv
   MAIL_MAILER=log                # 'smtp'/'mailgun'/etc. for production
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

2. **Build assets, then make your first user admin** (register through the UI first):

   ```bash
   npm install && npm run dev
   php artisan ui-kit:make-admin you@example.com   # no email → promotes the first user
   ```

   `ui-kit:make-admin` assigns the Spatie `admin` role when the
   `admin-middleware` module is installed and sets the `is_admin` flag when the
   column exists — whichever mechanisms are present.

3. **Verify:** `php artisan ui-kit:doctor` prints a ✓/✗ table (published files, Fortify flags, Vite imports, trait applied, storage link, 2FA columns, duplicate route names, mail config).

> The `UiKitUser` trait used to be a manual step here — the installer now
> applies it to `app/Models/User.php` itself and only asks you to do it by
> hand if the patch fails (custom model location, unrecognized class layout).

If your app has its own master layout for public pages, drop the kit's head/banner components into it so dark-mode no-flash and the impersonation ribbon work there too:

```blade
<head>
    <x-ui-kit::head />
</head>
<body>
    <x-ui-kit::banners />
```

(The kit's own layouts — guest, admin, user shell — already include them.)

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

Drop a logo at `public/images/logo.png` (or point `UI_KIT_BRAND_LOGO` anywhere, including a full URL). Until a logo exists the layouts render an initial badge, so nothing looks broken on a fresh install. `home_route` names the route used for "back to site" links and post-login redirects — **when the route doesn't exist the kit falls back to `/`**, so fresh apps work without defining one.

### Tailwind theme

The design tokens live in `resources/css/ui-kit-theme.css` (Tailwind v4 `@theme`):

```css
@theme {
    --color-brand-500: #6366f1;   /* bg-brand-500, text-brand-600, … */
    --font-boldtext: 'Poppins', ui-sans-serif, system-ui, sans-serif;
}
```

Override any token in your own CSS after the import — last definition wins. Dark mode uses a `dark` class on `<html>` (toggled by `<x-theme-toggle />`, persisted to localStorage, no-flash on load).

### Sidebar navigation

```php
// config/admin.php
'nav' => [
    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
    ['section' => 'Support'],
    ['label' => 'Tickets', 'route' => 'admin.support.index', 'icon' => 'ticket', 'badge' => 'open_tickets'],
],
```

Modules append their own entries automatically between the `/* ui-kit:nav-* */` markers.

### Sidebar badges

Bind your own resolver so counters (e.g. "open tickets: 12") reflect your data:

```php
// In a service provider
$this->app->bind(
    \Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class,
    \App\Support\AdminBadgeResolver::class,
);
```

The resolver returns `['open_tickets' => 12, 'unread_contacts' => 3]` — keys match the `badge` field on nav items.

## Two-factor authentication

Fortify's `twoFactorAuthentication` feature is enabled by default. With the `profile` module installed (which adds `TwoFactorAuthenticatable` to the generated trait and ensures the columns exist), users get the full flow:

1. **Enable** on `/profile` → scan the QR code → (when Fortify's `confirm` option is on) enter a code to confirm → save recovery codes.
2. **Login** validates credentials first, then redirects 2FA users to `/two-factor-challenge` for a TOTP or recovery code. Both steps are rate limited.
3. **Disable / regenerate recovery codes** any time from the profile page.

Without the `profile` module (or without the trait applied), login simply never challenges — nothing breaks.

## Installing modules later

```bash
php artisan ui-kit:install-module support-tickets
php artisan ui-kit:install-module changelog contacts profile     # several at once
php artisan ui-kit:install-module changelog,contacts,profile     # commas work too
php artisan ui-kit:list-modules
php artisan ui-kit:doctor
```

A batch is validated up front — one unknown slug aborts the whole run before anything is installed. When a batch includes `admin-middleware`, `impersonation`, or `profile`, the `UiKitUser` trait is regenerated **and re-applied to your User model automatically** at the end of the run. (`ui-kit:install-modules` works as an alias.)

Re-running `ui-kit:install` is safe: it detects the existing install, keeps your files (pass `--force` to overwrite), and never duplicates nav entries, routes, or migrations.

## Module deep-dives

### `admin-middleware`
Ships `EnsureUserIsAdmin` (Spatie role check) + `AdminRoleSeeder`. The installer publishes Spatie's config/migrations, migrates, seeds the `admin`/`user` roles, and swaps the middleware in `config/admin.php` from the `is_admin` fallback to the role check. The `UiKitUser` trait (with `HasRoles`) is applied to your User model automatically; grant the role with `php artisan ui-kit:make-admin you@example.com`.

### `support-tickets`
Admin-only queue (the public form is yours to build). Search by name/email, filter by status/priority/category, inline replies, open-count badge. Status/priority changes are validated server-side. Mailables are intentionally omitted so you plug in your own notification flow.

### `changelog`
Admin CRUD + `ChangelogEntry::published()` scope for a public feed. HTML is sanitized via `mews/purifier`. For a public page, add a route rendering your own view over the `published()` scope (the module ships only the admin side).

### `contacts`
Inbox for a public contact form that writes to `contact_submissions`. Read/unread, archive, bulk actions, reply drafts (bring your own Mailable). When `support-tickets` is also installed, a **Copy to Ticket** button appears automatically.

### `profile`
Cards under `/profile` (rendered in the user shell, not the admin shell): update info + avatar, update password, two-factor authentication, delete account. Avatars are stored under a content-derived extension and resized to 200×200 when `intervention/image` is installed.

### `impersonation`
Two Blade partials over `lab404/laravel-impersonate`. `<x-ui-kit::banners />` shows the exit ribbon automatically; `@include('partials.impersonation-button', ['user' => $user])` renders the login-as button. The generated `UiKitUser` trait provides `canImpersonate()` / `canBeImpersonated()`.

### `activity-log`
Paginated admin viewer over `spatie/laravel-activitylog`. Filters: log stream, causer email, date range. Add the `LogsActivity` trait to models you want logged (see Spatie's docs).

## Environment reference

```dotenv
# --- Branding (all optional; sensible defaults) --------------------------
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
```

In local dev, `MAIL_MAILER=log` works — mail lands in `storage/logs/laravel.log`.

## Troubleshooting

Run `php artisan ui-kit:doctor` first — it catches the common ones and prints the fix.

- **`/login` returns 500 with "Vite manifest not found"** — run `npm run dev` or `npm run build`.
- **Password reset / verification emails never arrive** — check `MAIL_*` in `.env`.
- **"Unauthorized" on `/admin`** — the fallback middleware requires `$user->is_admin` to be truthy; with `admin-middleware` installed you need the `admin` role assigned.
- **Sidebar badges show 0 / blank** — bind your own `SidebarBadgeResolver`; the default returns an empty array.
- **`route:cache` fails with a duplicate name** — the doctor lists the duplicates; usually a route file was copied without its `ui-kit:managed` header and loaded twice by hand.
- **The welcome page's "Dashboard" button 404s** — that button is stock Laravel and links to `/dashboard`, which nothing registers. The kit's panel is at `/admin`; edit `resources/views/welcome.blade.php` to point there (or replace the page).

## Testing the package

```bash
composer install
vendor/bin/phpunit          # includes an end-to-end installer test + HTTP render tests
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

CI additionally builds a fresh Laravel 12 app, installs every module, builds assets, caches routes, runs the doctor, and smoke-tests the HTTP pages.

## License

MIT — see [LICENSE](LICENSE).

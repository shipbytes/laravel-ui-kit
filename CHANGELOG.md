# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Pre-1.0 release policy.** While the kit is in early iteration, the v0.1.0
> tag is rolling — fixes and improvements land under it rather than bumping
> the patch version. Pin to a commit if you need a frozen reference.

## [Unreleased]

## [0.1.0] - 2026-04-24

Initial public release. Contents updated in place during early iteration.

### Core
- **Core scaffold** installed by `php artisan ui-kit:install`: Livewire + Volt
  auth pages (login, register, forgot/reset password, email verification,
  confirm password), admin shell with collapsible sidebar and mobile bottom
  nav, dashboard stub, Users CRUD, Tailwind preset, Alpine stores for
  sidebar + theme.
- **Module installer** (`ui-kit:install-module {slug}`) + listing command
  (`ui-kit:list-modules`).
- **9 optional modules**:
  - `admin-middleware` — Spatie Permissions wiring with `is_admin` fallback.
  - `support-tickets` — admin ticket queue with replies and status filters.
  - `changelog` — admin-authored changelog entries with public feed helper.
  - `contacts` — inbox for public contact-form submissions, with
    auto-appearing "Copy to Ticket" button when `support-tickets` is present.
  - `analytics` — pluggable UTM / GA4 / PostHog providers, consent-gated.
  - `profile` — self-service name/email/password/avatar cards + 2FA toggle.
  - `impersonation` — login-as-user over `lab404/laravel-impersonate`.
  - `activity-log` — admin viewer over `spatie/laravel-activitylog`.
  - `dark-mode` — theme toggle component + no-flash snippet.
- **Sidebar badge contract** (`SidebarBadgeResolver`) so host apps can bind
  their own count resolvers.
- **CI matrix** covering PHP 8.1–8.4 × Laravel 10/11/12 with lint + phpunit.

### Installer safety
- **Preflight auth-scaffold detection** in `ui-kit:install` — aborts on
  Jetstream, warns + confirms on Breeze or stray auth files
  (`routes/auth.php`, `app/Livewire/Forms/LoginForm.php`,
  `resources/views/livewire/pages/auth/*`). Non-interactive runs abort
  unless `--force` is passed.
- **Module + provider pickers use plain STDIN** (numbered list +
  comma-separated input). Avoids Laravel Prompts' multiselect, which
  rendered invisibly on Windows cmd / Laragon / some WSL emulators. Other
  Prompts calls (`confirm`, `info`, `note`) auto-fall-back via
  `UI_KIT_PROMPTS_FALLBACK=0|1`.

### Turnkey installer (auto-patching, runtime wiring)
The post-install checklist drops from ~30 manual steps across 9 files to
**5 small steps** in 2 files (User model + master layout) plus an `.env`
and an `assignRole`. Everything else is automated.

- **Auto-patches `config/admin.php`** between `/* ui-kit:nav-start */` and
  `/* ui-kit:nav-end */` markers — modules' nav entries are merged in
  idempotently (dedup by route name).
- **Auto-patches `routes/admin.php`** between `/* ui-kit:admin-routes-* */`
  markers — modules' admin routes are appended idempotently.
- **`admin-middleware`** auto-swaps the middleware in `config/admin.php`
  from the fallback to `App\Http\Middleware\EnsureUserIsAdmin::class`.
- **Auto-Fortify configuration** — publishes `config/fortify.php` and
  flips `views` to `false` so Fortify's default view routes don't collide
  with the kit's Volt pages. Prevents `/register` 500 with
  "RegisterViewResponse is not instantiable" on fresh installs.
- **New `routes/ui-kit-user.php`** auto-loaded by the service provider.
  Houses authenticated user-side routes (e.g. `/profile`). No need to
  edit `routes/web.php`.
- **Auto-loaded routes** — `UiKitServiceProvider` wires `routes/auth.php`,
  `routes/admin.php`, and `routes/ui-kit-user.php` into the host app
  automatically when they exist. No `bootstrap/app.php` edit needed.
- **Generated `App\Models\Concerns\UiKitUser` trait** that bundles
  Spatie `HasRoles` + lab404 `Impersonate` + `canImpersonate` /
  `canBeImpersonated`, conditionally based on which modules you actually
  installed. You add `use UiKitUser;` to your User model — one line
  instead of two traits + two methods.
- **`<x-ui-kit::head />`** Blade component bundling dark-mode no-flash +
  GA4 + PostHog `@includes`. One tag in your `<head>`.
- **`<x-ui-kit::banners />`** Blade component for the impersonation
  ribbon (and any future kit-shipped banners).
- **Auto-installed dependencies** via `vendor:publish` for every kit
  module that needs it: Spatie Permission, mews/purifier,
  lab404/laravel-impersonate, Spatie Activitylog. Plus
  `php artisan storage:link` for the profile module and
  `npm install posthog-js` for the analytics:posthog provider.
- **Auto-runs** `php artisan migrate` (one shot, covering kit + module +
  Spatie published migrations) and `db:seed AdminRoleSeeder` once at the
  end of install.
- **Runtime `services.php` config** — `UiKitServiceProvider` reads
  `GOOGLE_ANALYTICS_ID` / `POSTHOG_PUBLIC_KEY` / `POSTHOG_HOST` from
  `.env` and seeds `services.google.*` + `services.posthog.*` at boot.
  No `config/services.php` edit needed.
- **Runtime UTM middleware registration** — when `analytics:utm` is
  installed, the SP pushes `CaptureUtmParameters` to the `web` middleware
  group automatically. No `bootstrap/app.php` edit.

### Module metadata
- `ModuleRegistry` modules declare structured fields (`admin_routes`,
  `admin_nav`, `user_routes`, `admin_middleware_swap`, `artisan_publish`,
  `artisan_seed`, `storage_link`, `npm`, plus per-provider `providers_meta`)
  instead of free-text post-install notes for everything. Residual
  `post_install_notes` remain for genuinely-manual steps (e.g. assigning
  the admin role).
- Final installer output is one consolidated summary instead of a
  per-module checklist dump. Suppressed when `--from-parent` runs the
  module command on behalf of `ui-kit:install`.

### Tests
- **Idempotency tests** under `tests/Feature/PatchingIdempotencyTest.php`
  assert nav / admin-routes / user-routes / middleware-swap don't
  duplicate on re-run.

### Known limitations
- Laravel 13 is not yet in CI — peer-dep readiness across Fortify / Spatie
  is still landing. The kit is expected to work on L13; bump `composer.json`
  locally to try.

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

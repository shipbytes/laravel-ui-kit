# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2026-04-24

Turnkey installer pass. The post-install checklist drops from ~30 manual
steps across 9 files to **5 small steps** in 2 files (User model + master
layout) plus an `.env` and an `assignRole`. Everything else is automated.

### Added
- **Auto-patching of `config/admin.php`** between `/* ui-kit:nav-start */`
  and `/* ui-kit:nav-end */` markers — modules' nav entries are merged in
  idempotently (dedup by route name).
- **Auto-patching of `routes/admin.php`** between `/* ui-kit:admin-routes-*
  */` markers — modules' admin routes are appended idempotently.
- **`admin-middleware` swaps the middleware** in `config/admin.php`
  automatically when installed.
- **New `routes/ui-kit-user.php`** auto-loaded by the service provider.
  Houses authenticated user-side routes (e.g. `/profile`). No need to
  edit `routes/web.php`.
- **Generated `App\Models\Concerns\UiKitUser` trait** that bundles
  Spatie `HasRoles` + lab404 `Impersonate` + `canImpersonate` /
  `canBeImpersonated` based on which modules you actually installed.
  You add `use UiKitUser;` to your User model — one line instead of
  two traits + two methods.
- **`<x-ui-kit::head />`** Blade component bundling dark-mode no-flash
  + GA4 + PostHog `@includes`. One tag in your `<head>`.
- **`<x-ui-kit::banners />`** Blade component for the impersonation
  ribbon (and any future kit-shipped banners).
- **Auto-installed dependencies** via `vendor:publish` for every kit
  module that needs it: Spatie Permission, mews/purifier,
  lab404/laravel-impersonate, Spatie Activitylog. Plus
  `php artisan storage:link` for the profile module and
  `npm install posthog-js` for the analytics:posthog provider.
- **Auto-runs** `php artisan migrate` (one shot, covering kit + module
  + Spatie published migrations) and `db:seed AdminRoleSeeder` once
  at the end of install.
- **Runtime `services.php` config** — `UiKitServiceProvider` reads
  `GOOGLE_ANALYTICS_ID` / `POSTHOG_PUBLIC_KEY` / `POSTHOG_HOST` from
  `.env` and seeds `services.google.*` + `services.posthog.*` at
  boot. No more editing `config/services.php`.
- **Runtime UTM middleware registration** — when `analytics:utm` is
  installed, the SP pushes `CaptureUtmParameters` to the `web` middleware
  group automatically. No `bootstrap/app.php` edit.
- **Idempotency tests** under `tests/Feature/PatchingIdempotencyTest.php`
  asserting nav / admin-routes / user-routes / middleware-swap don't
  duplicate on re-run.

### Changed
- `ModuleRegistry` modules now declare structured fields (`admin_routes`,
  `admin_nav`, `user_routes`, `admin_middleware_swap`, `artisan_publish`,
  `artisan_seed`, `storage_link`, `npm`, plus per-provider `providers_meta`)
  instead of free-text post-install notes for everything. Residual
  `post_install_notes` remain for genuinely-manual steps (e.g. assigning
  the admin role).
- Final installer output is now one consolidated summary instead of a
  per-module checklist dump. Suppressed when `--from-parent` runs the
  module command on behalf of `ui-kit:install`.
- Module + provider pickers use plain STDIN (numbered list +
  comma-separated input) instead of Laravel Prompts' multiselect, which
  rendered invisibly on Windows cmd / Laragon.

### Removed
- The `IsAdminUser` stub trait at
  `stubs/modules/admin-middleware/Models/Concerns/IsAdminUser.php`.
  Replaced by the dynamically-generated `UiKitUser` trait. **Breaking
  change** for anyone who installed v0.1.0 days ago — re-import as
  `App\Models\Concerns\UiKitUser`.

## [0.1.0] - 2026-04-24

Initial public release.

### Added
- **Core scaffold** installed by `php artisan ui-kit:install`: Livewire + Volt
  auth pages (login, register, forgot/reset password, email verification,
  confirm password), admin shell with collapsible sidebar and mobile bottom
  nav, dashboard stub, Users CRUD, Tailwind preset, Alpine stores for
  sidebar + theme.
- **Interactive installer** backed by Laravel Prompts — module picker,
  provider picker for analytics, dependency detection.
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
- **Preflight auth-scaffold detection** in `ui-kit:install` — aborts on
  Jetstream, warns + confirms on Breeze or stray auth files
  (`routes/auth.php`, `app/Livewire/Forms/LoginForm.php`,
  `resources/views/livewire/pages/auth/*`). Non-interactive runs abort
  unless `--force` is passed.
- **Auto-Fortify configuration** — the installer publishes `config/fortify.php`
  (if absent) and flips `views` to `false`, so Fortify's default view routes
  don't collide with the kit's Volt pages. Prevents a `/register` 500 with
  "Target [Laravel\Fortify\Contracts\RegisterViewResponse] is not instantiable"
  on fresh installs.
- **Auto-loaded routes** — `UiKitServiceProvider` wires `routes/auth.php`
  and `routes/admin.php` into the host app automatically when they exist.
  Consumers don't need to edit `bootstrap/app.php`. Delete either published
  route file to opt out.
- **Prompts fallback** — the installer detects Windows cmd and WSL and
  switches Laravel Prompts to its Symfony-Console fallback (numbered list
  instead of alt-screen rendering) so multiselect pickers don't appear
  invisible on terminals that mis-handle the fancy rendering. Override with
  `UI_KIT_PROMPTS_FALLBACK=0|1`.
- **CI matrix** covering PHP 8.1–8.4 × Laravel 10/11/12 with lint + phpunit.

### Known limitations
- Laravel 13 is not yet in CI — peer-dep readiness across Fortify / Spatie
  is still landing. The kit is expected to work on L13; bump `composer.json`
  locally to try.

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

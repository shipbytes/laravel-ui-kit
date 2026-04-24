# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **Module + provider pickers now use plain STDIN** instead of Laravel Prompts'
  multiselect. The Prompts fallback wasn't reliable on Windows cmd / Laragon
  (rendered invisibly even with `fallbackWhen(true)`), so we took it out of the
  critical path entirely. You now get a numbered list and type comma-separated
  numbers (or `all`). Works on every terminal. `UI_KIT_PROMPTS_FALLBACK` still
  controls the other Prompts calls (`confirm`, `info`, `note`).

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

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

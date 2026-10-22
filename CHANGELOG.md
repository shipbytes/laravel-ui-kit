# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> Published versions are immutable (Packagist enforces this): every
> publishable state gets a fresh tag. Pre-1.0, breaking changes bump the
> minor version.

## [Unreleased]

The start of 0.4.0: the kit moves onto the Shipbytes design system. This
first step covers the layouts and the shared components; the auth, profile and
admin screens inside them still use the 0.3 styling and are restyled before
0.4.0 ships.

### Changed

- **Breaking: built on the Shipbytes design system (`shipbytes/blade-ui`).**
  The kit no longer carries a palette, fonts or components of its own. The
  admin shell, the user shell, the guest layout, the theme toggle, the session
  status message and the impersonation ribbon and button now use `ds::`
  components and the design system's semantic tokens (`bg-surface`,
  `text-fg-muted`, `border-border`, …), which switch for dark mode on their
  own. The phone's "More" menu is the design system's bottom sheet. The
  accent colour is the design system's blue, and its Inter is the one font
  the layouts load.
  Composer installs `shipbytes/blade-ui` with the kit. The
  published `resources/css/ui-kit.css` imports its `tokens.css` and `theme.css`
  and adds the `@source` line Tailwind needs to see its components, so
  `app.css` still imports only `./ui-kit.css`. To upgrade a 0.3 app:
  1. `composer require shipbytes/laravel-ui-kit:^0.4 -W`.
  2. `php artisan vendor:publish --tag=ui-kit-assets --force` to replace
     `resources/css/ui-kit.css` and `resources/js/ui-kit.js`, and
     `php artisan vendor:publish --tag=ui-kit-views --force` to replace the
     layouts (back up any of these you edited first).
  3. Delete `resources/css/ui-kit-theme.css`.
  4. Move any token overrides you made there into `resources/css/app.css`,
     after the `ui-kit.css` import, as `--ds-*` variables, e.g.
     `:root { --ds-accent: … }`. `brand-*`, `font-boldtext` and
     `font-booktext` classes in your own views no longer exist.
  5. In `config/admin.php`, rename every nav `icon` to a
     [Heroicons v2](https://heroicons.com) outline name. This includes the
     kit's own entries, which the installer does not rewrite once they exist:
     `grid` → `squares-2x2`, `note` → `document-text`, `mail` → `envelope`,
     `chart` → `chart-bar`, `history` → `clock`; `users`, `ticket`, `clock`
     and `user-circle` stay as they are.
  6. `php artisan ui-kit:doctor`. Every row should pass.
- **Nav icons are Heroicons v2 names**, rendered through `ds::icon`, so any of
  the outline icons on heroicons.com works in `config/admin.php`.
  The kit's own entries are now `squares-2x2` (Dashboard), `users`, `ticket`,
  `document-text` (Changelog), `envelope` (Contacts) and `clock` (Activity).
- **The theme follows the OS until the user picks one.** With nothing stored,
  the theme store and the no-flash snippet now take the initial theme from
  `prefers-color-scheme`. Before, it was always light. They also set exactly
  one of `light` / `dark` on `<html>`: the design system's tokens follow the
  OS whenever neither class is present, so a dark-OS user who chose light
  would otherwise have got dark tokens.

### Added

- `ui-kit:doctor` checks for the design system: `design system installed`,
  `ui-kit.css imports the design system`, `no leftover ui-kit-theme.css` and
  `nav icons are Heroicons` (it lists each `label => icon` that doesn't
  resolve). Each failing row says how to fix it.
- `StubStyleTest`: the suite now fails when a published view uses a raw
  palette class, a `dark:` variant, an inline `<svg>` or a raw form control
  instead of the design system. Screens that haven't been moved over yet are
  on an explicit allowlist that the rest of the 0.4.0 work empties.

### Removed

- `resources/css/ui-kit-theme.css` and its `@theme` block: the `brand-*`
  palette and the Poppins (`font-boldtext`) and Montserrat (`font-booktext`)
  font utilities. The layouts load only Inter.
- The kit's own `@custom-variant dark`, `[x-cloak]` and `.safe-area-bottom`
  rules. The design system's `theme.css` provides all three.
- The `layouts/partials/nav-icons` partial and its hand-drawn icons.

## [0.3.0] - 2026-10-22

### Changed

- **Breaking: Livewire 4.** The kit now requires `livewire/livewire: ^4.4` and
  no longer uses Volt. The eleven auth and profile pages stay single-file
  components at the same paths under `resources/views/livewire/…` (no ⚡
  prefix), now extending `Livewire\Component`; `routes/auth.php` registers them
  with `Route::livewire()` instead of `Volt::route()`, with unchanged URLs and
  route names. Nothing changes visually. To upgrade a 0.2 app:
  1. If the app's own `composer.json` requires `livewire/volt` (it normally
     arrives through the kit), run `composer remove livewire/volt --no-update`.
  2. `composer require shipbytes/laravel-ui-kit:^0.3 -W` — a `^0.2`
     constraint never picks up 0.3, and `-W` lets Composer move Livewire 3 to 4
     and drop Volt.
  3. In `routes/auth.php`, replace `Volt::route(` with `Route::livewire(` and
     delete the `use Livewire\Volt\Volt;` line.
  4. In `resources/views/livewire/**`, replace `use Livewire\Volt\Component;`
     with `use Livewire\Component;`.
  5. If the app has a `VoltServiceProvider`, remove it from
     `bootstrap/providers.php` and delete `app/Providers/VoltServiceProvider.php`.
  6. `php artisan ui-kit:doctor` — every row should pass.

  Until steps 3–5 are done the app keeps working through the shim below.

### Added

- `ui-kit:doctor` checks for the Livewire 4 move: `Livewire 4 installed`,
  `routes/auth.php uses Route::livewire`, `views free of Volt imports` (lists
  the views under `resources/views/livewire/` that still import Volt) and
  `no Volt service provider`, each with the repair step as its hint.
- A transition shim for apps upgrading from 0.2: when Volt is not installed,
  the kit aliases `Livewire\Volt\Volt` (`route()`, `mount()`) and
  `Livewire\Volt\Component` onto their Livewire 4 equivalents, so an
  unmigrated `routes/auth.php` and its views keep working (and artisan keeps
  booting) until the steps above are done. It will be removed in 1.0.

### Removed

- The `livewire/volt` dependency.

## [0.2.2] - 2026-10-22

### Added

- **Documentation screenshots.** `npm run screenshots` (scripts/screenshots.mjs)
  builds a fresh Laravel 12 app with every module, seeds it deterministically
  and photographs each screen in light and dark mode into `docs/images/`,
  which shipbytes.dev copies at build time.

### Fixed

- **The impersonation module registers its routes.** `lab404/laravel-impersonate`
  ships `impersonate` and `impersonate.leave` as a router macro the app has to
  call; the module now injects `Route::impersonate();` into
  `routes/ui-kit-user.php` (web + auth) at install, so the partials no longer
  fail with `Route [impersonate] not defined` on a fresh install. `ui-kit:doctor`
  gained an `impersonation routes registered` check. Apps that added the macro
  to `routes/web.php` by hand should remove that line after re-running
  `ui-kit:install-module impersonation`, or the doctor reports duplicate names.
- `ContactSubmission` now uses `HasFactory`, so `ContactSubmission::factory()`
  works as the README promised.

## [0.2.1] - 2026-08-20

### Added

- **`ui-kit:make-admin {email?}`** — promotes a user to admin from the CLI:
  assigns the Spatie `admin` role when the `admin-middleware` module is
  installed and sets the `is_admin` flag when the column exists. No email
  argument → the first user. Replaces the tinker one-liners in the docs.
- **`ui-kit:install-module` accepts multiple slugs** — space- or
  comma-separated (`ui-kit:install-module changelog contacts profile`), with
  `ui-kit:install-modules` as an alias. The batch is validated before
  anything installs; one unknown slug aborts the whole run.

### Changed

- **The `UiKitUser` trait is applied to `app/Models/User.php` automatically**
  by `ui-kit:install` and standalone `ui-kit:install-module` runs — no more
  manual `use UiKitUser;` edit. The patch is idempotent and lint-checked
  (`php -l`); on any failure the file is reverted and the manual instruction
  is printed instead. The fresh-app CI job now asserts the auto-application
  instead of `sed`-ing the trait in.

## [0.2.0] - 2026-08-20

**Scope reset + audit-fix release.** A full-codebase audit found v0.1.0 had
never survived a real end-to-end install; this release narrows the kit to a
fresh-Laravel-12 turnkey experience and fixes everything the audit surfaced.

### Breaking — scope reset

- **Laravel 12+ only.** Constraints are now `php: ^8.2`,
  `illuminate/*: ^12 || ^13`, `laravel/fortify: ^1.25`,
  `laravel/prompts: ^0.3`, `livewire/livewire: ^3.6`, `livewire/volt: ^1.7`,
  `propaganistas/laravel-disposable-email: ^2.4`. Laravel 10/11 support
  removed.
- **Analytics module removed** (UTM tracking, GA4, PostHog) along with the
  provider-picker machinery, npm-install deferral, and the runtime
  services-config / UTM-middleware wiring.
- **Tailwind CSS v4 native.** `tailwind-preset.js` is gone; the kit ships a
  CSS-first `@theme` token file (`ui-kit-theme.css`) plus a
  `@custom-variant` binding `dark:` to the kit's class strategy. The
  `@tailwindcss/forms` dependency is gone (native controls styled via
  `accent-color`).
- **dark-mode module folded into core** — `<x-theme-toggle />` ships in core
  and the admin shell header includes it.
- **Socialite feature flag + social-buttons partial removed** (they pointed
  at routes that never existed).

### Fixed (full-codebase audit)

- Fresh installs no longer 500 on every core page: `route('home')` calls were
  replaced by `UiKit::homeUrl()`, which falls back to `/` when the configured
  home route doesn't exist (guest layout, admin sidebar, all auth redirects,
  email verification, profile).
- The generated `UiKitUser` trait is now written **in the same process** that
  installs the modules (installed-module state syncs to the runtime config).
- `markInstalled()` no longer round-trips `config/ui-kit.php` through
  `var_export` — `env()` defaults and comments survive; only the slug list
  between `/* ui-kit:modules-* */` markers is patched.
- The service provider only auto-loads route files carrying the
  `// ui-kit:managed` header — pre-existing Breeze or hand-rolled
  `routes/auth.php` files are never double-registered (this used to break
  `route:cache` the moment the package was required).
- `patchAdminNav` preserved only the newest module's entries — installing a
  second nav-declaring module wiped the first one's sidebar entry. Fixed and
  regression-tested.
- Deferred install commands (storage:link, seeders, vendor publishes) lived
  in **trait statics**, which PHP copies per using class — module-registered
  deferrals never reached the parent installer's drain, so `storage:link`
  and `AdminRoleSeeder` silently never ran on `ui-kit:install`. Queue moved
  to a shared `InstallQueue` class.
- Re-running `ui-kit:install` no longer trips the Breeze-collision preflight
  on the kit's own files: a kit-managed install is detected and the run
  switches to update mode (non-interactive re-runs now succeed).
- `config/fortify.php` patching extended: `views=false` (as before), plus
  `home => /` (Fortify's `/home` default doesn't exist on fresh apps) and
  the passkeys feature commented out (the kit ships no passkey UI).
- The kit's `verification.verify` route yields to Fortify's when the
  `emailVerification` feature is enabled (duplicate names broke
  `route:cache`).
- The core `is_admin` migration publish reuses an existing filename instead
  of stamping a new duplicate on every re-install; Fortify's (unguarded)
  2FA migration publish is glob-guarded the same way.
- Avatar uploads derive the file extension from the actual MIME type instead
  of the client filename (a JPEG/PHP polyglot could previously land as
  `avatars/1.php` on the public disk).
- Support ticket status/priority updates are validated against allowlists.
- `--modules=all` works; unknown module slugs abort with exit 1 instead of
  silently continuing.
- `composer` is resolved via `ExecutableFinder` (Windows `composer.bat`).
- The activity-log nav icon (`clock`) actually exists; Poppins/Montserrat
  fonts referenced by the theme are actually loaded.
- Install tail commands (vendor:publish, migrate, db:seed, storage:link)
  run in a fresh `php artisan` subprocess when the run composer-required
  new packages — the running process's autoloader can't see them, which
  silently skipped Spatie's publishes and crashed `AdminRoleSeeder`.
  Caught by the new fresh-app CI job on its first run.

### Added

- **Working two-factor authentication end to end**: Fortify's 2FA columns
  are published and migrated by the installer; the generated `UiKitUser`
  trait bundles `TwoFactorAuthenticatable` when the profile module is
  installed; the profile 2FA card gates on
  `Features::canManageTwoFactorAuthentication()` and supports Fortify's
  confirm flow (enable → QR → confirm code → recovery codes) via Fortify's
  own actions; `LoginForm` stages a challenge and the new
  `/two-factor-challenge` Volt page (TOTP + recovery code, rate limited)
  completes authentication.
- **`ui-kit:doctor`** — health check with fix hints: published files,
  managed route headers, Fortify flags, Vite imports, trait applied,
  storage link, 2FA columns, duplicate route names, mail config.
- **`layouts/user-shell.blade.php`** — light authenticated layout (brand
  bar, admin link for admins, theme toggle, logout); `/profile` renders
  there instead of inside the admin shell.
- **Logout everywhere it was missing**: admin sidebar footer gains a
  current-user row with profile link + logout (posting to Fortify's
  always-registered `logout` route).
- **Vite auto-wiring**: the installer adds the kit's CSS/JS imports to
  `resources/css/app.css` / `resources/js/app.js` idempotently.
- **Rate limiting**: registration (10/hour/IP), the 2FA challenge, and
  defensive `login` / `two-factor` named limiters for Fortify's own POST
  endpoints (which referenced limiters no fresh app defines).
- **Dark mode across the guest/auth pages** (previously admin-only), with
  the no-flash snippet included by every kit layout.
- **Model factories** for SupportTicket, TicketReply, ChangelogEntry,
  ContactSubmission.
- **Brand-mark fallback** — layouts render an initial badge until a logo
  file exists.
- **Tooling**: Pint + Larastan (level 5) clean and enforced in CI; a
  fresh-Laravel-12 end-to-end CI job (path-repo install, `--modules=all`,
  npm build, `route:cache`, doctor, HTTP smoke); an in-repo end-to-end
  installer test plus HTTP render tests for the published pages.

### Core (unchanged highlights)

- `ui-kit:install` / `ui-kit:install-module {slug}` / `ui-kit:list-modules`
  with plain-STDIN pickers (reliable on Windows/WSL terminals).
- 7 optional modules: admin-middleware, support-tickets, changelog,
  contacts, profile, impersonation, activity-log.
- Marker-based idempotent patching of `config/admin.php`,
  `routes/admin.php`, `routes/ui-kit-user.php`.
- `SidebarBadgeResolver` contract for host-app badge counts.

## [0.1.0] - 2026-04-24

Initial public release: core scaffold (Volt auth pages, admin shell,
dashboard stub, users list) plus 9 optional modules — including analytics
(UTM/GA4/PostHog) and dark-mode — on Laravel 10–13 with a Tailwind 3
preset, marker-based installers, and a PHP 8.1–8.4 × Laravel 10/11/12 CI
matrix.

**Superseded by 0.2.0**, which removed the analytics module, dropped
Laravel 10/11, moved to Tailwind v4, and fixed the install-breaking bugs
found in audit — see its notes before upgrading.

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.2...v0.3.0
[0.2.2]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

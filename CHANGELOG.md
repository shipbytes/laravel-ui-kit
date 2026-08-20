# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Pre-1.0 release policy.** While the kit is in early iteration, the v0.1.0
> tag is rolling — fixes and improvements land under it rather than bumping
> the patch version. Pin to a commit if you need a frozen reference.

## [Unreleased]

## [0.1.0] - 2026-08-20

Initial public release. Contents updated in place during early iteration —
the 2026-08-20 revision is a **scope reset** following a full audit.

### Scope reset (2026-08-20) — breaking

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

### Fixed (2026-08-20 audit)

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

### Added (2026-08-20)

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

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

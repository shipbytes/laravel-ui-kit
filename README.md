<p>
  <a href="https://shipbytes.dev">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="docs/images/shipbytes-dark.svg">
      <img src="docs/images/shipbytes-light.svg" alt="Shipbytes" width="184" height="36">
    </picture>
  </a>
</p>

# Laravel UI Kit

Admin panel + auth UI scaffolding for **Laravel 12+** with Livewire 4, Fortify, and **Tailwind CSS v4**.

**Documentation with screenshots: [shipbytes.dev/docs/laravel-ui-kit](https://shipbytes.dev/docs/laravel-ui-kit).**

Core + 7 optional modules, installed by a turnkey `php artisan ui-kit:install`. CI covers PHP 8.2–8.4 on Laravel 12 (plus an experimental Laravel 13 leg) and runs a full fresh-app install → build → `route:cache` → HTTP smoke on every push. See [CHANGELOG.md](CHANGELOG.md) for release notes.

## What you get

### Always installed (core)

- **Auth pages** — login, register, forgot/reset password, email verification, confirm password, and a **two-factor challenge** page (Livewire 4 single-file components + Fortify), light and dark
- **Working 2FA** — the login flow stages a TOTP/recovery-code challenge for users who enabled two-factor auth (enable/confirm UI ships with the `profile` module)
- **Admin shell** — collapsible sidebar, mobile bottom nav with a "More" sheet, theme toggle, current-user footer with profile link + logout; nav is config-driven from `config/admin.php` with Heroicons v2 icon names
- **User shell** — a light layout (`layouts/user-shell`) for the pages outside the admin panel (`/profile`, `/support`, `/contact`, `/changelog`). Signed in, its header shows Admin (for admins), the avatar and Log out; signed out, Sign in and Register. Support and Changelog links appear once their modules are installed
- **Dashboard stub + Users list/detail** — stat tiles, a searchable table with avatars and an Admin badge, a detail page with a guarded Delete user button, and the `<x-ui-kit::page-header>` component every admin screen opens with
- **Built on the Shipbytes design system** — every view uses [`shipbytes/blade-ui`](https://shipbytes.dev/docs/design-system) (`ds::` Blade components, semantic tokens that switch for dark mode on their own, Heroicons v2, Inter); it comes with the kit, so there's no separate install step
- **`WithDesignSystemPagination`** — a Livewire trait that pages any list with the design system's pagination, keeping its search and filters
- **Alpine stores** — sidebar collapse (localStorage-persisted), theme toggle with no-flash snippet (follows the OS until the user picks a theme)
- **`ui-kit:doctor`** — a health check that verifies the install is fully wired

### Optional modules

| Slug | What it adds | Composer deps |
|---|---|---|
| `admin-middleware` | Spatie Permissions wiring (falls back to `is_admin` boolean if skipped) | `spatie/laravel-permission:^6.10` |
| `support-tickets` | Users open and follow tickets at `/support`; admins answer from a queue; mail both ways | — |
| `changelog` | Admin-authored changelog, published at `/changelog` with a category filter | `mews/purifier:^3.4` |
| `contacts` | Public contact form at `/contact` and the admin inbox it fills, replies sent by mail | — |
| `profile` | Self-service name/email/password/avatar + full 2FA management | — (optional: `intervention/image`) |
| `impersonation` | Login-as-user with exit ribbon + button partial | `lab404/laravel-impersonate:^1.7.5` |
| `activity-log` | Spatie activity log + filterable admin viewer | `spatie/laravel-activitylog:^4.10` |

Modules that ship models also ship **factories**, so `SupportTicket::factory()` works in your tests out of the box.

## Requirements

- PHP 8.2+
- Laravel 12 (Laravel 13 supported in composer constraints; CI leg is experimental)
- Livewire 4.4+ (installed with the kit)
- `shipbytes/blade-ui` ^1.3, the design system (installed with the kit)
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
| Frontend stack | **Blade** | Gives you plain Vite + Tailwind CSS v4, which the kit's stylesheet (and the design system it imports) plugs straight into. Livewire 4 arrives via Composer with the kit itself. |
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
- Patches `config/fortify.php` (`views=false`, `home=/`, and turns off the features whose endpoints the kit's own pages replace: registration, password reset, profile and password updates, passkeys) and publishes Fortify's 2FA columns migration
- Patches `config/admin.php` nav, `routes/admin.php` and both groups of `routes/ui-kit-user.php` (one open to guests, one for signed-in users) between `/* ui-kit:* */` markers — **idempotently**, and without wiping other modules' entries
- **Wires your Vite entrypoints**: adds `@import './ui-kit.css';` to `resources/css/app.css` and `import './ui-kit';` to `resources/js/app.js`, and deletes the skeleton's Instrument Sans `--font-sans` from `app.css` (see [Finish wiring](#finish-wiring-the-irreducible-checklist))
- Runs `vendor:publish` for every dependent package, one `php artisan migrate`, seeds the admin role, runs `storage:link`
- Generates `app/Models/Concerns/UiKitUser.php` bundling exactly the traits your chosen modules need (Spatie `HasRoles`, lab404 `Impersonate`, Fortify `TwoFactorAuthenticatable` plus `avatarUrl()`) — **and applies it to `app/Models/User.php` automatically** (the patch is lint-checked and reverted if it would break the file; you only edit by hand if the installer says so)
- With the `profile` module, adds `two_factor_secret` and `two_factor_recovery_codes` to the User model's `$hidden` (or its `#[Hidden]` attribute), so a user's JSON never carries them
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

3. **Verify:** `php artisan ui-kit:doctor` prints a ✓/✗ table: published files,
   the Livewire 4 setup, Fortify flags, Vite imports, the design system
   (package installed, `ui-kit.css` imports it and scans the kit's views, no
   leftover 0.3 theme file, no font override in `app.css`, nav icons resolve,
   admin lists use the design system's pagination), trait applied, storage
   link, 2FA columns, each module's routes and mail views, duplicate route
   names and mail config. Every failing row says how to fix it.

> The `UiKitUser` trait used to be a manual step here — the installer now
> applies it to `app/Models/User.php` itself and only asks you to do it by
> hand if the patch fails (custom model location, unrecognized class layout).

#### What the CSS ends up as

After the install a fresh Laravel 12 app's `resources/css/app.css` is the
skeleton's file with one line added and one block removed:

```diff
 @import 'tailwindcss';
+@import './ui-kit.css';

 @source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
 @source '../../storage/framework/views/*.php';
 @source '../**/*.blade.php';
 @source '../**/*.js';
-
-@theme {
-    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
-        'Segoe UI Symbol', 'Noto Color Emoji';
-}
```

The skeleton's `@theme` block came after the kit's import, so it beat the
design system's Inter, and since nothing loads Instrument Sans the app rendered
in the system font. The installer deletes only that declaration; a font you set
yourself is left alone (and the doctor reminds you to load it).

The published `resources/css/ui-kit.css` holds the design system's three-line
contract plus the kit's own views:

```css
@import '../../vendor/shipbytes/blade-ui/dist/tokens.css';
@import '../../vendor/shipbytes/blade-ui/dist/theme.css';
@source '../../vendor/shipbytes/blade-ui/resources/views';
@source '../../vendor/shipbytes/laravel-ui-kit/resources/views';
```

Keep both `@source` lines: Tailwind doesn't scan `vendor/`, so without them the
`ds::` components and the kit's page header render unstyled and nothing reports
an error.

#### Your own layouts

The kit's three layouts (guest, admin, user shell) load Inter and include the
kit's head and banner components. A master layout of your own for public pages
needs the same in its `<head>` and `<body>` to match, keep dark mode flash-free
and show the impersonation ribbon:

```blade
<head>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <x-ui-kit::head />
</head>
<body>
    <x-ui-kit::banners />
```

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

Drop a logo at `public/images/logo.png` (or point `UI_KIT_BRAND_LOGO` anywhere, including a full URL). Until a logo exists the layouts render an initial badge in the accent colour, so nothing looks broken on a fresh install. Brand *colours* are design-system tokens; see the next section. `home_route` names the route used for "back to site" links and post-login redirects — **when the route doesn't exist the kit falls back to `/`**, so fresh apps work without defining one.

### Design system

The kit's views are built on the [Shipbytes design system](https://shipbytes.dev/docs/design-system)
(`shipbytes/blade-ui`): `ds::` Blade components and semantic tokens such as
`bg-surface`, `text-fg-muted` and `border-border`. Composer installs it with the
kit, and `resources/css/ui-kit.css` wires it in (see
[What the CSS ends up as](#what-the-css-ends-up-as)). The accent is the design
system's blue and the font is Inter.

To change a token (the accent colour, say), redefine its `--ds-*` variable in
`app.css` **after** the `ui-kit.css` import, once for light and once for dark:

```css
@import './ui-kit.css';

:root { --ds-accent: #0d9488; --ds-accent-hover: #0f766e; }
.dark { --ds-accent: #2dd4bf; --ds-accent-hover: #5eead4; }
```

The design system's docs list every token and component. Your own views use the
same `ds::` components and token classes; the kit's stubs are working examples.

**Dark mode** is a `light` or `dark` class on `<html>`. Exactly one is always
set: `<x-ui-kit::head />` sets it before first paint and the theme store keeps
it across `wire:navigate` page changes. Until the user picks a theme with
`<x-theme-toggle />` it follows the OS setting; picking one stores it in
localStorage as `ui-kit.theme`.

### Sidebar navigation

```php
// config/admin.php
'nav' => [
    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2'],
    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
    ['section' => 'Support'],
    ['label' => 'Tickets', 'route' => 'admin.support.index', 'icon' => 'ticket', 'badge' => 'open_tickets'],
],
```

Modules append their own entries automatically between the `/* ui-kit:nav-* */` markers.
`icon` is a [Heroicons v2](https://heroicons.com) outline name (`squares-2x2`, `document-text`, `envelope`, `chart-bar`…), rendered through `ds::icon`; any icon on heroicons.com works, and `ui-kit:doctor` flags a name that doesn't resolve. Until it is fixed, that entry shows a question-mark icon and the log gets one `blade-ui: icon "…" not found` warning per request; the rest of the admin works.

### Your own admin screens

Open them with the kit's page header, and page their lists with the design system:

```blade
<x-ui-kit::page-header title="Reports" subtitle="Monthly figures.">
    <x-slot:actions>
        <x-ds::button :href="route('admin.reports.export')">Export</x-ds::button>
    </x-slot:actions>
</x-ui-kit::page-header>
```

```php
use Shipbytes\UiKit\Livewire\Concerns\WithDesignSystemPagination;

class ReportList extends Component
{
    use WithDesignSystemPagination;   // in place of WithPagination
}
```

The trait renders the design system's Livewire pagination, so a page change is a
Livewire update that keeps the component's search and filters. A component that
defines its own `paginationView()` keeps it.

### Sidebar badges

A nav entry's `badge` key names a count, shown on the desktop rail and the phone bottom bar (capped at "9+" there). The kit's `DefaultBadgeResolver` is bound out of the box and counts `open_tickets` (`SupportTicket::open()`) and `unread_contacts` (`ContactSubmission::unread()`) when those modules are installed, once per request. A count that throws, such as a table not yet migrated, is left out rather than failing every admin page, and `ui-kit:doctor` reports it.

For badges of your own, bind a resolver that returns every key your nav uses:

```php
// In a service provider
$this->app->bind(
    \Shipbytes\UiKit\Contracts\SidebarBadgeResolver::class,
    \App\Support\AdminBadgeResolver::class,
);
```

It returns `['open_tickets' => 12, 'unread_contacts' => 3, 'pending_orders' => 4]`. Yours replaces the default entirely, so extend `DefaultBadgeResolver` (and merge `parent::compute()`) to keep the kit's two counts. The doctor's `sidebar badges resolve` row fails when a nav entry's key is missing from the output, because a missing badge looks exactly like a count of zero.

## Two-factor authentication

Fortify's `twoFactorAuthentication` feature is enabled by default. With the `profile` module installed (which adds `TwoFactorAuthenticatable` to the generated trait and ensures the columns exist), users get the full flow:

1. **Enable** on `/profile` → scan the QR code → (when Fortify's `confirm` option is on) enter a code to confirm → save recovery codes.
2. **Login** validates credentials first, then redirects 2FA users to `/two-factor-challenge` for a TOTP or recovery code. Both steps are rate limited. Fortify's own `POST /login` works too: it redirects to `two-factor.login`, which the kit points at the same page, and the page picks up the login Fortify staged.
3. **Disable / regenerate recovery codes** any time from the profile page.

The installed `config/fortify.php` turns on Fortify's `confirmPassword` option, so enabling, disabling, showing and regenerating recovery codes first ask for the password unless it was confirmed within `auth.password_timeout` (three hours by default). The confirm page returns the user to `/profile`.

Secrets are read with `Fortify::currentEncrypter()`, so `Fortify::encryptUsing()` works.

Without the `profile` module (or without the trait applied), login simply never challenges — nothing breaks.

## Mail

The `support-tickets` and `contacts` modules send mail. Each mailable is a published
stub in `app/Mail/` with a markdown view in `resources/views/mail/`, so change the
wording, the subject or the recipients there.

| Mailable | Sent when | To |
|---|---|---|
| `NewTicketMail` | a user opens a ticket | the admins (replying writes to the user) |
| `TicketReplyMail` | a user replies, or an admin does | the admins, or the ticket's owner; the link opens the admin page or the user's own |
| `TicketStatusChangedMail` | an admin changes a ticket's status | the ticket's owner, with the old and new status |
| `ContactSubmittedMail` | someone sends the contact form | the admins (replying writes to the sender) |
| `ContactConfirmationMail` | the same | the sender, with a copy of the message |
| `ContactReplyMail` | an admin replies from the inbox | the sender: the reply, then their message quoted |

**The admins** are `\Shipbytes\UiKit\Support\UiKit::admins()`: users with the Spatie
`admin` role when `admin-middleware` is installed, else users with `is_admin`. To send
admin mail to a shared mailbox instead, list addresses in `.env`:

```dotenv
UI_KIT_ADMIN_ADDRESSES="support@example.com,ops@example.com"
```

(or set `mail.admin_addresses` in `config/ui-kit.php`; a non-empty list replaces the
admin users).

**Queued.** The mailables implement `ShouldQueue` on the default queue. With
`QUEUE_CONNECTION=sync` they go out during the request; with the Laravel 12 skeleton's
`database` default, run `php artisan queue:work` or nothing is sent.

**A failure never loses the row.** The ticket, reply or message is saved before the mail
is attempted, and a send that throws is logged as `ui-kit: could not send App\Mail\…`
with the error; the form still succeeds. The admin screens say so: "Reply saved, but the
email failed: …". On a real queue a failure happens in the worker instead and lands in
`failed_jobs`.

**Set `MAIL_FROM_ADDRESS`.** The doctor's `mail from address configured` row warns while
it is unset or still `hello@example.com`.

**The look** is Laravel's stock markdown theme, with your `APP_NAME` in the header and no
kit branding. To restyle it, `php artisan vendor:publish --tag=laravel-mail` and edit
`resources/views/vendor/mail/html/themes/default.css`, or add a theme of your own and
name it in `config/mail.php` under `markdown.theme`.

The doctor's `support-tickets mail views published` and `contacts mail views published`
rows check every mailable class and view is in place.

### Public forms of your own

The contact form and the new-ticket form share two guards: a honeypot field and an hourly
limit counted only after validation passes. Use them on a form of yours:

```php
use Shipbytes\UiKit\Livewire\Concerns\WithSpamProtection;

public function submit(): void
{
    if ($this->caughtInHoneypot()) {            // a bot filled the hidden field
        $this->sent = true;                     // fake success, save nothing
        return;
    }

    $this->validate();

    if (! $this->passesRateLimit('quote:'.request()->ip(), 3)) {   // 3 an hour
        return;                                 // the refusal is in the `limit` error
    }

    // save, then UiKit::sendMail(UiKit::admins(), new QuoteRequestedMail($quote));
}
```

```blade
@error('limit') <x-ds::alert tone="danger">{{ $message }}</x-ds::alert> @enderror
<x-ui-kit::honeypot />
```

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
Both halves of a help desk. **Users** (signed in) get `/support`: their own tickets with a status filter, status and priority badges and 15 to a page; `/support/create`: subject, a category from `SupportTicket::categories()` and a message of 20 to 5,000 characters; and `/support/{ulid}`: the conversation, a reply box, and Close / Reopen. A closed or resolved ticket takes no replies until it is reopened. Access goes through the published `App\Policies\SupportTicketPolicy` (Laravel finds it by name): the owner or an admin may view and update a ticket, only an admin may delete one. Limits: five new tickets an hour per user, ten replies an hour per ticket, counted only after validation passes; the create form has a honeypot.

**Admins** keep the queue at `/admin/support`: four stat tiles (total, open, in progress, resolved), a search field and status/priority/category filters over a table with badge tones from `SupportTicket::statusTone()` / `priorityTone()`. A ticket shows the thread, an inline reply, and Status and Priority selects. The Support nav entry shows the open count (see [Sidebar badges](#sidebar-badges)).

**Mail** both ways (see [Mail](#mail)): a new ticket and each user reply go to the admins; an admin reply and a status change go to the owner.

### `changelog`
Admin CRUD at `/admin/changelog`: a table with a title search, a category filter, category badges (`ChangelogEntry::categoryTone()`) and Published/Draft badges; the form's Published toggle is a `ds::switch`. Bodies are sanitized with `mews/purifier` on save.

**Public** at `/changelog`, for guests and users alike: published entries (`ChangelogEntry::published()`: published and dated today or earlier), newest first, grouped by month, each with its category badge. The category filter is a row of links (`ds::tabs` in navigation mode) carrying per-category counts from one query, and lives in the query string (`/changelog?category=fix`), so a filtered view can be shared. Categories are `ChangelogEntry::categories()`.

### `contacts`
**Public** at `/contact`, for guests and users (a signed-in user's name and email are filled in): name, email, a subject from `contacts.subjects` in `config/ui-kit.php` (five neutral labels, edit them freely) and a message of 10 to 5,000 characters. A honeypot fakes success for bots, and each address may send three an hour (counted after validation). The message is saved with the sender's IP, the admins get a `ContactSubmittedMail` and the sender a `ContactConfirmationMail`, and the form gives way to a thank-you with a **Send another** button.

**Admins** read the inbox at `/admin/contacts`: stat tiles (total, unread, replied, archived), a search field and a status filter over a table with a select column; selecting rows shows a bulk bar (Mark read, Mark unread, Archive, Delete). A message opens in a `ds::modal`; its reply is saved and sent as a `ContactReplyMail` ("Re: …", the reply above the quoted message), and the inbox says "Reply sent to …" or, when the mail fails, "Reply saved, but the email failed: …". Archiving sets `archived_at`; archived messages show under the Archived filter. With `support-tickets` installed, a **Copy to Ticket** button appears. The Contacts nav entry shows the unread count.

### `profile`
Cards under `/profile` (rendered in the user shell, not the admin shell): update info + avatar, update password, two-factor authentication, delete account. The avatar is a round `ds::avatar` (initials until a photo exists) beside a `ds::file-upload` drop zone with Save photo, Cancel and Remove; deleting the account confirms the password in a `ds::modal`. Avatars are stored on the public disk under a random 40-character name with a content-derived extension, resized to 200×200 when `intervention/image` is installed; replacing a photo deletes the old file, and deleting the account deletes the photo. The profile stores `avatar_path` with `forceFill()`, so a stock `User` model's `$fillable` doesn't need changing. The generated trait's `avatarUrl()` puts the photo in the admin rail, the user list and detail, and the user shell. Re-sending the verification email is limited to six a minute per user.

### `impersonation`
Two Blade partials over `lab404/laravel-impersonate`. The package ships its routes as a macro; the installer calls it for you by adding `Route::impersonate();` to `routes/ui-kit-user.php` (web + auth), which registers `impersonate` and `impersonate.leave`. `ui-kit:doctor` checks both routes exist. `<x-ui-kit::banners />` shows the exit ribbon automatically, and the admin user detail page shows the login-as button on its own (it includes `partials.impersonation-button` when the partial exists; the doctor's `impersonation button wired` row checks it). Use `@include('partials.impersonation-button', ['user' => $user])` to put the button on screens of your own. The generated `UiKitUser` trait provides `canImpersonate()` / `canBeImpersonated()`.

### `activity-log`
Paginated admin viewer over `spatie/laravel-activitylog`: a table of when, who, event (with the log name as a small badge under it), subject and description, filtered by labelled log stream, user email and date range fields. Add the `LogsActivity` trait to models you want logged (see Spatie's docs).

### Public and user routes

The module pages live in `routes/ui-kit-user.php`, in two groups: `/contact` and `/changelog` in the web-only group (guests too), `/profile` and `/support/*` in the web + auth group. Rename a path there if it clashes with one of yours; keep the route name (`contact`, `changelog.public`, `support.index`…), which the kit's links use. The doctor's `no duplicate route names` row catches a name that collides.

## Environment reference

```dotenv
# --- Branding (all optional; sensible defaults) --------------------------
UI_KIT_BRAND_NAME="Acme"
UI_KIT_BRAND_LOGO="/images/logo.png"
UI_KIT_HOME_ROUTE="home"

# --- Mail (password reset, verification, tickets, the contact form) -------
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.example.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
UI_KIT_ADMIN_ADDRESSES=""             # optional: admin mail to these instead of the admin users
QUEUE_CONNECTION=sync                 # or keep 'database' and run php artisan queue:work
```

In local dev, `MAIL_MAILER=log` works — mail lands in `storage/logs/laravel.log`.

## Troubleshooting

Run `php artisan ui-kit:doctor` first — it catches the common problems and prints the fix beside each failing row.

**Behind a load balancer, CDN or other proxy**, tell Laravel to trust it, or every request arrives from the proxy's address. The kit's limiters key on the IP: one visitor's ten registrations an hour lock out everyone, and five bad passwords lock that email out for the whole internet. In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '10.0.0.0/8'); // your proxy's addresses, or '*' if only the proxy can reach the app
})
```

The doctor's `trusted proxies configured` row is a skip outside production and fails in production until that call exists (`trustProxies(at: [])` says "no proxy" and passes).

Every known failure, with its symptom, cause and fix command, is on the site:
**[shipbytes.dev/docs/laravel-ui-kit/troubleshooting](https://shipbytes.dev/docs/laravel-ui-kit/troubleshooting)**.
Upgrading from an older minor is covered step by step on
[shipbytes.dev/docs/laravel-ui-kit/upgrading](https://shipbytes.dev/docs/laravel-ui-kit/upgrading)
and in [CHANGELOG.md](CHANGELOG.md).

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

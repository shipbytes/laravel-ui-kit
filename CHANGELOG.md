# Changelog

All notable changes to `shipbytes/laravel-ui-kit` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> Published versions are immutable (Packagist enforces this): every
> publishable state gets a fresh tag. Pre-1.0, breaking changes bump the
> minor version.

## [Unreleased]

## [0.5.1] - 2026-10-23

### Fixed

- **A changelog entry dated today now shows on `/changelog` on SQLite.**
  `ChangelogEntry::published()` compared `published_at` with today's date as
  strings, and the date cast stores `2026-10-23 00:00:00`, which SQLite (Laravel
  12's default database) ranks after `2026-10-23`. Every entry dated today stayed
  hidden until the next day; MySQL and PostgreSQL were not affected. The scope
  now uses `whereDate`. An app on 0.5.0 picks it up by republishing the model
  (`php artisan ui-kit:install-module changelog`, which overwrites the module's
  published files, so commit first) or by changing `where('published_at', …)` to
  `whereDate('published_at', …)` in `app/Models/ChangelogEntry.php`.

## [0.5.0] - 2026-10-23

The user-facing halves of three modules. Until now `support-tickets`, `contacts`
and `changelog` shipped only their admin screens: the ticket queue had no way for
a user to open a ticket, the inbox's reply was saved and never sent, and the
changelog had no reader. Each now has its public or signed-in pages, the mail
that connects the two sides, and doctor rows for what the installer promises.
New routes make it a minor release: "Upgrading from 0.4" at the end has the steps.

### Breaking

- **Five new routes.** `support-tickets` adds `/support`, `/support/create` and
  `/support/{ulid}` for signed-in users (`support.index`, `support.create`,
  `support.show`); `contacts` adds `/contact` (`contact`) and `changelog` adds
  `/changelog` (`changelog.public`), both open to guests. An app that already
  has a route at one of those paths ends up with two, and only one answers:
  rename the kit's path in `routes/ui-kit-user.php` and keep its route name,
  which the kit's links use. A colliding route *name* fails the doctor's
  `no duplicate route names` row, and `route:cache`.
- **`routes/ui-kit-user.php` has a second group.** A `Route::middleware(['web'])`
  group between `/* ui-kit:public-routes-start */` and `/* ui-kit:public-routes-end */`
  markers sits above the web + auth one, and modules patch it through the new
  `public_routes` registry key. The installer never overwrites the file without
  `--force`, so an upgraded app keeps its 0.4 copy: the installer then warns that
  it skipped `/contact` and `/changelog`, and the doctor's
  `routes/ui-kit-user.php has the public-routes block` row fails with the fix.
- **Support and contacts send mail.** A ticket, a reply, a status change and a
  contact message now each mail someone (see Added). If you wired mail of your
  own after the 0.4 post-install notes, remove it or each one goes out twice.

### Added

- **Support tickets for users.** `/support` lists the signed-in user's own
  tickets with a status filter (kept in the query string), status and priority
  badges from the model's tone methods and 15 to a page, and an empty state with
  a New ticket button. `/support/create` takes a subject (up to 255), a category
  from the new `SupportTicket::categories()` and a message of 20 to 5,000
  characters, then opens the ticket (priority medium, status open, the IP saved)
  with that message as its first reply and goes to it. `/support/{ulid}` shows
  the thread with a reply box (2 to 5,000 characters, refused while the ticket is
  closed or resolved) and Close / Reopen. Limits: five tickets an hour per user,
  ten replies an hour per ticket. The pages render in the user shell.
- **`App\Policies\SupportTicketPolicy`**, published and found by Laravel by
  name: the owner or an admin may view and update a ticket, only an admin may
  delete one.
- **Mail, as published stubs.** Six mailables in `app/Mail/` with markdown views
  in `resources/views/mail/`, on Laravel's stock markdown theme with no kit
  branding: `NewTicketMail` (to the admins, reply-to the user, linking the admin
  page), `TicketReplyMail` (both directions; `toAdmin` picks the readers and the
  link), `TicketStatusChangedMail` (to the owner, old to new),
  `ContactSubmittedMail` (to the admins, reply-to the sender),
  `ContactConfirmationMail` (to the sender, with a copy) and `ContactReplyMail`
  ("Re: …", the reply above the quoted message). They implement `ShouldQueue` on
  the default queue, so `QUEUE_CONNECTION=sync` sends them during the request.
  Every send happens after the row is saved, inside a try/catch that logs
  `ui-kit: could not send <class>`, so a mail that fails never fails the form.
- **A public contact form** at `/contact`: name, email, a subject from
  `ui-kit.contacts.subjects` (five neutral labels in the published config), and a
  message of 10 to 5,000 characters; a signed-in user's name and email are filled
  in. Three messages an hour per IP. It saves the message with the sender's IP,
  mails the admins and the sender, and shows a thank-you with a Send another
  button.
- **A public changelog** at `/changelog`: published entries, newest first, grouped
  by month, each with its category badge and its body. The category filter is a
  row of links (`ds::tabs` in navigation mode) with per-category counts from one
  query, kept in the query string. `ChangelogEntry::categories()` lists the
  categories.
- **The user shell works signed out.** Guests see Sign in, and Register when the
  route exists, in place of the avatar and Log out. Support (signed in) and
  Changelog links appear when their routes exist, the way Profile does, marked
  `aria-current` on their own pages, and wrap onto a second row on a phone.
- **`UiKit::admins()`**: who gets admin mail. The Spatie `admin` role when the
  User model has `hasRole()`, else `is_admin`; `ui-kit.mail.admin_addresses`
  (`UI_KIT_ADMIN_ADDRESSES`, comma-separated) replaces that list when set.
- **`UiKit::sendMail($recipients, $mailable)`**: one message per recipient, never
  throws, returns the first failure for the screen to report.
- **`WithSpamProtection` and `<x-ui-kit::honeypot />`**, shared by the contact
  and new-ticket forms and usable on forms of your own: a honeypot field
  (`website`, visually hidden, `tabindex="-1"`, `autocomplete="off"`) that fakes
  success without saving, and `passesRateLimit()`, which counts an attempt only
  after validation has passed.
- **Registry keys** `public_routes` (patched like `user_routes`, idempotent by
  route name, re-applied in update mode) and `mail` (mailable => view).
- **Doctor rows:** `routes/ui-kit-user.php published` and its auto-load row,
  `routes/ui-kit-user.php has the public-routes block` (fails only once an
  installed module needs it), `<module> routes registered` for every installed
  module's admin, user and public routes, `<module> mail views published` for
  `support-tickets` and `contacts`, and `support ticket policy published`.
- The fresh-app CI job smoke-tests `/contact`, `/changelog` and the guest
  redirect from `/support`.

### Changed

- **The admin ticket screen mails the owner.** A reply sends `TicketReplyMail`
  linking the user's own ticket page and says "Reply sent to …"; a status change
  sends `TicketStatusChangedMail`. When the mail fails, the screen says the reply
  or the status was saved and the email was not.
- **The inbox reply is sent.** `ContactList@submitReply` sends `ContactReplyMail`
  and says "Reply sent to …", or "Reply saved, but the email failed: …".
- The admin ticket list's category filter reads `SupportTicket::categories()`.
- The installer copies a module's `Mail/` and `Policies/` stubs into `app/`.
- Post-install notes: the changelog's "optional public feed", the contacts
  module's "wire up a public contact form" and "plug in your own ContactReply",
  and support-tickets' "ships without Mailables" are gone; both mailing modules
  note the queue worker and `MAIL_FROM_ADDRESS` instead. Module summaries in
  `ui-kit:list-modules` describe both halves.

### Fixed

- **A ticket's conversation keeps its order.** `SupportTicket::replies()` sorted
  by `created_at` alone, so two messages written in the same second could show in
  either order, on the admin page too. The id now breaks the tie.

### Upgrading from 0.4

1. `composer require shipbytes/laravel-ui-kit:^0.5`.
2. Commit first, then re-run the installer in update mode with every installed
   module: `php artisan ui-kit:install --force --modules=…` (the names
   `ui-kit:list-modules` shows). It republishes `routes/ui-kit-user.php` with the
   public-routes block and patches the modules' lines back in, and publishes the
   new components, mailables, views and policy. As before, `--force` overwrites
   `config/admin.php` and `config/ui-kit.php`, so read `git diff` and restore
   your own nav entries and values. (Without `--force`, add
   `Route::middleware(['web'])->group(function () { /* ui-kit:public-routes-start */ /* ui-kit:public-routes-end */ });`
   above the auth group in `routes/ui-kit-user.php` yourself and run
   `php artisan ui-kit:install-module support-tickets contacts changelog`, which
   re-copies those modules' files.)
3. `npm run build`.
4. Set `MAIL_FROM_ADDRESS` in `.env`, and run `php artisan queue:work` unless
   `QUEUE_CONNECTION=sync`. Optionally set `UI_KIT_ADMIN_ADDRESSES` to send
   admin mail to a shared mailbox.
5. `php artisan ui-kit:doctor`. Every row should pass, or skip with a reason.

## [0.4.1] - 2026-10-23

A hardening release: no breaking changes. The installer closes five Fortify
endpoints that answered with a 500, keeps a user's two-factor secrets out of
its JSON, and several things that were README instructions now happen on their
own. "Upgrading from 0.4.0" at the end of this entry has the steps.

### Security

- **Fortify's endpoints the kit does not own are switched off.** `views =>
  false` only removed Fortify's GET pages; it still registered `POST /register`,
  `POST /forgot-password`, `POST /reset-password`, `PUT /user/profile-information`
  and `PUT /user/password`. Each resolves an action class (`CreatesNewUsers`
  and friends) that no kit app binds, so each answered with a 500, and an app
  that did bind `CreatesNewUsers` got a second registration path that skipped
  the kit's disposable-email rule and its limiter. The installer now comments
  out `Features::registration()`, `resetPasswords()`, `updateProfileInformation()`
  and `updatePasswords()` in `config/fortify.php` (the kit's own pages do all
  five jobs) and leaves `emailVerification()` alone. The patch is idempotent
  and lint-checked, and update mode applies it to an existing install.
- **The two-factor columns are hidden on the User model.** The profile module
  adds `TwoFactorAuthenticatable` to the model but never hid its columns, so
  `toArray()` and the JSON of any user carried the encrypted
  `two_factor_secret` and `two_factor_recovery_codes`. The installer adds both
  to the model's `$hidden`, whether that is a `protected $hidden` array (the
  Laravel 12 skeleton), a `#[Hidden([...])]` attribute (Laravel 13), or
  missing. A model that already lists them is left alone, and the result is
  lint-checked like the trait patch.

### Fixed

- **Fortify's `POST /login` no longer 500s for a two-factor user.** It
  redirects such a user to the route `two-factor.login`, which did not exist:
  the kit's page is `two-factor.challenge`. `routes/auth.php` adds
  `two-factor.login` as a redirect to the kit's page, which now also accepts
  the login Fortify staged in the session (`login.id`, `login.remember`), so
  that sign-in completes. `route('two-factor.challenge')` is unchanged.
- **The installer no longer leaves passkeys on.** Fortify's published config
  spreads `Features::passkeys([...])` over three lines, which the 0.4.0 patch
  never matched. Each disabled feature's whole call is now commented out.
- **The profile page's two-factor card honours `confirmPassword`.** Fortify's
  published config sets it, but the card called the actions directly, so
  enabling, disabling, showing and regenerating recovery codes never asked for
  the password. Without a confirmation inside `auth.password_timeout`, those
  actions now go to the confirm-password page, which returns to the profile.
- **A failed registration no longer uses up the hourly limit.** The register
  page counted every submit against its ten per IP per hour before validating,
  so a few typos locked the address out. Only a successful registration counts
  now.
- **The 2FA challenge reads secrets with Fortify's encrypter**
  (`Fortify::currentEncrypter()`), so an app that set its own with
  `Fortify::encryptUsing()` can sign in.
- **A failed login fires `Illuminate\Auth\Events\Failed`.** The login form
  checks credentials with `validate()`, which fires no event, so listeners and
  audit logs never saw a failed login.
- **Registering regenerates the session**, as signing in already did.
- **Avatars are stored under a random 40-character name**, not the user's id,
  which made every photo on the public disk guessable. The old file is still
  deleted on replace, and deleting an account now deletes its photo too.
- **`ui-kit:doctor` reports correctly when called twice in one process.** It
  kept the first run's rows and its failure.

### Added

- **Sidebar badges work without code.** `ModuleRegistry` has always declared
  the `open_tickets` and `unread_contacts` badges, but bound a resolver that
  returned nothing. The new default, `Shipbytes\UiKit\Support\DefaultBadgeResolver`,
  counts `SupportTicket::open()` and `ContactSubmission::unread()` (new scope:
  unread and not archived) when those models exist, once per request. A count
  that fails, such as a table not yet migrated, is left out instead of failing
  every admin page. An app that binds its own `SidebarBadgeResolver` still wins.
- **The phone bottom bar shows the badges**, through the new `badge` prop on
  `ds::bottom-nav-item`. Requires `shipbytes/blade-ui` ^1.2.3, which caps the
  pill at "9+" and reads the full count to screen readers.
- **The phone "More" sheet** ends with Profile (when the profile module is
  installed), Back to site and Log out, which a phone could not reach before.
  The user shell gains a Profile link (the user's avatar) beside Log out.
- **Avatars appear across the shells.** The generated `UiKitUser` trait gains
  `avatarUrl(): ?string` when the profile module is installed, and the admin
  rail, the user list, the user detail page and the user shell show it.
  `UiKit::avatarUrl($user)` returns null without the module, so those views
  fall back to initials.
- **Admin user screens.** The user list and detail show an "Admin" badge
  (`UiKit::isAdmin($user)`: the Spatie `admin` role or the `is_admin` column,
  the same test as the user shell). The detail page has a Delete user button,
  which the server refuses for admins and for yourself.
- **The impersonation button is wired in.** The user detail page includes
  `partials.impersonation-button` when it exists, so the README's "add this
  line yourself" step is gone.
- **Resending the verification email is throttled** to six a minute per user,
  shared between the verify-email page and the profile page, with a message
  when the limit is hit.
- **The register page checks names** against letters, spaces, apostrophes,
  hyphens and full stops, with a plain message.
- **Doctor rows:** `fortify features limited to two-factor`,
  `two-factor.login route registered`, `two-factor columns hidden on the User
  model`, `sidebar badges resolve`, `impersonation button wired` (with the
  impersonation module) and `trusted proxies configured`. The last passes when
  `bootstrap/app.php` calls `trustProxies(`, is a skip outside production and
  fails in production. Behind an untrusted proxy every visitor shares one IP,
  so ten registrations an hour lock out everyone.
- **Behaviour tests** for every auth page (login, register, password reset,
  email verification, confirm password, the 2FA challenge), the profile's 2FA
  card and avatar, the admin user screens, the shell and the admin ticket
  screen: 133 tests, up from 68.

### Upgrading from 0.4.0

1. `composer update shipbytes/laravel-ui-kit -W` (`-W` lets Composer take
   `shipbytes/blade-ui` to 1.2.3).
2. Commit first, then either:
   - re-run the installer in update mode with every installed module:
     `php artisan ui-kit:install --force --modules=…` (the names
     `ui-kit:list-modules` shows). This applies the two security patches and
     republishes the changed stubs. As in 0.4.0, `--force` overwrites
     `config/admin.php` and `config/ui-kit.php`, so read `git diff` and
     restore your own nav entries and values; or
   - without `--force`, which keeps every file you own and still applies both
     security patches (they edit `config/fortify.php` and `app/Models/User.php`
     in place), then follow the doctor's hints for the rest: the
     `two-factor.login` route line in `routes/auth.php` and the impersonation
     include on the user detail page.
3. `npm run build`.
4. `php artisan ui-kit:doctor`. Every row should pass, or skip with a reason.

## [0.4.0] - 2026-10-23

0.4.0 moves the kit onto the Shipbytes design system (`shipbytes/blade-ui`).
The layouts, the shared components, the seven auth pages, the profile page and
the nine admin screens are all built from its `ds::` components and semantic
tokens; the kit no longer carries a palette, fonts or components of its own.
Every route, route name, `wire:model`, Livewire method, validation message and
`config('ui-kit.copy.*')` string is unchanged. What changes is every file the
kit publishes, so an app on 0.3 has to merge: "Upgrading from 0.3" at the
end of this entry lists the steps.

### Breaking

- **Requires `shipbytes/blade-ui` ^1.2.2**, which Composer installs with the
  kit. The design system's contract with an app is three lines of CSS: import
  its `tokens.css`, import its `theme.css`, and `@source` its views so Tailwind
  keeps the classes its components use. The published
  `resources/css/ui-kit.css` carries all three, so `app.css` still imports only
  `./ui-kit.css`. Without the `@source` line every `ds::` component renders as
  unstyled HTML and nothing reports an error.
- **`resources/css/ui-kit-theme.css` is gone**, and with it the `brand-*`
  colour ramp and the Poppins (`font-boldtext`) and Montserrat
  (`font-booktext`) font utilities. The accent is the design system's blue and
  the one font is its Inter, which the kit's three layouts load from
  fonts.bunny.net. Classes such as `bg-brand-600` or `font-boldtext` in an
  app's own views no longer exist. Brand colours are now `--ds-*` token
  overrides in `app.css`, after the `ui-kit.css` import
  (`:root { --ds-accent: … }`, and `.dark { … }` for the dark value).
- **Every published view, and the admin Livewire classes and module models,
  are rewritten.** The layouts, `<x-theme-toggle />`, the session status
  message, the impersonation ribbon and button, the auth pages, the profile
  page and the nine admin screens use `ds::` components and tokens
  (`bg-surface`, `text-fg-muted`, `border-border`, …) that switch for dark mode
  on their own, with no palette classes or `dark:` variants. The five
  paginating admin components use the new pagination trait (below);
  `SupportTicket`, `ContactSubmission` and `ChangelogEntry` gain badge-tone
  methods. An app that edited any of these files must merge its edits into
  the new ones; re-publishing with `--force` and reading `git diff` is the
  way to do it.
- **Nav icons are Heroicons v2 names**, rendered through `ds::icon`, so any
  outline icon on [heroicons.com](https://heroicons.com) works as an `icon` in
  `config/admin.php`. The kit's own entries are now `squares-2x2` (Dashboard),
  `users`, `ticket` (Support), `document-text` (Changelog), `envelope`
  (Contacts) and `clock` (Activity). Of the 0.3 names, `grid`, `note`, `chart`
  and `history` no longer resolve (use `squares-2x2`, `document-text`,
  `chart-bar`, `clock`); `mail` still works through a design-system alias of
  `envelope`; `users`, `ticket`, `clock` and `user-circle` are unchanged. A
  name that does not resolve makes every admin page fail with `Unable to
  locate a class or view for component [heroicon-o-…]`, because the sidebar
  renders on every page; `ui-kit:doctor` names each one. The
  `layouts/partials/nav-icons` partial and its hand-drawn SVGs are gone.
- **`ui-kit.css` now carries two `@source` lines**: the design system's views,
  and the kit's own package views in `vendor/` (`<x-ui-kit::page-header>` and
  friends), which are not published either. A 0.3 `ui-kit.css` has neither.
- **The installer deletes the Laravel 12 skeleton's Instrument Sans.** The
  skeleton's `app.css` ends with an `@theme` block setting `--font-sans` to
  `'Instrument Sans'`. It comes after the kit's import, so it beat the design
  system's Inter, and since nothing loads Instrument Sans the app rendered in
  the system font. `ui-kit:install` (including a re-run on an existing app)
  now deletes that one declaration, and the `@theme` block if nothing else is
  in it. A font the app chose for itself is left alone.
- **Admin lists paginate through
  `Shipbytes\UiKit\Livewire\Concerns\WithDesignSystemPagination`.** The five
  paginating admin components (users, tickets, contacts, changelog, activity)
  use it in place of Livewire's `WithPagination`, so their pages render with
  the design system's Livewire pagination. `ui-kit:doctor` fails while a
  published admin component is still on plain `WithPagination`.

### Added

- **`WithDesignSystemPagination`**, for an app's own Livewire lists too. It
  includes `WithPagination`; the design system's pagination view calls
  `gotoPage`, `previousPage` and `nextPage`, so a page change is a Livewire
  update that keeps the component's search and filters. A component that
  defines `paginationView()` or `paginationSimpleView()` itself keeps its own:
  a class method wins over the trait's.
- **`<x-ui-kit::page-header title="…" subtitle="…">`**, a package component
  with an optional `actions` slot that sits right of the title and wraps under
  it on a phone. Every admin screen opens with it, and an app's own admin
  screens can too.
- **Doctor rows for the design system**, each with a hint that says how to fix
  it: `design system installed`, `ui-kit.css imports the design system`,
  `ui-kit.css scans the kit's own views`, `no leftover ui-kit-theme.css`,
  `app.css does not override the design system font` (skipped, with a
  reminder to load the font, when the app sets a font of its own there),
  `nav icons are Heroicons` (it lists each `label => icon` that doesn't
  resolve) and `admin lists paginate with the design system` (it names each
  published admin component still on plain `WithPagination`).
- `StubStyleTest`: the suite fails when any published view uses a raw palette
  class, a `dark:` variant, an inline `<svg>` or a raw form control instead of
  the design system, or a published stylesheet sets `accent-color`. It reads
  every stub; there is no allowlist.

### Changed

- **The theme follows the OS until the user picks one.** With nothing stored,
  the theme store and the no-flash snippet take the initial theme from
  `prefers-color-scheme` (in 0.3 it was always light), and the toggle stores
  an explicit choice. Both set exactly one of `light` / `dark` on `<html>`:
  the design system's tokens follow the OS whenever neither class is present,
  so a dark-OS user who chose light would otherwise have got dark tokens.
- **The profile page.** The avatar field is a drop zone: the photo shows as a
  round `ds::avatar` (initials when there is none) beside a `ds::file-upload`
  labelled "Profile photo", with Save photo, Cancel and Remove under it; the
  hover overlay on the old square thumbnail is gone. Account deletion confirms
  in `ds::modal`, opened by Alpine state on the form (`confirmingDeletion`)
  instead of the `open-modal` window event; it stays open with the error shown
  after a wrong password and closes with Cancel, the backdrop or Escape. The
  two-factor confirm form shows Fortify's "invalid code" message under the
  code field rather than below the buttons.
- **The nine admin screens.** Each opens with the page header. Lists are
  `ds::table` with `ds::badge` statuses, a `ds::empty-state` row when nothing
  matches, and the design system's pagination. Filters are selects and a
  search field with an icon, and the activity log's filters have visible
  labels. The dashboard and the ticket and contact counts are
  `ds::stat-tile`s; flash messages are `ds::alert`. Rows link through a View
  (or Open) button in a trailing column, with `wire:navigate`; the design
  system has no whole-row link. In particular:
  - The ticket list is a table (Subject, From, Status, Priority, Category,
    Updated) instead of a list of cards; on a ticket, the Status and Priority
    selects share one panel with visible labels.
  - Contact messages are a table with a select column: each row has a
    checkbox labelled "Select" plus the subject for screen readers, the header
    has "Select all", and the bulk bar (Mark read, Mark unread, Archive,
    Delete) appears when anything is selected. A message opens in `ds::modal`,
    driven by the component's `showModal` through `$wire.entangle()`; closing
    it with the ✕, the backdrop or Escape calls `closeModal()` on the server
    as the old close button did.
  - The changelog's Published toggle is `ds::switch` bound with `wire:model`,
    saved with the form like every other field, with a line under it saying
    so. The old toggle saved nothing on its own either, but looked as if it
    did.
  - The activity log drops its Log column: the log name is a small outline
    badge under the event.
  - Badge tones come from one method per concept on the model:
    `SupportTicket::statusTone()` and `priorityTone()`,
    `ContactSubmission::statusTone()`, `ChangelogEntry::categoryTone()`.
- The phone's "More" menu in the admin shell is the design system's bottom
  sheet.
- The design-system releases behind 1.2.2 fix and add what these screens need:
  `tone="danger"` on ghost and secondary buttons (the avatar's Remove is a
  quiet red ghost button), a readonly look for `ds::input` (the
  reset-password email looks fixed instead of editable), `hideLabel` on
  `ds::checkbox` (the contact list's select column), and a `wire:model`
  field's id that stays the same from one render to the next, so a live
  search keeps focus and keeps what was typed after the first response.

### Fixed

- **A profile photo never saved on a standard app.** The profile module wrote
  the file to the public disk and then set `avatar_path` with `update()`,
  which a stock `User` model (whose `$fillable` is name, email and password)
  silently ignores, so the page went straight back to the initials. Remove
  had the same bug. Both now use `forceFill()`. This dates from 0.1; existing
  apps need the new profile view.
- **Dark mode switched off after any `wire:navigate` page change**, including
  the redirect from the login form to the two-factor challenge: Livewire
  copies the next page's `<html>` attributes over the current one and does not
  re-run the head snippet. The theme store now re-applies the theme on every
  swap, before paint. Existing apps need the new `resources/js/ui-kit.js` and
  an asset build.
- **Every `ds::button` inside a Livewire component showed a stray `>` before
  its label** ("> Sign in", with the `>` in its accessible name) on
  blade-ui 1.1: Livewire placed one of its `<!--[if BLOCK]>` markers inside
  the real button tag. Fixed in blade-ui 1.2.0, which 0.4 requires; the page
  tests now fail if a Livewire marker ever lands inside a tag again.
- After Save photo or Cancel, the drop zone still listed the file just saved
  or discarded. It now resets.
- The installer's closing checklist printed the layout tags as `&lt;head&gt;`
  and the build command as `npm install &amp;&amp; npm run dev`. It prints
  `<head>` and `&&` now.
- `ui-kit:install` and `ui-kit:install-module` no longer fail with
  `Undefined constant STDIN` when run outside a CLI script, such as through
  `Artisan::call()` in a web request. Without a STDIN they treat the session
  as non-interactive.

### Removed

- `resources/css/ui-kit-theme.css` and its `@theme` block (the `brand-*`
  palette, `font-boldtext`, `font-booktext`).
- The kit's own `@custom-variant dark`, `[x-cloak]` and `.safe-area-bottom`
  rules. The design system's `theme.css` provides all three.
- The `layouts/partials/nav-icons` partial.
- The `accent-color` rule for native checkboxes and radios in `ui-kit.css`.
  The kit has no native ones left, and the design system's controls paint
  themselves.
- The profile module's `<x-modal>` component
  (`resources/views/components/modal.blade.php`). Nothing in the kit uses it
  now; an app that used it in its own views keeps its published copy.

### Upgrading from 0.3

The same steps as "0.3 to 0.4" on
[shipbytes.dev/docs/laravel-ui-kit/upgrading](https://shipbytes.dev/docs/laravel-ui-kit/upgrading).

1. Start from a clean working tree (`git status` shows nothing). Step 3
   overwrites files your app owns, and step 4 uses `git diff` to get your
   changes back.
2. Require the new minor. `^0.3` never picks up 0.4, and `-W` lets Composer
   add `shipbytes/blade-ui`:

   ```bash
   composer require shipbytes/laravel-ui-kit:^0.4 -W
   ```

3. Re-run the installer in update mode with `--force`, naming every module
   `ui-kit:list-modules` shows as installed (`--modules=all` if that is all
   seven). A module left out drops out of `installed_modules`.

   ```bash
   php artisan ui-kit:list-modules
   php artisan ui-kit:install --force --modules=admin-middleware,support-tickets,changelog,contacts,profile,impersonation,activity-log
   ```

   It re-publishes `config/ui-kit.php`, `config/admin.php`, the route files,
   every view and layout, the Livewire classes, the module models and
   factories, `resources/css/ui-kit.css` and `resources/js/ui-kit.js`, then
   re-applies every module's routes, nav entries and middleware swap between
   the markers and records the modules again. That replaces the kit's nav
   icon names, puts the pagination trait in the admin lists, and deletes the
   skeleton's Instrument Sans block from `app.css`. Migrations that already
   exist are not published again, and the database is only migrated forward.
4. Read `git diff` and bring back what was yours: entries you added to
   `config/admin.php` outside the `ui-kit:nav` markers, values you changed in
   `config/ui-kit.php` (brand, copy, features), and edits to views, Livewire
   classes or models. Re-apply view edits to the new markup rather than
   restoring the old file: the 0.3 markup uses classes that no longer exist.
5. Delete the 0.3 theme file and the old icon partial, which nothing uses now:

   ```bash
   rm resources/css/ui-kit-theme.css resources/views/layouts/partials/nav-icons.blade.php
   ```

6. If you changed tokens in `ui-kit-theme.css`, move them into
   `resources/css/app.css`, after the `ui-kit.css` import, as design-system
   tokens:

   ```diff
    @import 'tailwindcss';
    @import './ui-kit.css';
   +
   +:root { --ds-accent: #0d9488; --ds-accent-hover: #0f766e; }
   +.dark { --ds-accent: #2dd4bf; --ds-accent-hover: #5eead4; }
   ```

7. Rename the icons of your own nav entries to Heroicons v2 names:
   `grid` → `squares-2x2`, `note` → `document-text`, `chart` → `chart-bar`,
   `history` → `clock` (`mail` still works; `envelope` is its real name).
   Until you do, an entry with a 0.3 name makes every admin page fail with
   `Unable to locate a class or view for component [heroicon-o-…]`. The
   doctor's `nav icons are Heroicons` row names each one.

   ```diff
   -['label' => 'Reports', 'route' => 'admin.reports', 'icon' => 'chart'],
   +['label' => 'Reports', 'route' => 'admin.reports', 'icon' => 'chart-bar'],
   ```

8. In your own views, replace `brand-*` classes with design-system token
   classes such as `bg-accent` and `text-accent`, and `font-boldtext` or
   `font-booktext` with `font-semibold` or nothing. Your own Livewire lists
   can use `WithDesignSystemPagination` in place of `WithPagination`. A layout
   of your own that should look like the kit's needs `<x-ui-kit::head />` and
   Inter in its `<head>`:

   ```blade
   <link rel="preconnect" href="https://fonts.bunny.net">
   <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
   ```

9. Build the assets:

   ```bash
   npm run build
   ```

10. Check the result:

    ```bash
    php artisan ui-kit:doctor
    ```

    Every row passes; `mail from address configured` stays a skip until
    `MAIL_FROM_ADDRESS` is set.

### Known

- A submit button with `wire:loading.attr="disabled"` drops keyboard focus to
  `<body>` while its request runs, because the browser moves focus off a
  control that becomes disabled. Keyboard users tab from the top of the page
  again after submitting a form. Not fixed in 0.4.0.

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

[Unreleased]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.5.1...HEAD
[0.5.1]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.2...v0.3.0
[0.2.2]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/shipbytes/laravel-ui-kit/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/shipbytes/laravel-ui-kit/releases/tag/v0.1.0

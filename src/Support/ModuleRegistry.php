<?php

namespace Shipbytes\UiKit\Support;

class ModuleRegistry
{
    /**
     * Canonical catalogue of optional modules.
     *
     * Keys are module slugs (passed to `ui-kit:install-module`), values are
     * metadata used by the installers, module listing, and the interactive picker.
     *
     * @var array<string, array{label: string, summary: string, composer?: array<int, string>, npm?: array<int, string>, providers?: array<int, string>, depends?: array<int, string>, post_install_notes?: array<int, string>}>
     */
    protected array $modules = [
        'admin-middleware' => [
            'label' => 'Admin middleware + Spatie Permissions',
            'summary' => 'Route gating via roles/permissions. Falls back to an is_admin boolean column if skipped.',
            'composer' => ['spatie/laravel-permission:^6.0'],
            'post_install_notes' => [
                'Add `use App\\Models\\Concerns\\IsAdminUser;` to your User model and declare `use IsAdminUser;` inside the class.',
                'Swap the middleware in config/admin.php from `EnsureIsAdminFallback::class` to `App\\Http\\Middleware\\EnsureUserIsAdmin::class`.',
                'Publish Spatie config & migrations: `php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"`.',
                'Run `php artisan migrate` then `php artisan db:seed --class=Database\\\\Seeders\\\\AdminRoleSeeder`.',
                'Assign the role to a user: `$user->assignRole(\'admin\');`.',
            ],
        ],
        'support-tickets' => [
            'label' => 'Support Tickets',
            'summary' => 'Admin ticket queue with replies, status filters, and open-count sidebar badge.',
            'post_install_notes' => [
                'Add routes to routes/admin.php:  Route::get(\'support\', \App\Livewire\Admin\Support\TicketList::class)->name(\'support.index\');  Route::get(\'support/{ulid}\', \App\Livewire\Admin\Support\TicketDetail::class)->name(\'support.show\');',
                'Add a nav entry to config/admin.php:  [\'label\' => \'Support\', \'route\' => \'admin.support.index\', \'icon\' => \'ticket\', \'badge\' => \'open_tickets\'].',
                'Run `php artisan migrate` to create the support_tickets and ticket_replies tables.',
                'Optional: bind your own SidebarBadgeResolver that returns [\'open_tickets\' => SupportTicket::open()->count()].',
                'Optional: wire email notifications — the module ships without Mailables so you can plug in your own (e.g. `TicketReplyNotification`, `TicketStatusChanged`).',
            ],
        ],
        'changelog' => [
            'label' => 'Changelog',
            'summary' => 'Admin-authored changelog entries with a public feed endpoint.',
            'composer' => ['mews/purifier:^3.4'],
            'post_install_notes' => [
                'Add admin routes to routes/admin.php:  Route::get(\'changelog\', \App\Livewire\Admin\Changelog\ChangelogList::class)->name(\'changelog.index\');  Route::get(\'changelog/create\', \App\Livewire\Admin\Changelog\ChangelogForm::class)->name(\'changelog.create\');  Route::get(\'changelog/{ulid}/edit\', \App\Livewire\Admin\Changelog\ChangelogForm::class)->name(\'changelog.edit\');',
                'Optionally expose a public feed: Route::get(\'changelog\', fn () => view(\'changelog.public\', [\'entries\' => \App\Models\ChangelogEntry::published()->orderBy(\'published_at\', \'desc\')->get()]))->name(\'changelog.public\');',
                'Add a nav entry to config/admin.php:  [\'label\' => \'Changelog\', \'route\' => \'admin.changelog.index\', \'icon\' => \'note\'].',
                'Run `php artisan migrate` to create the changelog_entries table.',
                'Publish mews/purifier config: `php artisan vendor:publish --provider="Mews\\Purifier\\PurifierServiceProvider"`.',
            ],
        ],
        'contacts' => [
            'label' => 'Contacts',
            'summary' => 'Inbox for public contact-form submissions.',
            'post_install_notes' => [
                'Add admin route to routes/admin.php:  Route::get(\'contacts\', \App\Livewire\Admin\Contacts\ContactList::class)->name(\'contacts.index\');',
                'Wire up a public contact form that writes to `\App\Models\ContactSubmission` (the module only ships the admin inbox).',
                'Add a nav entry to config/admin.php:  [\'label\' => \'Contacts\', \'route\' => \'admin.contacts.index\', \'icon\' => \'mail\', \'badge\' => \'unread_contacts\'].',
                'Run `php artisan migrate` to create the contact_submissions table.',
                'Optional: plug in your own `ContactReply` Mailable and uncomment the Mail::to() call in `ContactList@submitReply`.',
                'The "Copy to Ticket" button auto-appears when the support-tickets module is installed.',
            ],
        ],
        'analytics' => [
            'label' => 'Analytics',
            'summary' => 'Product analytics: UTM tracking, GA4, and/or PostHog. Pick providers at install time.',
            'providers' => ['utm', 'ga4', 'posthog'],
            'post_install_notes' => [
                'UTM: register the middleware globally in bootstrap/app.php (L11+) or app/Http/Kernel.php (L10):  \App\Http\Middleware\CaptureUtmParameters::class.',
                'UTM: add admin route:  Route::get(\'analytics/utm\', \App\Livewire\Admin\Analytics\UtmLinkBuilder::class)->name(\'analytics.utm\');',
                'UTM: add a nav entry to config/admin.php:  [\'label\' => \'UTM Links\', \'route\' => \'admin.analytics.utm\', \'icon\' => \'link\'].',
                'UTM: run `php artisan migrate` to add utm_* columns to users and create the utm_links table.',
                'GA4: set `GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX` in .env and add `@include(\'partials.ga4\')` to resources/views/layouts/app.blade.php.',
                'PostHog: set `POSTHOG_PUBLIC_KEY` (and optionally `POSTHOG_HOST`) in .env, add `@include(\'partials.posthog\')` to the layout, and `npm install posthog-js` then `import \'./posthog-bridge.js\'` in resources/js/app.js.',
                'Both GA4 and PostHog loaders gate on a `cookie_consent=accepted` cookie — set that cookie from your consent banner to activate tracking.',
                'Add `services.google.analytics_id`, `services.posthog.public_key`, and `services.posthog.host` keys to config/services.php.',
            ],
        ],
        'profile' => [
            'label' => 'Profile page',
            'summary' => 'Self-service name/email/password/avatar + 2FA toggle.',
            'post_install_notes' => [
                'Add a user-facing route to routes/web.php:  Route::get(\'profile\', \App\Livewire\Profile\ProfilePage::class)->middleware([\'auth\'])->name(\'profile\');',
                'Add a nav entry (under the user menu or sidebar) pointing to route(\'profile\').',
                'Run `php artisan migrate` to add the avatar_path column to the users table.',
                'Run `php artisan storage:link` so uploaded avatars are publicly accessible at /storage/avatars/...',
                'Optional: `composer require intervention/image` to enable automatic 200×200 avatar resizing (otherwise the raw upload is stored as-is).',
                'Optional: enable 2FA by publishing Fortify (`php artisan vendor:publish --provider="Laravel\\Fortify\\FortifyServiceProvider"`) and adding `Features::twoFactorAuthentication()` to config/fortify.php. The 2FA card is hidden automatically when Fortify isn\'t installed.',
                'The module ships `x-modal` and `x-action-message` Blade components — remove them from resources/views/components/ if you already have Breeze\'s versions.',
            ],
        ],
        'impersonation' => [
            'label' => 'Impersonation (admin-as-user)',
            'summary' => 'Login-as-user from admin, with an exit-impersonation ribbon.',
            'composer' => ['lab404/laravel-impersonate:^1.7'],
            'post_install_notes' => [
                'Publish the package config: `php artisan vendor:publish --tag="impersonate"`.',
                'Add `use Lab404\\Impersonate\\Models\\Impersonate;` at the top of App\\Models\\User and declare `use Impersonate;` inside the class.',
                'Define `canImpersonate()` on User (e.g. return $this->hasRole(\'admin\') or $this->is_admin) and `canBeImpersonated()` (e.g. return ! $this->hasRole(\'admin\')).',
                'Include the banner at the top of resources/views/layouts/admin-sidebar.blade.php (and any user-facing layout):  @include(\'partials.impersonation-banner\').',
                'Use the button in the users list/detail view:  @include(\'partials.impersonation-button\', [\'user\' => $user]).',
                'The package auto-registers routes at /impersonate/take/{id} and /impersonate/leave — no additional routing needed.',
            ],
        ],
        'activity-log' => [
            'label' => 'Activity log',
            'summary' => 'Audit trail via spatie/laravel-activitylog + admin viewer.',
            'composer' => ['spatie/laravel-activitylog:^4.8'],
            'post_install_notes' => [
                'Publish & run package migrations: `php artisan vendor:publish --provider="Spatie\\Activitylog\\ActivitylogServiceProvider" --tag="activitylog-migrations"` then `php artisan migrate`.',
                'Add admin route to routes/admin.php:  Route::get(\'activity\', \App\Livewire\Admin\Activity\ActivityViewer::class)->name(\'activity.index\');',
                'Add a nav entry to config/admin.php:  [\'label\' => \'Activity\', \'route\' => \'admin.activity.index\', \'icon\' => \'clock\'].',
                'On each model you want logged, add the `Spatie\\Activitylog\\Traits\\LogsActivity` trait and a `getActivitylogOptions()` method (see the package README).',
                'Set `causer_id` automatically by being authenticated when the model is saved (the package auto-detects auth()->user() as the causer).',
            ],
        ],
        'dark-mode' => [
            'label' => 'Dark mode',
            'summary' => 'Theme store with localStorage persistence and dark: variants across the shell.',
            'post_install_notes' => [
                'The Alpine `$store.theme` store ships in core/js/ui-kit.js — no extra JS import needed.',
                'Add `darkMode: \'class\'` to tailwind.config.js (the shipped tailwind-preset.js already sets this).',
                'Include the toggle in your header: <x-theme-toggle /> (e.g. inside layouts/admin-sidebar.blade.php next to the user dropdown).',
                'To avoid a light-flash on load, inline this before </head>:  <script>(()=>{const t=localStorage.getItem(\'ui-kit.theme\'); if(t===\'dark\') document.documentElement.classList.add(\'dark\');})();</script>',
                'All core views and every shipped module already include dark: variants — no manual audit required.',
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $slug): bool
    {
        return isset($this->modules[$slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $slug): array
    {
        if (! $this->has($slug)) {
            throw new \InvalidArgumentException("Unknown module: {$slug}");
        }

        return $this->modules[$slug];
    }

    public function isInstalled(string $slug): bool
    {
        $installed = config('ui-kit.installed_modules', []);

        if (is_string($slug) && str_contains($slug, ':')) {
            [$module, $provider] = explode(':', $slug, 2);

            return in_array($provider, $installed[$module] ?? [], true);
        }

        return array_key_exists($slug, $installed);
    }
}

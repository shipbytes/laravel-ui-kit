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
     * Recognised keys (all optional except label + summary):
     *   - composer: composer packages to require before copying stubs
     *   - admin_routes: array of route lines to inject between
     *       /* ui-kit:admin-routes-start *\/  ...  /* ui-kit:admin-routes-end *\/
     *     in routes/admin.php
     *   - admin_nav: array of nav-array entries to merge into config/admin.php
     *   - user_routes: array of route lines to inject into routes/ui-kit-user.php
     *   - admin_middleware_swap: when true, replace EnsureIsAdminFallback::class
     *       with App\Http\Middleware\EnsureUserIsAdmin::class
     *   - artisan_publish: list of vendor:publish argument arrays
     *   - artisan_seed: list of seeder class names
     *   - storage_link: when true, run php artisan storage:link
     *   - post_install_notes: residual manual steps the installer can't automate
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $modules = [
        'admin-middleware' => [
            'label' => 'Admin middleware + Spatie Permissions',
            'summary' => 'Route gating via roles/permissions. Falls back to an is_admin boolean column if skipped.',
            'composer' => ['spatie/laravel-permission:^6.10'],
            'admin_middleware_swap' => true,
            'artisan_publish' => [
                ['--provider' => 'Spatie\\Permission\\PermissionServiceProvider'],
            ],
            'artisan_seed' => ['Database\\Seeders\\AdminRoleSeeder'],
            'post_install_notes' => [
                'Grant a user the admin role: `php artisan ui-kit:make-admin you@example.com`.',
            ],
        ],
        'support-tickets' => [
            'label' => 'Support Tickets',
            'summary' => 'Admin ticket queue with replies, status filters, and open-count sidebar badge.',
            'admin_routes' => [
                "Route::get('support', \\App\\Livewire\\Admin\\Support\\TicketList::class)->name('support.index');",
                "Route::get('support/{ulid}', \\App\\Livewire\\Admin\\Support\\TicketDetail::class)->name('support.show');",
            ],
            'admin_nav' => [
                ['label' => 'Support', 'route' => 'admin.support.index', 'icon' => 'ticket', 'badge' => 'open_tickets'],
            ],
            'post_install_notes' => [
                'Optional: bind your own SidebarBadgeResolver returning [\'open_tickets\' => SupportTicket::open()->count()].',
                'Optional: wire email notifications — the module ships without Mailables so you can plug in your own.',
            ],
        ],
        'changelog' => [
            'label' => 'Changelog',
            'summary' => 'Admin-authored changelog entries with a public feed endpoint.',
            'composer' => ['mews/purifier:^3.4'],
            'admin_routes' => [
                "Route::get('changelog', \\App\\Livewire\\Admin\\Changelog\\ChangelogList::class)->name('changelog.index');",
                "Route::get('changelog/create', \\App\\Livewire\\Admin\\Changelog\\ChangelogForm::class)->name('changelog.create');",
                "Route::get('changelog/{ulid}/edit', \\App\\Livewire\\Admin\\Changelog\\ChangelogForm::class)->name('changelog.edit');",
            ],
            'admin_nav' => [
                ['label' => 'Changelog', 'route' => 'admin.changelog.index', 'icon' => 'note'],
            ],
            'artisan_publish' => [
                ['--provider' => 'Mews\\Purifier\\PurifierServiceProvider'],
            ],
            'post_install_notes' => [
                'Optional public feed: add to routes/web.php — Route::get(\'changelog\', fn () => view(\'changelog.public\', [\'entries\' => \\App\\Models\\ChangelogEntry::published()->orderBy(\'published_at\', \'desc\')->get()]))->name(\'changelog.public\'); (you supply the changelog.public view).',
            ],
        ],
        'contacts' => [
            'label' => 'Contacts',
            'summary' => 'Inbox for public contact-form submissions.',
            'admin_routes' => [
                "Route::get('contacts', \\App\\Livewire\\Admin\\Contacts\\ContactList::class)->name('contacts.index');",
            ],
            'admin_nav' => [
                ['label' => 'Contacts', 'route' => 'admin.contacts.index', 'icon' => 'mail', 'badge' => 'unread_contacts'],
            ],
            'post_install_notes' => [
                'Wire up a public contact form that writes to `\\App\\Models\\ContactSubmission` (the module only ships the admin inbox).',
                'Optional: plug in your own `ContactReply` Mailable and uncomment the Mail::to() call in `ContactList@submitReply`.',
                'The "Copy to Ticket" button auto-appears when the support-tickets module is installed.',
            ],
        ],
        'profile' => [
            'label' => 'Profile page',
            'summary' => 'Self-service name/email/password/avatar + two-factor authentication.',
            'user_routes' => [
                "Route::get('profile', \\App\\Livewire\\Profile\\ProfilePage::class)->name('profile');",
            ],
            'storage_link' => true,
            'post_install_notes' => [
                'Optional: `composer require intervention/image` to enable automatic 200×200 avatar resizing.',
            ],
        ],
        'impersonation' => [
            'label' => 'Impersonation (admin-as-user)',
            'summary' => 'Login-as-user from admin, with an exit-impersonation ribbon.',
            'composer' => ['lab404/laravel-impersonate:^1.7.5'],
            'artisan_publish' => [
                ['--tag' => 'impersonate'],
            ],
            'post_install_notes' => [
                'Add `@include(\'partials.impersonation-button\', [\'user\' => $user])` inside your users list/detail view next to each user row.',
            ],
        ],
        'activity-log' => [
            'label' => 'Activity log',
            'summary' => 'Audit trail via spatie/laravel-activitylog + admin viewer.',
            'composer' => ['spatie/laravel-activitylog:^4.10'],
            'admin_routes' => [
                "Route::get('activity', \\App\\Livewire\\Admin\\Activity\\ActivityViewer::class)->name('activity.index');",
            ],
            'admin_nav' => [
                ['label' => 'Activity', 'route' => 'admin.activity.index', 'icon' => 'clock'],
            ],
            'artisan_publish' => [
                ['--provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider', '--tag' => 'activitylog-migrations'],
            ],
            'post_install_notes' => [
                'On each model you want logged, add the `Spatie\\Activitylog\\Traits\\LogsActivity` trait and a `getActivitylogOptions()` method (see the package README).',
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
        return in_array($slug, config('ui-kit.installed_modules', []), true);
    }
}

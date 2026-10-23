<?php

namespace Shipbytes\UiKit\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\DefaultBadgeResolver;
use Shipbytes\UiKit\Support\ModuleRegistry;

class DoctorCommand extends Command
{
    protected $signature = 'ui-kit:doctor';

    protected $description = 'Check that the UI Kit install is fully wired and spot common misconfigurations.';

    /** @var array<int, array{0: string, 1: string, 2: string}> */
    protected array $results = [];

    protected bool $failed = false;

    public function handle(): int
    {
        // The container hands Artisan::call() the same instance every time,
        // so a second run in one process would inherit the first one's rows.
        $this->results = [];
        $this->failed = false;

        $this->checkPublishedFiles();
        $this->checkPublicRoutesBlock();
        $this->checkLivewire();
        $this->checkFortify();
        $this->checkViteWiring();
        $this->checkDesignSystem();
        $this->checkUserTrait();
        $this->checkModuleRequirements();
        $this->checkModuleWiring();
        $this->checkDuplicateRouteNames();
        $this->checkTrustedProxies();
        $this->checkMail();

        $this->table(['Check', 'Status', 'Hint'], $this->results);

        if ($this->failed) {
            $this->error('Some checks failed — see the hints above.');

            return self::FAILURE;
        }

        $this->info('All checks passed.');

        return self::SUCCESS;
    }

    protected function passCheck(string $check): void
    {
        $this->results[] = [$check, '<info>✓ pass</info>', ''];
    }

    protected function failCheck(string $check, string $hint): void
    {
        $this->failed = true;
        $this->results[] = [$check, '<fg=red>✗ fail</>', $hint];
    }

    protected function skipCheck(string $check, string $why): void
    {
        $this->results[] = [$check, '<comment>– skip</comment>', $why];
    }

    protected function checkPublishedFiles(): void
    {
        $expected = [
            'config/ui-kit.php' => 'php artisan vendor:publish --tag=ui-kit-config',
            'config/admin.php' => 'php artisan vendor:publish --tag=ui-kit-config',
            'routes/auth.php' => 'php artisan vendor:publish --tag=ui-kit-routes',
            'routes/admin.php' => 'php artisan vendor:publish --tag=ui-kit-routes',
            'routes/ui-kit-user.php' => 'php artisan vendor:publish --tag=ui-kit-routes',
            'resources/css/ui-kit.css' => 'php artisan vendor:publish --tag=ui-kit-assets',
            'resources/js/ui-kit.js' => 'php artisan vendor:publish --tag=ui-kit-assets',
        ];

        foreach ($expected as $file => $hint) {
            if (file_exists(base_path($file))) {
                $this->passCheck("{$file} published");
            } else {
                $this->failCheck("{$file} published", $hint);
            }
        }

        foreach (['routes/auth.php', 'routes/admin.php', 'routes/ui-kit-user.php'] as $file) {
            $path = base_path($file);

            if (! file_exists($path)) {
                continue;
            }

            if (str_contains((string) file_get_contents($path), 'ui-kit:managed')) {
                $this->passCheck("{$file} auto-loaded (ui-kit:managed)");
            } else {
                $this->skipCheck("{$file} auto-loaded", 'no ui-kit:managed header — load it yourself or re-publish');
            }
        }
    }

    /**
     * 0.5 split routes/ui-kit-user.php into a web-only group for pages a guest
     * opens (/contact, /changelog) above the web + auth one. The installer
     * never overwrites the file without --force, so an upgraded app keeps the
     * old single group, and the installer can only warn that it skipped them.
     */
    protected function checkPublicRoutesBlock(): void
    {
        $check = 'routes/ui-kit-user.php has the public-routes block';
        $path = base_path('routes/ui-kit-user.php');

        if (! file_exists($path)) {
            $this->skipCheck($check, 'routes/ui-kit-user.php not published');

            return;
        }

        $contents = (string) file_get_contents($path);

        if (str_contains($contents, '/* ui-kit:public-routes-start */') && str_contains($contents, '/* ui-kit:public-routes-end */')) {
            $this->passCheck($check);

            return;
        }

        $registry = app(ModuleRegistry::class);
        $needs = array_values(array_filter(
            (array) config('ui-kit.installed_modules', []),
            fn ($slug) => is_string($slug) && $registry->has($slug) && ! empty($registry->get($slug)['public_routes'])
        ));

        $hint = 'the file predates 0.5: re-run php artisan ui-kit:install --force --modules=<the installed ones> (it overwrites every published file, so commit first), '
            ."or add Route::middleware(['web'])->group(function () { /* ui-kit:public-routes-start */ /* ui-kit:public-routes-end */ }); above the auth group";

        if ($needs === []) {
            $this->skipCheck($check, 'no installed module adds a public page yet; when one does, '.$hint);

            return;
        }

        $this->failCheck($check, $hint.' and run php artisan ui-kit:install-module '.implode(' ', $needs));
    }

    /**
     * 0.3 moved the kit from Livewire 3 + Volt to Livewire 4. An app upgraded
     * from 0.2 keeps booting through the VoltCompat shim, so these checks are
     * what tell the user the migration is unfinished.
     */
    protected function checkLivewire(): void
    {
        // version_compare on the normalized version rather than
        // InstalledVersions::satisfies(): that needs composer/semver, which a
        // host app doesn't necessarily have installed.
        $version = InstalledVersions::isInstalled('livewire/livewire')
            ? (string) InstalledVersions::getVersion('livewire/livewire')
            : '';

        if (version_compare($version, '4.0.0.0-dev', '>=') && version_compare($version, '5.0.0.0-dev', '<')) {
            $this->passCheck('Livewire 4 installed');
        } else {
            $this->failCheck('Livewire 4 installed', 'composer require livewire/livewire:^4.0');
        }

        $routes = base_path('routes/auth.php');

        if (! file_exists($routes)) {
            $this->skipCheck('routes/auth.php uses Route::livewire', 'routes/auth.php not published');
        } elseif (str_contains((string) file_get_contents($routes), 'Volt::')) {
            $this->failCheck('routes/auth.php uses Route::livewire', 'in routes/auth.php replace `Volt::route(` with `Route::livewire(` and delete the `use Livewire\Volt\Volt;` line. The kit\'s Volt shim keeps the old file working until then.');
        } else {
            $this->passCheck('routes/auth.php uses Route::livewire');
        }

        $this->checkVoltViews();

        $providers = base_path('bootstrap/providers.php');
        $listed = file_exists($providers) && str_contains((string) file_get_contents($providers), 'VoltServiceProvider');

        if ($listed || file_exists(app_path('Providers/VoltServiceProvider.php'))) {
            $this->failCheck('no Volt service provider', 'remove VoltServiceProvider from bootstrap/providers.php, delete app/Providers/VoltServiceProvider.php, then composer remove livewire/volt');
        } else {
            $this->passCheck('no Volt service provider');
        }
    }

    protected function checkVoltViews(): void
    {
        $dir = resource_path('views/livewire');

        if (! is_dir($dir)) {
            $this->skipCheck('views free of Volt imports', 'resources/views/livewire does not exist');

            return;
        }

        $offending = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (str_ends_with($file->getFilename(), '.blade.php')
                && str_contains((string) file_get_contents($file->getPathname()), 'Livewire\Volt')) {
                $offending[] = 'resources/views/livewire/'.str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            }
        }

        if ($offending === []) {
            $this->passCheck('views free of Volt imports');

            return;
        }

        sort($offending);
        $listed = implode(', ', array_slice($offending, 0, 5));

        if (count($offending) > 5) {
            $listed .= ' and '.(count($offending) - 5).' more';
        }

        $this->failCheck('views free of Volt imports', "replace `use Livewire\\Volt\\Component;` with `use Livewire\\Component;` in {$listed}");
    }

    protected function checkFortify(): void
    {
        $path = config_path('fortify.php');

        if (! file_exists($path)) {
            $this->failCheck('config/fortify.php published', 'run php artisan ui-kit:install (or publish fortify-config) — without views=false, /register 500s');

            return;
        }

        $contents = (string) file_get_contents($path);

        if (str_contains($contents, "'views' => false")) {
            $this->passCheck('fortify views disabled');
        } else {
            $this->failCheck('fortify views disabled', "set 'views' => false in config/fortify.php so Fortify doesn't collide with the kit's auth pages");
        }

        // Fortify's own POST /login redirects a two-factor user to this name;
        // routes/auth.php from before 0.4.1 lacks it, so that redirect 500s.
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            $this->skipCheck('two-factor.login route registered', 'two-factor authentication is off');
        } elseif (Route::has('two-factor.login')) {
            $this->passCheck('two-factor.login route registered');
        } else {
            $this->failCheck('two-factor.login route registered', "add Route::redirect('two-factor-login', '/two-factor-challenge')->name('two-factor.login'); beside the two-factor-challenge route in routes/auth.php, or re-run php artisan ui-kit:install --force --modules=<the installed ones> (Fortify's POST /login sends two-factor users there)");
        }

        // Read the live config, not the file: a cached config is what serves.
        $live = array_values(array_filter(
            InstallCommand::FORTIFY_FEATURES_OFF,
            fn (string $feature) => Features::enabled(Features::{$feature}())
        ));

        if ($live === []) {
            $this->passCheck('fortify features limited to two-factor');
        } else {
            $lines = implode(', ', array_map(fn (string $feature) => "Features::{$feature}()", $live));

            $this->failCheck('fortify features limited to two-factor', "comment out {$lines} in config/fortify.php: their POST/PUT endpoints call actions no kit app binds and 500 (the kit's own pages do those jobs), or re-run php artisan ui-kit:install");
        }
    }

    protected function checkViteWiring(): void
    {
        $css = resource_path('css/app.css');

        if (file_exists($css) && str_contains((string) file_get_contents($css), 'ui-kit.css')) {
            $this->passCheck('ui-kit.css imported from app.css');
        } else {
            $this->failCheck('ui-kit.css imported from app.css', "add @import './ui-kit.css'; after the tailwindcss import");
        }

        $js = resource_path('js/app.js');

        if (file_exists($js) && str_contains((string) file_get_contents($js), './ui-kit')) {
            $this->passCheck('ui-kit.js imported from app.js');
        } else {
            $this->failCheck('ui-kit.js imported from app.js', "add import './ui-kit'; to resources/js/app.js");
        }
    }

    /**
     * 0.4 moved the kit onto the Shipbytes design system. An app upgraded from
     * 0.3 keeps its old ui-kit.css, which imports the kit's own theme file and
     * never reaches the design system's tokens, and its custom nav entries
     * keep icon names from the old partial. The first throws nothing and
     * renders unstyled components; the second makes every admin page fail,
     * since ds::icon cannot find heroicon-o-<name> and the sidebar is on all
     * of them, with an error that never mentions config/admin.php.
     */
    protected function checkDesignSystem(): void
    {
        if (file_exists(base_path('vendor/shipbytes/blade-ui/dist/theme.css'))) {
            $this->passCheck('design system installed');
        } else {
            $this->failCheck('design system installed', 'composer require shipbytes/blade-ui');
        }

        $css = resource_path('css/ui-kit.css');

        if (! file_exists($css)) {
            $this->skipCheck('ui-kit.css imports the design system', 'resources/css/ui-kit.css not published');
        } else {
            $contents = (string) file_get_contents($css);

            if (str_contains($contents, 'blade-ui/dist/theme.css') && str_contains($contents, '@source')) {
                $this->passCheck('ui-kit.css imports the design system');
            } else {
                $this->failCheck('ui-kit.css imports the design system', 'php artisan vendor:publish --tag=ui-kit-assets --force (the 0.3 file imported ./ui-kit-theme.css instead)');
            }

            // The admin screens' page header is a package view, so its classes
            // exist only if Tailwind scans the kit in vendor/. Without it the
            // header renders with whatever classes a published view happens to
            // share, which looks almost right.
            if (str_contains($contents, 'laravel-ui-kit/resources/views')) {
                $this->passCheck("ui-kit.css scans the kit's own views");
            } else {
                $this->failCheck("ui-kit.css scans the kit's own views", 'php artisan vendor:publish --tag=ui-kit-assets --force (ui-kit.css from before 0.4 lacks the @source line for vendor/shipbytes/laravel-ui-kit/resources/views)');
            }
        }

        if (file_exists(resource_path('css/ui-kit-theme.css'))) {
            $this->failCheck('no leftover ui-kit-theme.css', 'delete resources/css/ui-kit-theme.css: tokens now come from the design system, and any overrides belong in app.css after the ui-kit.css import (:root { --ds-accent: … })');
        } else {
            $this->passCheck('no leftover ui-kit-theme.css');
        }

        $this->checkFontOverride();
        $this->checkNavIcons();
        $this->checkAdminPagination();
        $this->checkSidebarBadges();
    }

    /**
     * A nav entry's `badge` key names a count the bound resolver returns. A
     * key the resolver never returns shows no badge, which looks exactly like
     * a count of zero, so nothing ever says the badge is unwired.
     */
    protected function checkSidebarBadges(): void
    {
        $check = 'sidebar badges resolve';
        $keys = [];

        foreach ((array) config('admin.nav', []) as $item) {
            if (is_array($item) && isset($item['badge']) && is_string($item['badge'])) {
                $keys[$item['badge']] = $item['label'] ?? $item['badge'];
            }
        }

        if ($keys === []) {
            $this->skipCheck($check, 'no nav entry declares a badge');

            return;
        }

        try {
            $resolver = app(SidebarBadgeResolver::class);
            $counts = $resolver->counts();
        } catch (\Throwable $e) {
            $this->skipCheck($check, 'the resolver could not count: '.$e->getMessage());

            return;
        }

        $missing = array_diff_key($keys, $counts);

        if ($missing === []) {
            $this->passCheck($check);

            return;
        }

        $failures = $resolver instanceof DefaultBadgeResolver ? array_intersect_key($resolver->failures, $missing) : [];

        if ($failures !== []) {
            $this->failCheck($check, 'could not count '.implode('; ', array_map(fn ($key, $why) => "{$key}: {$why}", array_keys($failures), $failures)).'. Run php artisan migrate, or remove the module\'s nav entry from config/admin.php');

            return;
        }

        $listed = implode(', ', array_map(fn ($key, $label) => "{$label} => {$key}", array_keys($missing), $missing));

        $this->failCheck($check, $resolver::class." returns no count for {$listed}: return those keys from your SidebarBadgeResolver, or remove your binding to use the kit's DefaultBadgeResolver (it counts open_tickets and unread_contacts when their modules are installed)");
    }

    /**
     * A --font-sans declared after the ui-kit.css import beats the design
     * system's Inter. The Laravel 12 skeleton's is Instrument Sans, which
     * nothing loads, so the app renders in the system font and nothing says so.
     */
    protected function checkFontOverride(): void
    {
        $check = 'app.css does not override the design system font';
        $css = resource_path('css/app.css');
        $contents = file_exists($css) ? (string) file_get_contents($css) : '';

        if (! preg_match("/^@import\s+['\"](?:\.\/)?ui-kit\.css['\"].*$/m", $contents, $import, PREG_OFFSET_CAPTURE)) {
            $this->skipCheck($check, 'app.css does not import ui-kit.css');

            return;
        }

        $after = substr($contents, $import[0][1] + strlen($import[0][0]));

        if (preg_match("/--font-sans:\s*['\"]?Instrument Sans/", $after)) {
            $this->failCheck($check, "delete the @theme { --font-sans: 'Instrument Sans', … } block from resources/css/app.css (nothing loads that font), or re-run php artisan ui-kit:install");
        } elseif (preg_match('/--font-sans\s*:/', $after)) {
            $this->skipCheck($check, 'you override the font; make sure it is loaded');
        } else {
            $this->passCheck($check);
        }
    }

    /**
     * The published admin lists page through WithDesignSystemPagination. One
     * still on plain WithPagination renders Livewire's own Tailwind pager,
     * palette colours and all, which works and looks foreign; nothing errors.
     */
    protected function checkAdminPagination(): void
    {
        $check = 'admin lists paginate with the design system';
        $dir = app_path('Livewire/Admin');

        if (! is_dir($dir)) {
            $this->skipCheck($check, 'app/Livewire/Admin not published');

            return;
        }

        $stale = [];

        foreach ((new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS))) as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            if (preg_match('/^\s*use\s+WithPagination\s*;/m', $contents) && ! str_contains($contents, 'function paginationView')) {
                $stale[] = $file->getBasename('.php');
            }
        }

        if ($stale === []) {
            $this->passCheck($check);
        } else {
            $this->failCheck($check, implode(', ', $stale).': replace `use WithPagination;` with `use \\Shipbytes\\UiKit\\Livewire\\Concerns\\WithDesignSystemPagination;`, or republish with php artisan ui-kit:install --force');
        }
    }

    protected function checkNavIcons(): void
    {
        $svgDir = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg');
        $aliases = (array) config('blade-ui.icon_aliases', []);
        $bad = [];

        foreach ((array) config('admin.nav', []) as $item) {
            if (! is_array($item) || ! isset($item['icon']) || ! is_string($item['icon'])) {
                continue;
            }

            $icon = $item['icon'];

            if (! file_exists("{$svgDir}/o-{$icon}.svg") && ! array_key_exists($icon, $aliases)) {
                $bad[] = ($item['label'] ?? '?').' => '.$icon;
            }
        }

        if ($bad === []) {
            $this->passCheck('nav icons are Heroicons');
        } else {
            $this->failCheck('nav icons are Heroicons', 'not found: '.implode(', ', $bad).'. 0.4 switched nav icons to Heroicons v2 names (heroicons.com); e.g. grid → squares-2x2, note → document-text, mail → envelope, history → clock');
        }
    }

    protected function checkUserTrait(): void
    {
        if (! file_exists(app_path('Models/Concerns/UiKitUser.php'))) {
            $this->skipCheck('UiKitUser trait applied', 'no generated trait — nothing to apply');

            return;
        }

        $userModel = app_path('Models/User.php');

        if (! file_exists($userModel)) {
            $this->skipCheck('UiKitUser trait applied', 'app/Models/User.php not found — apply the trait to your user model');

            return;
        }

        if (str_contains((string) file_get_contents($userModel), 'UiKitUser')) {
            $this->passCheck('UiKitUser trait applied');
        } else {
            $this->failCheck('UiKitUser trait applied', 'add `use App\Models\Concerns\UiKitUser;` inside app/Models/User.php');
        }
    }

    protected function checkModuleRequirements(): void
    {
        $installed = config('ui-kit.installed_modules', []);

        if (in_array('impersonation', $installed, true)) {
            if (Route::has('impersonate') && Route::has('impersonate.leave')) {
                $this->passCheck('impersonation routes registered');
            } else {
                $this->failCheck('impersonation routes registered', 'add `Route::impersonate();` between the ui-kit:user-routes markers in routes/ui-kit-user.php, or re-run php artisan ui-kit:install-module impersonation');
            }

            $detail = resource_path('views/livewire/admin/users/user-detail.blade.php');

            if (! file_exists(resource_path('views/partials/impersonation-button.blade.php'))) {
                $this->failCheck('impersonation button wired', 'resources/views/partials/impersonation-button.blade.php is missing: re-run php artisan ui-kit:install-module impersonation');
            } elseif (file_exists($detail) && str_contains((string) file_get_contents($detail), 'partials.impersonation-button')) {
                $this->passCheck('impersonation button wired');
            } else {
                $this->failCheck('impersonation button wired', "the user detail page never shows it: add @includeIf('partials.impersonation-button', ['user' => \$user]) to resources/views/livewire/admin/users/user-detail.blade.php, or republish with php artisan ui-kit:install --force");
            }
        }

        if (! in_array('profile', $installed, true)) {
            return;
        }

        if (is_link(public_path('storage')) || is_dir(public_path('storage'))) {
            $this->passCheck('storage linked (profile avatars)');
        } else {
            $this->failCheck('storage linked (profile avatars)', 'run php artisan storage:link');
        }

        $this->checkTwoFactorColumnsHidden();

        try {
            if (Schema::hasColumn('users', 'two_factor_secret')) {
                $this->passCheck('two-factor columns migrated');
            } else {
                $this->failCheck('two-factor columns migrated', 'run php artisan vendor:publish --tag=fortify-migrations && php artisan migrate');
            }
        } catch (\Throwable $e) {
            $this->skipCheck('two-factor columns migrated', 'database not reachable: '.$e->getMessage());
        }
    }

    /**
     * What the registry says each installed module put in place: its routes,
     * under the names the kit's links use, and the mailables it sends with
     * their markdown views. A route line that never made it into its marker
     * block (a routes file from an older version, a line deleted by hand)
     * leaves a nav entry or a header link pointing at nothing, and a missing
     * mail view only fails inside a queue worker, where nobody is looking.
     */
    protected function checkModuleWiring(): void
    {
        $registry = app(ModuleRegistry::class);
        $adminPrefix = (string) config('admin.route_name_prefix', 'admin.');

        foreach ((array) config('ui-kit.installed_modules', []) as $slug) {
            if (! is_string($slug) || ! $registry->has($slug)) {
                continue;
            }

            $meta = $registry->get($slug);
            $names = [];

            foreach (['admin_routes' => $adminPrefix, 'user_routes' => '', 'public_routes' => ''] as $key => $prefix) {
                foreach ((array) ($meta[$key] ?? []) as $line) {
                    if (preg_match("/->name\\(\\s*'([^']+)'\\s*\\)/", (string) $line, $m)) {
                        $names[] = $prefix.$m[1];
                    }
                }
            }

            if ($names !== []) {
                $missing = array_values(array_filter($names, fn (string $name) => ! Route::has($name)));

                if ($missing === []) {
                    $this->passCheck("{$slug} routes registered");
                } else {
                    $this->failCheck("{$slug} routes registered", 'missing: '.implode(', ', $missing).". Re-run php artisan ui-kit:install-module {$slug}: it adds the module's lines between the ui-kit markers in routes/admin.php and routes/ui-kit-user.php (a renamed path is fine; keep the route name)");
                }
            }

            $mail = (array) ($meta['mail'] ?? []);

            if ($mail !== []) {
                $missing = [];

                foreach ($mail as $class => $view) {
                    if (! class_exists($class)) {
                        $missing[] = $class;
                    }

                    if (! view()->exists($view)) {
                        $missing[] = 'resources/views/'.str_replace('.', '/', $view).'.blade.php';
                    }
                }

                if ($missing === []) {
                    $this->passCheck("{$slug} mail views published");
                } else {
                    $this->failCheck("{$slug} mail views published", 'missing: '.implode(', ', $missing).". Re-run php artisan ui-kit:install-module {$slug} (it copies the module's app/Mail and resources/views/mail files, overwriting its other published files too, so commit first)");
                }
            }
        }

        if (in_array('support-tickets', (array) config('ui-kit.installed_modules', []), true)) {
            if (class_exists('App\\Policies\\SupportTicketPolicy')) {
                $this->passCheck('support ticket policy published');
            } else {
                $this->failCheck('support ticket policy published', 'app/Policies/SupportTicketPolicy.php is missing, so every ticket page answers 403: re-run php artisan ui-kit:install-module support-tickets');
            }
        }
    }

    /**
     * Asks the model rather than reading the file, so a $hidden property, a
     * #[Hidden] attribute and a $visible allow-list all count.
     */
    protected function checkTwoFactorColumnsHidden(): void
    {
        $check = 'two-factor columns hidden on the User model';
        $model = (string) config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($model)) {
            $this->skipCheck($check, "user model {$model} not found");

            return;
        }

        /** @var Model $user */
        $user = new $model;
        $exposed = array_values(array_filter(
            ['two_factor_secret', 'two_factor_recovery_codes'],
            fn (string $column) => ! in_array($column, $user->getHidden(), true)
                && ($user->getVisible() === [] || in_array($column, $user->getVisible(), true))
        ));

        if ($exposed === []) {
            $this->passCheck($check);
        } else {
            $this->failCheck($check, 'add '.implode(' and ', array_map(fn ($c) => "'{$c}'", $exposed)).' to $hidden in app/Models/User.php: toArray() and JSON of a user carry them');
        }
    }

    protected function checkDuplicateRouteNames(): void
    {
        $names = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if ($name !== null) {
                $names[$name] = ($names[$name] ?? 0) + 1;
            }
        }

        $duplicates = array_keys(array_filter($names, fn (int $count) => $count > 1));

        if ($duplicates === []) {
            $this->passCheck('no duplicate route names');
        } else {
            $this->failCheck('no duplicate route names', 'route:cache will fail — duplicated: '.implode(', ', array_slice($duplicates, 0, 5)));
        }
    }

    /**
     * Behind a load balancer or CDN that the app does not trust, every request
     * arrives from the proxy's address. The kit's limiters key on the IP, so
     * one visitor's ten registrations an hour lock out everyone, and five bad
     * logins lock that email out for the whole internet.
     */
    protected function checkTrustedProxies(): void
    {
        $check = 'trusted proxies configured';
        $app = base_path('bootstrap/app.php');
        $hint = "if a proxy or CDN sits in front of the app, call \$middleware->trustProxies(at: [...]) in bootstrap/app.php; otherwise every visitor shares the proxy's IP and the login and register limiters lock everyone out together";

        // A commented-out call (the docs' example, say) does not count.
        if (file_exists($app) && preg_match('/^(?![ \t]*(?:\/\/|\*|#)).*trustProxies\(/m', (string) file_get_contents($app))) {
            $this->passCheck($check);
        } elseif (! $this->laravel->environment('production')) {
            $this->skipCheck($check, 'not production; '.$hint);
        } else {
            $this->failCheck($check, $hint.' (no proxy at all? trustProxies(at: []) says so and passes this check)');
        }
    }

    protected function checkMail(): void
    {
        $from = (string) config('mail.from.address');

        if ($from !== '' && $from !== 'hello@example.com') {
            $this->passCheck('mail from address configured');
        } else {
            $this->skipCheck('mail from address configured', 'set MAIL_FROM_ADDRESS in .env — password reset, verification and the support and contact mail need it');
        }
    }
}

<?php

namespace Shipbytes\UiKit\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DoctorCommand extends Command
{
    protected $signature = 'ui-kit:doctor';

    protected $description = 'Check that the UI Kit install is fully wired and spot common misconfigurations.';

    /** @var array<int, array{0: string, 1: string, 2: string}> */
    protected array $results = [];

    protected bool $failed = false;

    public function handle(): int
    {
        $this->checkPublishedFiles();
        $this->checkLivewire();
        $this->checkFortify();
        $this->checkViteWiring();
        $this->checkDesignSystem();
        $this->checkUserTrait();
        $this->checkModuleRequirements();
        $this->checkDuplicateRouteNames();
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

        foreach (['routes/auth.php', 'routes/admin.php'] as $file) {
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
     * keep icon names from the old partial. Neither throws: the first renders
     * unstyled components, the second renders a nav row without an icon.
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
        }

        if (file_exists(resource_path('css/ui-kit-theme.css'))) {
            $this->failCheck('no leftover ui-kit-theme.css', 'delete resources/css/ui-kit-theme.css: tokens now come from the design system, and any overrides belong in app.css after the ui-kit.css import (:root { --ds-accent: … })');
        } else {
            $this->passCheck('no leftover ui-kit-theme.css');
        }

        $this->checkFontOverride();
        $this->checkNavIcons();
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
        }

        if (! in_array('profile', $installed, true)) {
            return;
        }

        if (is_link(public_path('storage')) || is_dir(public_path('storage'))) {
            $this->passCheck('storage linked (profile avatars)');
        } else {
            $this->failCheck('storage linked (profile avatars)', 'run php artisan storage:link');
        }

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

    protected function checkMail(): void
    {
        $from = (string) config('mail.from.address');

        if ($from !== '' && $from !== 'hello@example.com') {
            $this->passCheck('mail from address configured');
        } else {
            $this->skipCheck('mail from address configured', 'set MAIL_FROM_ADDRESS in .env — password reset / verification mails need it');
        }
    }
}

<?php

namespace Shipbytes\UiKit\Console;

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
        $this->checkFortify();
        $this->checkViteWiring();
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

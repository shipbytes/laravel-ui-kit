<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use Shipbytes\UiKit\Console\InstallCommand;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Shipbytes\UiKit\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class PatchingIdempotencyTest extends TestCase
{
    private string $configDir;

    private string $routesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configDir = config_path();
        $this->routesDir = base_path('routes');

        (new Filesystem)->ensureDirectoryExists($this->configDir);
        (new Filesystem)->ensureDirectoryExists($this->routesDir);

        // Seed the published files with markers, mimicking what
        // `php artisan vendor:publish --tag=ui-kit-config|ui-kit-routes` does.
        $stubs = realpath(__DIR__.'/../../stubs/core');
        copy($stubs.'/config/admin.php', $this->configDir.'/admin.php');
        copy($stubs.'/routes/admin.php', $this->routesDir.'/admin.php');
        copy($stubs.'/routes/ui-kit-user.php', $this->routesDir.'/ui-kit-user.php');

        // Also publish ui-kit.php so markInstalled() can record state.
        copy($stubs.'/config/ui-kit.php', $this->configDir.'/ui-kit.php');
    }

    protected function tearDown(): void
    {
        @unlink($this->configDir.'/admin.php');
        @unlink($this->configDir.'/ui-kit.php');
        @unlink($this->routesDir.'/admin.php');
        @unlink($this->routesDir.'/ui-kit-user.php');

        parent::tearDown();
    }

    public function test_admin_nav_patch_is_idempotent(): void
    {
        $command = $this->makeCommand();

        $entry = ['label' => 'Tickets', 'route' => 'admin.support.index', 'icon' => 'ticket'];

        $this->invoke($command, 'patchAdminNav', [[$entry]]);
        $first = file_get_contents($this->configDir.'/admin.php');
        $countFirst = substr_count($first, "'admin.support.index'");

        $this->invoke($command, 'patchAdminNav', [[$entry]]);
        $second = file_get_contents($this->configDir.'/admin.php');
        $countSecond = substr_count($second, "'admin.support.index'");

        $this->assertSame(1, $countFirst, 'first patch should add one entry');
        $this->assertSame(1, $countSecond, 'second patch must not duplicate');
    }

    public function test_admin_nav_patch_preserves_entries_from_other_modules(): void
    {
        $command = $this->makeCommand();

        // Install support-tickets' nav entry first, then contacts'. The block
        // between the markers is replaced wholesale, so the second patch must
        // carry the first module's entry forward instead of wiping it.
        $this->invoke($command, 'patchAdminNav', [[
            ['label' => 'Support', 'route' => 'admin.support.index', 'icon' => 'ticket'],
        ]]);
        $this->invoke($command, 'patchAdminNav', [[
            ['label' => 'Contacts', 'route' => 'admin.contacts.index', 'icon' => 'mail'],
        ]]);

        $contents = file_get_contents($this->configDir.'/admin.php');

        $this->assertStringContainsString('admin.support.index', $contents, 'earlier module nav entry was wiped by a later install');
        $this->assertStringContainsString('admin.contacts.index', $contents);

        $parsed = require $this->configDir.'/admin.php';
        $routes = array_column(array_filter($parsed['nav'], fn ($i) => isset($i['route'])), 'route');
        $this->assertContains('admin.support.index', $routes);
        $this->assertContains('admin.contacts.index', $routes);
    }

    public function test_admin_routes_patch_is_idempotent(): void
    {
        $command = $this->makeCommand();

        $line = "Route::get('support', \\App\\Livewire\\Admin\\Support\\TicketList::class)->name('support.index');";

        $this->invoke($command, 'patchAdminRoutes', [[$line]]);
        $first = file_get_contents($this->routesDir.'/admin.php');
        $countFirst = substr_count($first, "name('support.index')");

        $this->invoke($command, 'patchAdminRoutes', [[$line]]);
        $second = file_get_contents($this->routesDir.'/admin.php');
        $countSecond = substr_count($second, "name('support.index')");

        $this->assertSame(1, $countFirst);
        $this->assertSame(1, $countSecond, 'second route patch must not duplicate');
    }

    public function test_admin_middleware_swap_is_idempotent(): void
    {
        $command = $this->makeCommand();

        $this->invoke($command, 'patchAdminMiddleware');
        $first = file_get_contents($this->configDir.'/admin.php');

        $this->invoke($command, 'patchAdminMiddleware');
        $second = file_get_contents($this->configDir.'/admin.php');

        $this->assertStringContainsString('App\\Http\\Middleware\\EnsureUserIsAdmin', $first);
        $this->assertStringNotContainsString('EnsureIsAdminFallback', $first);
        $this->assertSame($first, $second, 'second swap must be a no-op');
    }

    public function test_user_routes_patch_is_idempotent(): void
    {
        $command = $this->makeCommand();

        $line = "Route::get('profile', \\App\\Livewire\\Profile\\ProfilePage::class)->name('profile');";

        $this->invoke($command, 'patchUserRoutes', [[$line]]);
        $this->invoke($command, 'patchUserRoutes', [[$line]]);

        $contents = file_get_contents($this->routesDir.'/ui-kit-user.php');
        $this->assertSame(1, substr_count($contents, "name('profile')"));
    }

    public function test_user_routes_patch_is_idempotent_for_lines_without_a_route_name(): void
    {
        $command = $this->makeCommand();

        // The impersonation module injects a macro call, which has no ->name();
        // the patcher falls back to matching the whole line.
        $line = 'Route::impersonate();';

        $this->invoke($command, 'patchUserRoutes', [[$line]]);
        $this->invoke($command, 'patchUserRoutes', [[$line]]);

        $contents = file_get_contents($this->routesDir.'/ui-kit-user.php');
        $this->assertSame(1, substr_count($contents, $line));
        $this->assertMatchesRegularExpression('/ui-kit:user-routes-start \*\/\n    Route::impersonate\(\);\n    \/\* ui-kit:user-routes-end/', $contents);
    }

    public function test_fortify_features_patch_is_idempotent(): void
    {
        // Fortify's own published stub, whose passkeys() call spans three lines.
        copy(__DIR__.'/../../vendor/laravel/fortify/stubs/fortify.php', $this->configDir.'/fortify.php');
        $command = $this->makeCommand(new InstallCommand);

        $this->invoke($command, 'configureFortify');
        $first = file_get_contents($this->configDir.'/fortify.php');

        $this->invoke($command, 'configureFortify');
        $second = file_get_contents($this->configDir.'/fortify.php');

        $features = (require $this->configDir.'/fortify.php')['features'];

        $this->assertSame(['two-factor-authentication'], array_values($features), 'only two-factor (and email verification, when the app turns it on) may stay');
        $this->assertStringContainsString("        // Features::passkeys([\n            // 'confirmPassword' => true,\n        // ]),", $first);
        $this->assertSame($first, $second, 'a second run must change nothing');

        @unlink($this->configDir.'/fortify.php');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function userModels(): array
    {
        return [
            'Laravel 12 $hidden property' => [
                "class User extends Authenticatable\n{\n    use \\App\\Models\\Concerns\\UiKitUser;\n\n    protected \$hidden = [\n        'password',\n        'remember_token',\n    ];\n}\n",
                "    protected \$hidden = [\n        'password',\n        'remember_token',\n        'two_factor_secret',\n        'two_factor_recovery_codes',\n    ];",
            ],
            'Laravel 13 #[Hidden] attribute' => [
                "#[Hidden(['password', 'remember_token'])]\nclass User extends Authenticatable\n{\n    use \\App\\Models\\Concerns\\UiKitUser;\n}\n",
                "#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]",
            ],
            'one column already listed' => [
                "class User extends Authenticatable\n{\n    protected \$hidden = ['password', 'two_factor_secret'];\n}\n",
                "protected \$hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];",
            ],
            'no list at all' => [
                "class User extends Authenticatable\n{\n    use \\App\\Models\\Concerns\\UiKitUser;\n    use Notifiable;\n\n    protected \$fillable = ['name'];\n}\n",
                "    use Notifiable;\n\n    protected \$hidden = ['two_factor_secret', 'two_factor_recovery_codes'];\n\n    protected \$fillable = ['name'];",
            ],
        ];
    }

    #[DataProvider('userModels')]
    public function test_hidden_two_factor_columns_patch_is_idempotent(string $class, string $expected): void
    {
        config(['ui-kit.installed_modules' => ['profile']]);
        (new Filesystem)->ensureDirectoryExists(app_path('Models'));
        $path = app_path('Models/User.php');
        file_put_contents($path, "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Attributes\\Hidden;\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;\nuse Illuminate\\Notifications\\Notifiable;\n\n".$class);

        $command = $this->makeCommand();

        $this->assertTrue($this->invoke($command, 'hideTwoFactorColumns'));
        $first = file_get_contents($path);
        $this->assertTrue($this->invoke($command, 'hideTwoFactorColumns'));

        $this->assertStringContainsString($expected, $first);
        $this->assertSame(1, substr_count($first, "'two_factor_secret'"));
        $this->assertSame($first, file_get_contents($path), 'a second run must change nothing');

        @unlink($path);
    }

    public function test_hidden_patch_leaves_a_model_without_the_profile_module_alone(): void
    {
        config(['ui-kit.installed_modules' => ['support-tickets']]);
        (new Filesystem)->ensureDirectoryExists(app_path('Models'));
        file_put_contents(app_path('Models/User.php'), $model = "<?php\n\nclass User\n{\n    protected \$hidden = ['password'];\n}\n");

        $this->assertTrue($this->invoke($this->makeCommand(), 'hideTwoFactorColumns'));
        $this->assertSame($model, file_get_contents(app_path('Models/User.php')));

        @unlink(app_path('Models/User.php'));
    }

    private function makeCommand(?Command $command = null): Command
    {
        $command ??= new InstallModuleCommand;
        $command->setLaravel($this->app);

        // Wire a no-op output so $this->line() / $this->warn() don't blow up.
        $output = new NullOutput;
        $command->setOutput(new OutputStyle(
            new ArrayInput([]),
            $output
        ));

        return $command;
    }

    private function invoke(object $object, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($object, $args);
    }
}

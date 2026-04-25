<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Shipbytes\UiKit\Tests\TestCase;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Illuminate\Filesystem\Filesystem;

class PatchingIdempotencyTest extends TestCase
{
    private string $configDir;
    private string $routesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configDir = config_path();
        $this->routesDir = base_path('routes');

        (new Filesystem())->ensureDirectoryExists($this->configDir);
        (new Filesystem())->ensureDirectoryExists($this->routesDir);

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

    private function makeCommand(): InstallModuleCommand
    {
        $command = new InstallModuleCommand();
        $command->setLaravel($this->app);

        // Wire a no-op output so $this->line() / $this->warn() don't blow up.
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
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

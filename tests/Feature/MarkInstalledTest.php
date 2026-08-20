<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Shipbytes\UiKit\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MarkInstalledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem)->ensureDirectoryExists(config_path());
        copy(realpath(__DIR__.'/../../stubs/core/config/ui-kit.php'), config_path('ui-kit.php'));
    }

    protected function tearDown(): void
    {
        @unlink(config_path('ui-kit.php'));
        (new Filesystem)->deleteDirectory(app_path('Models/Concerns'));

        parent::tearDown();
    }

    public function test_mark_installed_patches_markers_and_preserves_env_calls(): void
    {
        $command = $this->makeCommand();

        $this->invoke($command, 'markInstalled', ['support-tickets']);
        $this->invoke($command, 'markInstalled', ['profile']);
        $this->invoke($command, 'markInstalled', ['support-tickets']); // duplicate call

        $contents = file_get_contents(config_path('ui-kit.php'));

        $this->assertSame(1, substr_count($contents, "'support-tickets',"));
        $this->assertSame(1, substr_count($contents, "'profile',"));

        // The rest of the config must be untouched: env() defaults intact,
        // comments intact.
        $this->assertStringContainsString("env('UI_KIT_BRAND_NAME'", $contents);
        $this->assertStringContainsString('| Brand', $contents);

        // The file must still be valid PHP returning the recorded slugs.
        $parsed = require config_path('ui-kit.php');
        $this->assertSame(['support-tickets', 'profile'], $parsed['installed_modules']);
    }

    public function test_mark_installed_updates_runtime_config_immediately(): void
    {
        $command = $this->makeCommand();

        $this->assertSame([], config('ui-kit.installed_modules'));

        $this->invoke($command, 'markInstalled', ['impersonation']);

        $this->assertSame(['impersonation'], config('ui-kit.installed_modules'));
    }

    public function test_trait_generates_in_the_same_process_as_the_install(): void
    {
        $command = $this->makeCommand();

        // Without the runtime-config sync this returned early and no trait
        // file was ever written on the run that installed the module.
        $this->invoke($command, 'markInstalled', ['impersonation']);
        $this->invoke($command, 'generateUiKitUserTrait');

        $path = app_path('Models/Concerns/UiKitUser.php');
        $this->assertFileExists($path);

        $trait = file_get_contents($path);
        $this->assertStringContainsString('use Lab404\\Impersonate\\Models\\Impersonate;', $trait);
        $this->assertStringContainsString('function canImpersonate', $trait);
        $this->assertStringNotContainsString('HasRoles', $trait);
    }

    private function makeCommand(): InstallModuleCommand
    {
        $command = new InstallModuleCommand;
        $command->setLaravel($this->app);

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

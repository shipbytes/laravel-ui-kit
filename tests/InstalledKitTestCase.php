<?php

namespace Shipbytes\UiKit\Tests;

use App\Models\User;
use Laravel\Fortify\FortifyServiceProvider;
use Propaganistas\LaravelDisposableEmail\DisposableEmailServiceProvider;
use Shipbytes\UiKit\Tests\Concerns\AssertsRendersCleanly;
use Shipbytes\UiKit\Tests\Concerns\InstallsKitIntoSkeleton;

/**
 * A skeleton with the kit installed for real, for the behaviour tests: each
 * class drives one page the way a person would, against the published stubs.
 */
abstract class InstalledKitTestCase extends TestCase
{
    use AssertsRendersCleanly;
    use InstallsKitIntoSkeleton;

    /** Comma-separated modules to install alongside the core. */
    protected string $modules = 'profile';

    /** See installKit(): true needs #[RunTestsInSeparateProcesses]. */
    protected bool $verifiesEmail = false;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            FortifyServiceProvider::class,
            DisposableEmailServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->installKit($this->modules, $this->verifiesEmail);
    }

    protected function tearDown(): void
    {
        $this->cleanSkeleton();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUser(array $attributes = []): User
    {
        static $n = 0;
        $n++;

        return User::forceCreate($attributes + [
            'name' => "Person {$n}",
            'email' => "person{$n}@example.com",
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser($attributes + ['is_admin' => true]);
    }
}

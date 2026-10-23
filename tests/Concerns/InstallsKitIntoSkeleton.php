<?php

namespace Shipbytes\UiKit\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\Livewire;
use Shipbytes\UiKit\UiKitServiceProvider;

/**
 * Runs the real installer against the Testbench skeleton and wires up what a
 * fresh app would have booted with: the published classes, views, admin config
 * and route files. The page tests then render over HTTP, which is the closest
 * thing to clicking through a fresh app that a package test can do.
 */
trait InstallsKitIntoSkeleton
{
    private static bool $autoloaderRegistered = false;

    /**
     * @param  bool  $verifiesEmail  make the User model MustVerifyEmail. The
     *                               class loads once per process, so a test class that passes true has
     *                               to run its tests in separate processes.
     */
    protected function installKit(string $modules, bool $verifiesEmail = false): void
    {
        $this->withoutVite();

        // The skeleton's App\ namespace isn't autoloaded by Testbench —
        // register a loader so published Livewire classes and models resolve
        // when pages render.
        if (! self::$autoloaderRegistered) {
            spl_autoload_register(function (string $class): void {
                if (str_starts_with($class, 'App\\')) {
                    $path = app_path(str_replace('\\', '/', substr($class, 4)).'.php');

                    if (file_exists($path)) {
                        require $path;
                    }
                }
            });

            self::$autoloaderRegistered = true;
        }

        // A minimal host-app User model, as every real app has.
        (new Filesystem)->ensureDirectoryExists(app_path('Models'));
        $implements = $verifiesEmail ? ' implements \\Illuminate\\Contracts\\Auth\\MustVerifyEmail' : '';

        file_put_contents(app_path('Models/User.php'), <<<PHP
<?php

namespace App\\Models;

use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;

class User extends Authenticatable{$implements}
{
    use Notifiable;

    protected \$table = 'users';

    // The skeleton's own list. NOT \$guarded = []: that hid a profile stub
    // that mass-assigned avatar_path, which a real app silently drops.
    protected \$fillable = ['name', 'email', 'password'];

    protected \$hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
PHP);

        (new Filesystem)->ensureDirectoryExists(resource_path('css'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js'));
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n");
        file_put_contents(resource_path('js/app.js'), "import './bootstrap';\n");

        $this->loadLaravelMigrations();

        $this->artisan('ui-kit:install', ['--modules' => $modules, '--no-interaction' => true])->assertSuccessful();

        // The provider booted before anything was published — load the
        // published admin config and wire the routes/views up by hand.
        config(['admin' => require config_path('admin.php')]);

        Livewire::addLocation(viewPath: resource_path('views/livewire'));

        $this->rebootFortifyRoutes();

        $provider = new UiKitServiceProvider($this->app);
        $ref = new \ReflectionMethod($provider, 'registerRoutes');
        $ref->setAccessible(true);
        $ref->invoke($provider);

        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Fortify registered its routes at boot from its own default config, with
     * every feature on. A real app boots with the config the installer just
     * patched, so drop Fortify's routes and register them again from that.
     * Everything else (Livewire's update endpoint included) stays.
     */
    private function rebootFortifyRoutes(): void
    {
        if (! $this->app->providerIsLoaded(FortifyServiceProvider::class) || ! file_exists(config_path('fortify.php'))) {
            return;
        }

        config(['fortify' => require config_path('fortify.php')]);

        $kept = new RouteCollection;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getActionName(), 'Laravel\\Fortify\\')) {
                $kept->add($route);
            }
        }

        Route::setRoutes($kept);

        $ref = new \ReflectionMethod(FortifyServiceProvider::class, 'configureRoutes');
        $ref->invoke(new FortifyServiceProvider($this->app));
    }
}

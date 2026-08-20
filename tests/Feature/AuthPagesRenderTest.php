<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\Volt\Volt;
use Shipbytes\UiKit\Tests\TestCase;
use Shipbytes\UiKit\UiKitServiceProvider;

/**
 * Boots a real core install in the Testbench skeleton and renders the
 * published pages over HTTP — the closest thing to clicking through a
 * fresh app that a package test can do.
 */
class AuthPagesRenderTest extends TestCase
{
    private static bool $autoloaderRegistered = false;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            FortifyServiceProvider::class,
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

        $this->withoutVite();

        // The skeleton's App\ namespace isn't autoloaded by Testbench —
        // register a loader so published Livewire classes + the User model
        // resolve when pages render.
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
        file_put_contents(app_path('Models/User.php'), <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];
}
PHP);

        (new Filesystem)->ensureDirectoryExists(resource_path('css'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js'));
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n");
        file_put_contents(resource_path('js/app.js'), "import './bootstrap';\n");

        $this->loadLaravelMigrations();

        $this->artisan('ui-kit:install', ['--no-interaction' => true])->assertSuccessful();

        // The provider booted before anything was published — load the
        // published admin config and wire the routes/views up by hand.
        config(['admin' => require config_path('admin.php')]);

        Volt::mount([resource_path('views/livewire')]);

        $provider = new UiKitServiceProvider($this->app);
        $ref = new \ReflectionMethod($provider, 'registerRoutes');
        $ref->setAccessible(true);
        $ref->invoke($provider);

        Route::getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        $this->cleanSkeleton();

        parent::tearDown();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Sign in');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account');
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_two_factor_challenge_redirects_without_staged_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_admin_redirects_guests_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_is_forbidden_for_regular_users(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_dashboard_renders_for_admins(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Total users');
    }

    public function test_admin_users_list_renders_for_admins(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('admin@example.com');
    }
}

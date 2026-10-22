<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\Livewire;
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

        // The profile module rides along: it has no composer dependency, and
        // its page is the other half of the screens built on ds:: forms.
        $this->artisan('ui-kit:install', ['--modules' => 'profile', '--no-interaction' => true])->assertSuccessful();

        // The provider booted before anything was published — load the
        // published admin config and wire the routes/views up by hand.
        config(['admin' => require config_path('admin.php')]);

        Livewire::addLocation(viewPath: resource_path('views/livewire'));

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
            ->assertSee('Sign in')
            // Blade prints a component tag it failed to compile as literal
            // text, so this is the signature of a broken ds:: call.
            ->assertDontSee('<x-ds::', false);
    }

    public function test_auth_pages_are_livewire_page_routes(): void
    {
        $this->assertSame(
            'pages.auth.login',
            Route::getRoutes()->getByName('login')?->getAction('livewire_component'),
            'routes/auth.php must register its pages through Route::livewire()'
        );
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Create account')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Send reset link')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_reset_password_page_renders(): void
    {
        $this->get('/reset-password/some-token?email=someone%40example.com')
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('someone@example.com')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_verify_email_page_renders_for_an_unverified_user(): void
    {
        $user = User::forceCreate([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Resend verification email')
            ->assertSee('Log out')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_confirm_password_page_renders(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/confirm-password')
            ->assertOk()
            ->assertSee('Confirm')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_two_factor_challenge_renders_with_a_staged_login(): void
    {
        $this->withSession(['ui-kit.login.id' => 1])
            ->get('/two-factor-challenge')
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('Use a recovery code instead')
            ->assertDontSee('<x-ds::', false);
    }

    public function test_profile_page_renders_its_four_sections(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile Information')
            ->assertSee('Update Password')
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Delete Account')
            // The delete confirmation is the design system's dialog, closed
            // until Alpine opens it; the kit's own x-modal is gone.
            ->assertSee('role="dialog"', false)
            ->assertSee('form="delete-account-form"', false)
            ->assertDontSee('<x-modal', false)
            ->assertDontSee('<x-ds::', false);
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
            ->assertSee('Total users')
            // The shell: rail, phone bar and "More" sheet, all through ds::.
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Primary"', false)
            ->assertSee('aria-label="More"', false)
            ->assertDontSee('<x-ds::', false);
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

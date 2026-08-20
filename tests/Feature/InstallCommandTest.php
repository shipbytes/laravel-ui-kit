<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\FortifyServiceProvider;
use Shipbytes\UiKit\Tests\Fixtures\MakeAdminUser;
use Shipbytes\UiKit\Tests\TestCase;

/**
 * End-to-end run of `ui-kit:install` against the Testbench skeleton, using
 * the modules that have no composer dependencies (so nothing shells out).
 */
class InstallCommandTest extends TestCase
{
    private const MODULES = 'support-tickets,contacts,profile';

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            FortifyServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Mimic a fresh Laravel 12 app's Vite entrypoints (Tailwind v4).
        (new Filesystem)->ensureDirectoryExists(resource_path('css'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js'));
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n\n@source '../views';\n");
        file_put_contents(resource_path('js/app.js'), "import './bootstrap';\n");

        // …and its User model (the skeleton ships none), so the installer's
        // automatic trait application has a real file to patch.
        (new Filesystem)->ensureDirectoryExists(app_path('Models'));
        file_put_contents(app_path('Models/User.php'), <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];
}
PHP);
    }

    protected function tearDown(): void
    {
        $this->cleanSkeleton();

        parent::tearDown();
    }

    public function test_full_install_publishes_patches_and_records_state(): void
    {
        // The skeleton's database/migrations dir has no users migration —
        // create the framework tables first, as a real app would have.
        $this->loadLaravelMigrations();

        $this->artisan('ui-kit:install', ['--modules' => self::MODULES, '--no-interaction' => true])
            ->assertSuccessful();

        // Published core files land in the skeleton.
        $this->assertFileExists(config_path('ui-kit.php'));
        $this->assertFileExists(config_path('admin.php'));
        $this->assertFileExists(base_path('routes/auth.php'));
        $this->assertFileExists(app_path('Livewire/Admin/Users/UserList.php'));
        $this->assertFileExists(app_path('Livewire/Admin/Support/TicketList.php'));
        $this->assertFileExists(resource_path('views/livewire/pages/auth/login.blade.php'));

        // Fortify config is patched: view routes off, home points at the root.
        $fortify = file_get_contents(config_path('fortify.php'));
        $this->assertStringContainsString("'views' => false", $fortify);
        $this->assertStringContainsString("'home' => '/'", $fortify);

        // installed_modules recorded between markers WITHOUT nuking env() calls.
        $uiKit = file_get_contents(config_path('ui-kit.php'));
        foreach (explode(',', self::MODULES) as $slug) {
            $this->assertStringContainsString("'{$slug}',", $uiKit);
        }
        $this->assertStringContainsString("env('UI_KIT_BRAND_NAME'", $uiKit, 'markInstalled must not evaluate env() defaults');

        // Nav entries from BOTH nav-declaring modules survive sequential installs.
        $admin = file_get_contents(config_path('admin.php'));
        $this->assertStringContainsString('admin.support.index', $admin);
        $this->assertStringContainsString('admin.contacts.index', $admin);

        // Route lines injected for both modules + the profile user route.
        $adminRoutes = file_get_contents(base_path('routes/admin.php'));
        $this->assertStringContainsString("name('support.index')", $adminRoutes);
        $this->assertStringContainsString("name('contacts.index')", $adminRoutes);
        $this->assertStringContainsString(
            "name('profile')",
            file_get_contents(base_path('routes/ui-kit-user.php'))
        );

        // Vite entrypoints are wired automatically: the CSS import lands right
        // after Tailwind's own import, the JS import is appended.
        $appCss = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression(
            "/@import 'tailwindcss';\n@import '\.\/ui-kit\.css';/",
            $appCss
        );
        $this->assertStringContainsString("import './ui-kit';", file_get_contents(resource_path('js/app.js')));
        $this->assertFileExists(resource_path('css/ui-kit-theme.css'));

        // The single deferred migrate covered core + module + Fortify 2FA migrations.
        $this->assertTrue(Schema::hasColumn('users', 'is_admin'));
        $this->assertTrue(Schema::hasColumn('users', 'avatar_path'));
        $this->assertTrue(Schema::hasColumn('users', 'two_factor_secret'));
        $this->assertTrue(Schema::hasTable('support_tickets'));
        $this->assertTrue(Schema::hasTable('contact_submissions'));

        // profile is in the set → the trait must be generated in this same
        // process, bundling TwoFactorAuthenticatable (and nothing from the
        // modules that weren't selected).
        $this->assertFileExists(app_path('Models/Concerns/UiKitUser.php'));
        $trait = file_get_contents(app_path('Models/Concerns/UiKitUser.php'));
        $this->assertStringContainsString('use TwoFactorAuthenticatable;', $trait);
        $this->assertStringNotContainsString('HasRoles', $trait);
        $this->assertStringNotContainsString('Impersonate', $trait);

        // …and the trait is wired into the User model automatically, with the
        // result still being valid PHP.
        $userModel = file_get_contents(app_path('Models/User.php'));
        $this->assertStringContainsString('use \App\Models\Concerns\UiKitUser;', $userModel);
        exec(PHP_BINARY.' -l '.escapeshellarg(app_path('Models/User.php')).' 2>&1', $lintOut, $lintCode);
        $this->assertSame(0, $lintCode, implode("\n", $lintOut));

        // ---- Re-run: must succeed non-interactively (update mode) and stay idempotent.
        $this->artisan('ui-kit:install', ['--modules' => self::MODULES, '--no-interaction' => true])
            ->assertSuccessful();

        $uiKit = file_get_contents(config_path('ui-kit.php'));
        $this->assertSame(1, substr_count($uiKit, "'support-tickets',"));

        $admin = file_get_contents(config_path('admin.php'));
        $this->assertSame(1, substr_count($admin, "'admin.support.index'"));
        $this->assertSame(1, substr_count($admin, "'admin.contacts.index'"));

        $adminRoutes = file_get_contents(base_path('routes/admin.php'));
        $this->assertSame(1, substr_count($adminRoutes, "name('support.index')"));

        $this->assertSame(1, substr_count(file_get_contents(resource_path('css/app.css')), 'ui-kit.css'));
        $this->assertSame(1, substr_count(file_get_contents(resource_path('js/app.js')), "import './ui-kit';"));

        $this->assertSame(
            1,
            substr_count(file_get_contents(app_path('Models/User.php')), 'UiKitUser'),
            're-running the installer must not duplicate the User-model trait patch'
        );

        $this->assertCount(
            1,
            glob(database_path('migrations/*_add_is_admin_to_users_table.php')) ?: [],
            're-publishing must reuse the existing is_admin migration filename'
        );
        $this->assertCount(
            1,
            glob(database_path('migrations/*_add_two_factor_columns_to_users_table.php')) ?: [],
            "Fortify's unguarded 2FA migration must not be re-published on re-runs"
        );

        // A freshly-completed install should pass its own health check. This
        // (via the storage-link check) is also the regression net for the
        // trait-static deferred-queue bug: module deferrals must reach the
        // parent installer's drain.
        $this->artisan('ui-kit:doctor')->assertSuccessful();
    }

    public function test_unknown_module_aborts_with_failure(): void
    {
        $this->artisan('ui-kit:install', ['--modules' => 'does-not-exist', '--no-interaction' => true])
            ->assertFailed();
    }

    public function test_install_module_accepts_multiple_and_comma_separated_slugs(): void
    {
        $this->loadLaravelMigrations();

        $this->artisan('ui-kit:install', ['--modules' => 'support-tickets', '--no-interaction' => true])
            ->assertSuccessful();

        // One invocation, comma-separated — the shape users reach for first.
        $this->artisan('ui-kit:install-module', ['modules' => ['contacts,profile'], '--no-interaction' => true])
            ->assertSuccessful();

        $uiKit = file_get_contents(config_path('ui-kit.php'));
        $this->assertStringContainsString("'contacts',", $uiKit);
        $this->assertStringContainsString("'profile',", $uiKit);

        // profile in the batch → trait regenerated AND applied, standalone too.
        $this->assertFileExists(app_path('Models/Concerns/UiKitUser.php'));
        $this->assertStringContainsString(
            'use \App\Models\Concerns\UiKitUser;',
            file_get_contents(app_path('Models/User.php'))
        );
    }

    public function test_install_module_rejects_batch_containing_unknown_slug(): void
    {
        $this->artisan('ui-kit:install-module', ['modules' => ['contacts', 'nope'], '--no-interaction' => true])
            ->assertFailed();

        $this->assertFileDoesNotExist(
            config_path('ui-kit.php'),
            'a batch with an unknown slug must install nothing'
        );
    }

    public function test_make_admin_promotes_a_user(): void
    {
        $this->loadLaravelMigrations();

        $this->artisan('ui-kit:install', ['--modules' => 'profile', '--no-interaction' => true])
            ->assertSuccessful();

        config(['auth.providers.users.model' => MakeAdminUser::class]);

        MakeAdminUser::query()->create([
            'name' => 'First', 'email' => 'first@example.com', 'password' => bcrypt('secret123'),
        ]);
        MakeAdminUser::query()->create([
            'name' => 'Second', 'email' => 'second@example.com', 'password' => bcrypt('secret123'),
        ]);

        // By email.
        $this->artisan('ui-kit:make-admin', ['email' => 'second@example.com'])->assertSuccessful();
        $this->assertTrue(
            (bool) MakeAdminUser::query()->where('email', 'second@example.com')->value('is_admin')
        );

        // No argument → first user.
        $this->artisan('ui-kit:make-admin')->assertSuccessful();
        $this->assertTrue(
            (bool) MakeAdminUser::query()->where('email', 'first@example.com')->value('is_admin')
        );

        // Unknown email fails cleanly.
        $this->artisan('ui-kit:make-admin', ['email' => 'ghost@example.com'])->assertFailed();
    }
}

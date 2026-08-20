<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\FortifyServiceProvider;
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
        (new Filesystem())->ensureDirectoryExists(resource_path('css'));
        (new Filesystem())->ensureDirectoryExists(resource_path('js'));
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n\n@source '../views';\n");
        file_put_contents(resource_path('js/app.js'), "import './bootstrap';\n");
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();

        foreach ([
            config_path('ui-kit.php'),
            config_path('admin.php'),
            config_path('fortify.php'),
            base_path('routes/auth.php'),
            base_path('routes/admin.php'),
            base_path('routes/ui-kit-user.php'),
            resource_path('js/ui-kit.js'),
            resource_path('js/app.js'),
            resource_path('css/ui-kit.css'),
            resource_path('css/ui-kit-theme.css'),
            resource_path('css/app.css'),
            public_path('storage'),
        ] as $file) {
            if (is_link($file) || is_file($file)) {
                @unlink($file);
            }
        }

        foreach ([
            app_path('Livewire'),
            app_path('Models/Concerns'),
            resource_path('views/layouts'),
            resource_path('views/components'),
            resource_path('views/livewire'),
        ] as $dir) {
            $fs->deleteDirectory($dir);
        }

        foreach (glob(database_path('migrations/*_add_is_admin_to_users_table.php')) ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob(database_path('migrations/2024_*.php')) ?: [] as $file) {
            @unlink($file);
        }

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

        // The single deferred migrate covered core + module migrations.
        $this->assertTrue(Schema::hasColumn('users', 'is_admin'));
        $this->assertTrue(Schema::hasColumn('users', 'avatar_path'));
        $this->assertTrue(Schema::hasTable('support_tickets'));
        $this->assertTrue(Schema::hasTable('contact_submissions'));

        // No admin/impersonation modules selected → no trait generated.
        $this->assertFileDoesNotExist(app_path('Models/Concerns/UiKitUser.php'));

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

        $this->assertCount(
            1,
            glob(database_path('migrations/*_add_is_admin_to_users_table.php')) ?: [],
            're-publishing must reuse the existing is_admin migration filename'
        );
    }

    public function test_unknown_module_aborts_with_failure(): void
    {
        $this->artisan('ui-kit:install', ['--modules' => 'does-not-exist', '--no-interaction' => true])
            ->assertFailed();
    }
}

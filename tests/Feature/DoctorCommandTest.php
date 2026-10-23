<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Orchestra\Testbench\Attributes\UsesVendor;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\DefaultBadgeResolver;
use Shipbytes\UiKit\Support\NullBadgeResolver;
use Shipbytes\UiKit\Tests\Fixtures\HidesTwoFactorUser;
use Shipbytes\UiKit\Tests\TestCase;

/**
 * The upgrade checks. A 0.2-era app keeps booting through the Volt shim, and a
 * 0.3-era app keeps compiling its old ui-kit.css, so in both cases the doctor
 * is what reports the unfinished upgrade.
 *
 * UsesVendor links the package's vendor/ into the Testbench skeleton, the way
 * a real app has one, because the design-system rows look for the package
 * and its icons at base_path('vendor/…'), where the published CSS imports them.
 */
#[UsesVendor]
class DoctorCommandTest extends TestCase
{
    private string $providersBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providersBackup = (string) file_get_contents(base_path('bootstrap/providers.php'));
    }

    protected function tearDown(): void
    {
        file_put_contents(base_path('bootstrap/providers.php'), $this->providersBackup);
        @unlink(app_path('Providers/VoltServiceProvider.php'));
        $this->cleanSkeleton();

        parent::tearDown();
    }

    public function test_livewire_4_is_reported_installed(): void
    {
        $this->assertMatchesRegularExpression('/Livewire 4 installed\s*\|\s*✓ pass/u', $this->doctor());
    }

    public function test_volt_routes_file_fails_until_migrated(): void
    {
        file_put_contents(base_path('routes/auth.php'), <<<'PHP'
<?php

// ui-kit:managed — auto-loaded by UiKitServiceProvider.

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')->name('login');
});
PHP);

        $output = $this->doctor();

        $this->assertMatchesRegularExpression('/routes\/auth\.php uses Route::livewire\s*\|\s*✗ fail\s*\|.*replace `Volt::route\(` with `Route::livewire\(`/u', $output);

        file_put_contents(base_path('routes/auth.php'), str_replace(
            ["use Livewire\\Volt\\Volt;\n", 'Volt::route('],
            ['', 'Route::livewire('],
            (string) file_get_contents(base_path('routes/auth.php'))
        ));

        $this->assertMatchesRegularExpression('/routes\/auth\.php uses Route::livewire\s*\|\s*✓ pass/u', $this->doctor());
    }

    public function test_volt_view_fails_until_migrated(): void
    {
        $this->assertMatchesRegularExpression('/views free of Volt imports\s*\|\s*– skip/u', $this->doctor());

        $view = resource_path('views/livewire/pages/auth/login.blade.php');
        @mkdir(dirname($view), 0755, true);
        file_put_contents($view, <<<'BLADE'
<?php

use Livewire\Volt\Component;

new class extends Component {};
?>

<div></div>
BLADE);

        $output = $this->doctor();

        $this->assertMatchesRegularExpression('/views free of Volt imports\s*\|\s*✗ fail\s*\|.*resources\/views\/livewire\/pages\/auth\/login\.blade\.php/u', $output);

        file_put_contents($view, str_replace('Livewire\Volt\Component', 'Livewire\Component', (string) file_get_contents($view)));

        $this->assertMatchesRegularExpression('/views free of Volt imports\s*\|\s*✓ pass/u', $this->doctor());
    }

    public function test_leftover_volt_provider_fails(): void
    {
        $this->assertMatchesRegularExpression('/no Volt service provider\s*\|\s*✓ pass/u', $this->doctor());

        file_put_contents(base_path('bootstrap/providers.php'), <<<'PHP'
<?php

return [
    App\Providers\VoltServiceProvider::class,
];
PHP);

        $this->assertMatchesRegularExpression('/no Volt service provider\s*\|\s*✗ fail\s*\|.*composer remove livewire\/volt/u', $this->doctor());

        file_put_contents(base_path('bootstrap/providers.php'), $this->providersBackup);
        file_put_contents(app_path('Providers/VoltServiceProvider.php'), "<?php\n");

        $this->assertMatchesRegularExpression('/no Volt service provider\s*\|\s*✗ fail/u', $this->doctor());
    }

    public function test_design_system_is_reported_installed(): void
    {
        $this->assertMatchesRegularExpression('/design system installed\s*\|\s*✓ pass/u', $this->doctor());
    }

    public function test_old_ui_kit_css_fails_until_republished(): void
    {
        $this->assertMatchesRegularExpression('/ui-kit\.css imports the design system\s*\|\s*– skip/u', $this->doctor());

        @mkdir(resource_path('css'), 0755, true);
        file_put_contents(resource_path('css/ui-kit.css'), "@import './ui-kit-theme.css';\n\n[x-cloak] { display: none !important; }\n");

        $this->assertMatchesRegularExpression('/ui-kit\.css imports the design system\s*\|\s*✗ fail\s*\|.*vendor:publish --tag=ui-kit-assets --force/u', $this->doctor());

        copy(__DIR__.'/../../stubs/core/css/ui-kit.css', resource_path('css/ui-kit.css'));

        $this->assertMatchesRegularExpression('/ui-kit\.css imports the design system\s*\|\s*✓ pass/u', $this->doctor());
    }

    public function test_ui_kit_css_without_the_kit_source_line_fails(): void
    {
        @mkdir(resource_path('css'), 0755, true);

        // A ui-kit.css on the design system that scans only blade-ui's views.
        file_put_contents(resource_path('css/ui-kit.css'), str_replace(
            "@source '../../vendor/shipbytes/laravel-ui-kit/resources/views';\n",
            '',
            (string) file_get_contents(__DIR__.'/../../stubs/core/css/ui-kit.css')
        ));

        $this->assertMatchesRegularExpression("/ui-kit\\.css scans the kit's own views\\s*\\|\\s*✗ fail\\s*\\|.*vendor:publish --tag=ui-kit-assets --force/u", $this->doctor());

        copy(__DIR__.'/../../stubs/core/css/ui-kit.css', resource_path('css/ui-kit.css'));

        $this->assertMatchesRegularExpression("/ui-kit\\.css scans the kit's own views\\s*\\|\\s*✓ pass/u", $this->doctor());
    }

    public function test_admin_list_on_plain_with_pagination_fails(): void
    {
        $check = 'admin lists paginate with the design system';

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip/u", $this->doctor());

        @mkdir(app_path('Livewire/Admin/Users'), 0755, true);
        file_put_contents(app_path('Livewire/Admin/Users/UserList.php'), "<?php\n\nnamespace App\\Livewire\\Admin\\Users;\n\nuse Livewire\\Component;\nuse Livewire\\WithPagination;\n\nclass UserList extends Component\n{\n    use WithPagination;\n}\n");

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|\\s*UserList: replace/u", $this->doctor());

        copy(__DIR__.'/../../stubs/core/Livewire/Admin/Users/UserList.php', app_path('Livewire/Admin/Users/UserList.php'));

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());
    }

    public function test_leftover_theme_file_fails(): void
    {
        $this->assertMatchesRegularExpression('/no leftover ui-kit-theme\.css\s*\|\s*✓ pass/u', $this->doctor());

        @mkdir(resource_path('css'), 0755, true);
        file_put_contents(resource_path('css/ui-kit-theme.css'), "@theme { --color-brand-600: #4f46e5; }\n");

        $this->assertMatchesRegularExpression('/no leftover ui-kit-theme\.css\s*\|\s*✗ fail\s*\|.*delete resources\/css\/ui-kit-theme\.css/u', $this->doctor());
    }

    public function test_skeleton_font_after_the_import_fails(): void
    {
        $check = 'app\.css does not override the design system font';

        @mkdir(resource_path('css'), 0755, true);
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n");

        $this->assertMatchesRegularExpression("/{$check}\s*\|\s*– skip\s*\|.*does not import ui-kit\.css/u", $this->doctor());

        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n@import './ui-kit.css';\n\n@theme {\n    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',\n        'Segoe UI Symbol', 'Noto Color Emoji';\n}\n");

        $this->assertMatchesRegularExpression("/{$check}\s*\|\s*✗ fail\s*\|.*delete the @theme/u", $this->doctor());

        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n@import './ui-kit.css';\n\n@theme {\n    --font-sans: 'Geist', sans-serif;\n}\n");

        $this->assertMatchesRegularExpression("/{$check}\s*\|\s*– skip\s*\|.*you override the font; make sure it is loaded/u", $this->doctor());

        // Before the import it loses to the design system, so it is not an override.
        file_put_contents(resource_path('css/app.css'), "@import 'tailwindcss';\n@theme {\n    --font-sans: 'Instrument Sans', sans-serif;\n}\n@import './ui-kit.css';\n");

        $this->assertMatchesRegularExpression("/{$check}\s*\|\s*✓ pass/u", $this->doctor());
    }

    public function test_nav_icons_must_be_heroicons(): void
    {
        config(['admin.nav' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2'],
            ['section' => 'Content'],
            // A Heroicons v1 name the design system aliases still resolves.
            ['label' => 'Contacts', 'route' => 'admin.contacts.index', 'icon' => 'mail'],
            ['label' => 'Reports', 'route' => 'admin.reports'],
        ]]);

        $this->assertMatchesRegularExpression('/nav icons are Heroicons\s*\|\s*✓ pass/u', $this->doctor());

        config(['admin.nav' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
            ['label' => 'Changelog', 'route' => 'admin.changelog.index', 'icon' => 'note'],
            ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
        ]]);

        $output = $this->doctor();

        $this->assertMatchesRegularExpression('/nav icons are Heroicons\s*\|\s*✗ fail\s*\|.*Dashboard => grid, Changelog => note\./u', $output);
        $this->assertStringContainsString('grid → squares-2x2', $output);
        $this->assertStringContainsString('the sidebar shows a question-mark icon for each of them', $output);
        $this->assertStringNotContainsString('Users => users', $output);
    }

    public function test_fortify_features_beyond_two_factor_fail(): void
    {
        $check = 'fortify features limited to two-factor';
        file_put_contents(config_path('fortify.php'), "<?php\n\nreturn ['views' => false];\n");

        config(['fortify.features' => [Features::emailVerification(), Features::twoFactorAuthentication()]]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());

        config(['fortify.features' => [Features::registration(), Features::updatePasswords(), Features::twoFactorAuthentication()]]);
        $output = $this->doctor();

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|\\s*comment out Features::registration\\(\\), Features::updatePasswords\\(\\) in config\\/fortify\\.php/u", $output);
    }

    public function test_fortifys_two_factor_redirect_needs_its_route(): void
    {
        $check = 'two-factor\\.login route registered';
        file_put_contents(config_path('fortify.php'), "<?php\n\nreturn ['views' => false];\n");

        config(['fortify.features' => []]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip/u", $this->doctor());

        config(['fortify.features' => [Features::twoFactorAuthentication()]]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|\\s*add Route::redirect\\('two-factor-login'/u", $this->doctor());

        Route::redirect('two-factor-login', '/two-factor-challenge')->name('two-factor.login');
        Route::getRoutes()->refreshNameLookups();
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());
    }

    public function test_two_factor_columns_must_be_hidden_when_the_profile_module_is_on(): void
    {
        $check = 'two-factor columns hidden on the User model';
        config(['ui-kit.installed_modules' => ['profile']]);

        // The framework's base user hides nothing.
        config(['auth.providers.users.model' => User::class]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|\\s*add 'two_factor_secret' and 'two_factor_recovery_codes' to \\\$hidden/u", $this->doctor());

        config(['auth.providers.users.model' => HidesTwoFactorUser::class]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());

        config(['ui-kit.installed_modules' => []]);
        $this->assertStringNotContainsString($check, $this->doctor(), 'no profile module, no two-factor columns to hide');
    }

    public function test_a_badge_the_resolver_never_returns_fails(): void
    {
        $check = 'sidebar badges resolve';

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip/u", $this->doctor());

        config(['admin.nav' => [['label' => 'Support', 'route' => 'admin.support.index', 'icon' => 'ticket', 'badge' => 'open_tickets']]]);

        $this->app->instance(SidebarBadgeResolver::class, new class implements SidebarBadgeResolver
        {
            public function counts(): array
            {
                return ['open_tickets' => 0];
            }
        });
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());

        // An app's own resolver that forgot the key: nothing else would ever say so.
        $this->app->instance(SidebarBadgeResolver::class, new NullBadgeResolver);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|.*NullBadgeResolver returns no count for Support => open_tickets/u", $this->doctor());

        // The kit's own resolver, whose count threw (a table never migrated).
        $this->app->instance(SidebarBadgeResolver::class, new class extends DefaultBadgeResolver
        {
            protected function compute(): array
            {
                $this->failures['open_tickets'] = 'no such table: support_tickets';

                return [];
            }
        });
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|\\s*could not count open_tickets: no such table: support_tickets\\. Run php artisan migrate/u", $this->doctor());
    }

    public function test_the_impersonation_button_must_be_wired_when_the_module_is_on(): void
    {
        $check = 'impersonation button wired';
        config(['ui-kit.installed_modules' => ['impersonation']]);

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|.*impersonation-button\\.blade\\.php is missing/u", $this->doctor());

        @mkdir(resource_path('views/partials'), 0755, true);
        copy(__DIR__.'/../../stubs/modules/impersonation/views/partials/impersonation-button.blade.php', resource_path('views/partials/impersonation-button.blade.php'));
        @mkdir(resource_path('views/livewire/admin/users'), 0755, true);
        file_put_contents(resource_path('views/livewire/admin/users/user-detail.blade.php'), "<div>{{ \$user->name }}</div>\n");

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|.*the user detail page never shows it/u", $this->doctor());

        copy(__DIR__.'/../../stubs/core/views/livewire/admin/users/user-detail.blade.php', resource_path('views/livewire/admin/users/user-detail.blade.php'));

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());

        @unlink(resource_path('views/partials/impersonation-button.blade.php'));
        @rmdir(resource_path('views/partials'));
    }

    public function test_trusted_proxies_pass_skip_or_fail(): void
    {
        $check = 'trusted proxies configured';
        $app = base_path('bootstrap/app.php');
        $original = (string) file_get_contents($app);

        try {
            $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip\\s*\\|\\s*not production/u", $this->doctor());

            $this->app['env'] = 'production';
            $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|.*trustProxies\\(at:/u", $this->doctor());

            // Only the text is read; the file is put back before anything loads it.
            file_put_contents($app, $original."\n// \$middleware->trustProxies(at: '*');\n");
            $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail/u", $this->doctor(), 'a commented-out call is not a configuration');

            file_put_contents($app, $original."\n\$middleware->trustProxies(at: '*');\n");
            $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());
        } finally {
            file_put_contents($app, $original);
            $this->app['env'] = 'testing';
        }
    }

    public function test_the_public_routes_block_is_needed_once_a_module_adds_a_public_page(): void
    {
        $check = preg_quote('routes/ui-kit-user.php has the public-routes block', '/');

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip\\s*\\|\\s*routes\\/ui-kit-user\\.php not published/u", $this->doctor());

        // The file 0.4 published: one web + auth group.
        file_put_contents(base_path('routes/ui-kit-user.php'), "<?php\n\nRoute::middleware(['web', 'auth'])->group(function () {\n    /* ui-kit:user-routes-start */\n    /* ui-kit:user-routes-end */\n});\n");

        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*– skip\\s*\\|\\s*no installed module adds a public page/u", $this->doctor());

        config(['ui-kit.installed_modules' => ['profile', 'contacts', 'changelog']]);
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✗ fail\\s*\\|.*ui-kit:install --force.*ui-kit:install-module contacts changelog/u", $this->doctor());

        copy(__DIR__.'/../../stubs/core/routes/ui-kit-user.php', base_path('routes/ui-kit-user.php'));
        $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*✓ pass/u", $this->doctor());
    }

    public function test_a_module_missing_its_routes_mail_or_policy_fails(): void
    {
        config(['ui-kit.installed_modules' => ['support-tickets', 'contacts']]);

        // The class may already be loaded by an earlier test in this process;
        // the view is missing either way.
        $output = $this->doctor();
        $this->assertMatchesRegularExpression('/contacts routes registered\s*\|\s*✗ fail\s*\|\s*missing: admin\.contacts\.index, contact\./u', $output);
        $this->assertMatchesRegularExpression('/contacts mail views published\s*\|\s*✗ fail\s*\|\s*missing: (App\\\\Mail\\\\ContactSubmittedMail, )?resources\/views\/mail\/contact\/submitted\.blade\.php/u', $output);
        $this->assertMatchesRegularExpression('/support ticket policy published\s*\|\s*✗ fail\s*\|.*403/u', $output);

        // What the installer puts in place.
        foreach (['support-tickets', 'contacts'] as $module) {
            $stubs = __DIR__."/../../stubs/modules/{$module}";
            (new Filesystem)->copyDirectory("{$stubs}/Mail", app_path('Mail'));
            (new Filesystem)->copyDirectory("{$stubs}/views/mail", resource_path('views/mail'));
        }
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/modules/support-tickets/Policies', app_path('Policies'));
        spl_autoload_register($loader = function (string $class): void {
            $path = app_path(str_replace('\\', '/', substr($class, 4)).'.php');

            if (str_starts_with($class, 'App\\') && file_exists($path)) {
                require_once $path;
            }
        });

        foreach (['admin.support.index', 'admin.support.show', 'support.index', 'support.create', 'support.show', 'admin.contacts.index', 'contact'] as $name) {
            Route::get('/'.str_replace('.', '/', $name), fn () => 'ok')->name($name);
        }
        Route::getRoutes()->refreshNameLookups();

        try {
            $output = $this->doctor();

            foreach (['support-tickets routes registered', 'support-tickets mail views published', 'contacts routes registered', 'contacts mail views published', 'support ticket policy published'] as $row) {
                $this->assertMatchesRegularExpression('/'.preg_quote($row, '/').'\s*\|\s*✓ pass/u', $output);
            }
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    private function doctor(): string
    {
        Artisan::call('ui-kit:doctor');

        return Artisan::output();
    }
}

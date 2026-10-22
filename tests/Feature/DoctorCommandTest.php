<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\Attributes\UsesVendor;
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

    public function test_leftover_theme_file_fails(): void
    {
        $this->assertMatchesRegularExpression('/no leftover ui-kit-theme\.css\s*\|\s*✓ pass/u', $this->doctor());

        @mkdir(resource_path('css'), 0755, true);
        file_put_contents(resource_path('css/ui-kit-theme.css'), "@theme { --color-brand-600: #4f46e5; }\n");

        $this->assertMatchesRegularExpression('/no leftover ui-kit-theme\.css\s*\|\s*✗ fail\s*\|.*delete resources\/css\/ui-kit-theme\.css/u', $this->doctor());
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
        $this->assertStringNotContainsString('Users => users', $output);
    }

    private function doctor(): string
    {
        Artisan::call('ui-kit:doctor');

        return Artisan::output();
    }
}

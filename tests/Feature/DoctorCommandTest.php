<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Shipbytes\UiKit\Tests\TestCase;

/**
 * The Livewire 4 migration checks: a 0.2-era app keeps booting through the
 * Volt shim, so the doctor is what reports the unfinished upgrade.
 */
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

    private function doctor(): string
    {
        Artisan::call('ui-kit:doctor');

        return Artisan::output();
    }
}

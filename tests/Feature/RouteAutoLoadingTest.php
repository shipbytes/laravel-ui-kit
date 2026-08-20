<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Shipbytes\UiKit\Tests\TestCase;
use Shipbytes\UiKit\UiKitServiceProvider;

class RouteAutoLoadingTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(base_path('routes/admin.php'));

        parent::tearDown();
    }

    public function test_kit_managed_route_files_are_loaded(): void
    {
        file_put_contents(base_path('routes/admin.php'), <<<'PHP'
<?php

// ui-kit:managed — auto-loaded by UiKitServiceProvider.

use Illuminate\Support\Facades\Route;

Route::get('/managed-probe', fn () => 'ok')->name('managed-probe');
PHP);

        $this->reloadKitRoutes();

        $this->assertTrue(Route::has('managed-probe'));
    }

    public function test_foreign_route_files_are_left_alone(): void
    {
        file_put_contents(base_path('routes/admin.php'), <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/foreign-probe', fn () => 'ok')->name('foreign-probe');
PHP);

        $this->reloadKitRoutes();

        $this->assertFalse(
            Route::has('foreign-probe'),
            'files without the ui-kit:managed header must never be auto-loaded (double registration breaks route:cache)'
        );
    }

    private function reloadKitRoutes(): void
    {
        $provider = new UiKitServiceProvider($this->app);

        $ref = new \ReflectionMethod($provider, 'registerRoutes');
        $ref->setAccessible(true);
        $ref->invoke($provider);

        // Runtime-registered routes need their name index rebuilt before
        // Route::has() can see them (the framework does this at boot).
        Route::getRoutes()->refreshNameLookups();
    }
}

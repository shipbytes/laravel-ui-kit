<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Shipbytes\UiKit\Support\UiKit;
use Shipbytes\UiKit\Tests\TestCase;

class UiKitHelperTest extends TestCase
{
    public function test_home_url_falls_back_to_root_when_route_is_missing(): void
    {
        config(['ui-kit.brand.home_route' => 'home']);

        $this->assertSame('/', UiKit::homeUrl());
    }

    public function test_home_url_uses_the_named_route_when_it_exists(): void
    {
        config(['ui-kit.brand.home_route' => 'landing']);
        Route::get('/welcome-page', fn () => 'ok')->name('landing');
        Route::getRoutes()->refreshNameLookups();

        $this->assertSame('/welcome-page', UiKit::homeUrl());
    }

    public function test_home_url_handles_empty_config(): void
    {
        config(['ui-kit.brand.home_route' => null]);

        $this->assertSame('/', UiKit::homeUrl());
    }
}

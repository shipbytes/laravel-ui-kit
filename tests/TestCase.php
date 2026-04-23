<?php

namespace Shipbytes\UiKit\Tests;

use Livewire\LivewireServiceProvider;
use Livewire\Volt\VoltServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Shipbytes\UiKit\UiKitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            VoltServiceProvider::class,
            UiKitServiceProvider::class,
        ];
    }
}
